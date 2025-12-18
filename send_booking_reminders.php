<?php
/**
 * Booking Reminder Email Sender
 * This script should be run every 5-10 minutes via cron job to send booking reminders
 * 30 minutes before bookings start (only for individual bookings, not semester bookings)
 * 
 * Cron example: */10 * * * * /usr/bin/php /path/to/send_booking_reminders.php
 */

include('assets/conn.php');
include('smtp/PHPMailerAutoload.php');
include('assets/email_template.php');

// SMTP mailer function
function smtp_mailer($to, $subject, $msg) {
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Username = "hbs.superuser@gmail.com";
    $mail->Password = 'ubwncbdpsjvvxyus';
    $mail->SetFrom("hbs.superuser@gmail.com", "HBS - Pondicherry University");
    $mail->Subject = $subject;
    $mail->Body = $msg;
    $mail->AddAddress($to);
    $mail->SMTPOptions = array('ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false
    ));
    
    if (!$mail->Send()) {
        return false;
    }
    return true;
}

// Get current date and time
$currentDateTime = new DateTime();
$currentDate = $currentDateTime->format('Y-m-d');
$currentTime = $currentDateTime->format('H:i:s');

// Calculate 30 minutes from now
$reminderTime = clone $currentDateTime;
$reminderTime->modify('+30 minutes');
$reminderDate = $reminderTime->format('Y-m-d');
$reminderTimeStr = $reminderTime->format('H:i:s');

// Slot start times mapping
$slot_start_times = [
    1 => ['time' => '09:30', 'display' => '9:30 AM'],
    2 => ['time' => '10:30', 'display' => '10:30 AM'],
    3 => ['time' => '11:30', 'display' => '11:30 AM'],
    4 => ['time' => '12:30', 'display' => '12:30 PM'],
    5 => ['time' => '13:30', 'display' => '1:30 PM'],
    6 => ['time' => '14:30', 'display' => '2:30 PM'],
    7 => ['time' => '15:30', 'display' => '3:30 PM'],
    8 => ['time' => '16:30', 'display' => '4:30 PM']
];

// Find bookings that:
// 1. Start today (start_date = today)
// 2. Are approved
// 3. Have a slot starting in approximately 30 minutes
// 4. Are individual bookings (not semester bookings - check if day_of_week is NULL)
// 5. Haven't been sent a reminder yet (you may want to add a reminder_sent flag)

$query = "SELECT b.booking_id, b.organiser_name, b.start_date, b.end_date, b.slot_or_session, 
                 b.booking_id_gen, h.hall_name, d.department_name, u.email, b.day_of_week
          FROM bookings b
          JOIN hall_details h ON b.hall_id = h.hall_id
          LEFT JOIN departments d ON h.department_id = d.department_id
          JOIN users u ON b.user_id = u.user_id
          WHERE b.start_date = ?
          AND b.status = 'approved'
          AND b.is_active = 1
          AND (b.day_of_week IS NULL OR b.day_of_week = '')";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $reminderDate);
$stmt->execute();
$result = $stmt->get_result();

$emailsSent = 0;
$emailsFailed = 0;

while ($booking = $result->fetch_assoc()) {
    // Check if any slot starts in approximately 30 minutes
    $slots = explode(',', $booking['slot_or_session']);
    $shouldSendReminder = false;
    $firstSlotTime = null;
    
    foreach ($slots as $slot) {
        $slotNum = intval(trim($slot));
        if (isset($slot_start_times[$slotNum])) {
            $slotTime = $slot_start_times[$slotNum]['time'];
            $slotDateTime = new DateTime($reminderDate . ' ' . $slotTime . ':00');
            $timeDiff = abs($slotDateTime->getTimestamp() - $reminderTime->getTimestamp());
            
            // Check if slot starts within 5 minutes of reminder time (30 minutes from now)
            if ($timeDiff <= 300) { // 5 minutes tolerance
                $shouldSendReminder = true;
                if ($firstSlotTime === null) {
                    $firstSlotTime = $slot_start_times[$slotNum]['display'];
                }
                break;
            }
        }
    }
    
    if ($shouldSendReminder) {
        // Process slots for display
        $session_type = "";
        $slot_time = "";
        
        if (count($slots) == 8) {
            $session_type = "Full Day";
            $slot_time = "9:30 AM to 4:30 PM";
        } elseif (count(array_intersect($slots, [1, 2, 3, 4])) == 4) {
            $session_type = "Forenoon";
            $slot_time = "9:30 AM to 12:30 PM";
        } elseif (count(array_intersect($slots, [5, 6, 7, 8])) == 4) {
            $session_type = "Afternoon";
            $slot_time = "1:30 PM to 4:30 PM";
        } else {
            $slot_time = implode(", ", array_map(function($slot) use ($slot_start_times) {
                $slotNum = intval(trim($slot));
                return isset($slot_start_times[$slotNum]) ? $slot_start_times[$slotNum]['display'] : "Invalid Slot";
            }, $slots));
        }
        
        // Format date and time for display
        $dateTimeInfo = $booking['start_date'];
        if ($firstSlotTime) {
            $dateTimeInfo .= " at " . $firstSlotTime;
        }
        
        // Send reminder email
        $msg = getBookingReminderEmail(
            $booking['organiser_name'],
            $booking['hall_name'],
            $booking['department_name'],
            $dateTimeInfo,
            $session_type,
            $slot_time,
            $booking['booking_id_gen'],
            "30 minutes"
        );
        
        if (smtp_mailer($booking['email'], 'Booking Reminder - Starting in 30 Minutes', $msg)) {
            $emailsSent++;
            // Optional: Mark reminder as sent in database
            // UPDATE bookings SET reminder_sent = 1 WHERE booking_id = ?
        } else {
            $emailsFailed++;
        }
    }
}

$stmt->close();
$conn->close();

// Log results (optional)
echo "Booking reminders sent: $emailsSent\n";
echo "Failed: $emailsFailed\n";
?>

