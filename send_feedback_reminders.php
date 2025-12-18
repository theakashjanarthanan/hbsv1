<?php
/**
 * Feedback Reminder Email Sender
 * This script should be run daily via cron job to send feedback reminders
 * 7 days after a booking has been used
 * 
 * Cron example: 0 9 * * * /usr/bin/php /path/to/send_feedback_reminders.php
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

// Calculate date 7 days ago
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

// Find bookings that:
// 1. Were used 7 days ago (end_date = 7 days ago)
// 2. Are approved
// 3. Don't have feedback yet
// 4. Haven't been sent a reminder yet (you may want to add a reminder_sent flag)

$query = "SELECT DISTINCT b.booking_id, b.organiser_name, b.start_date, b.end_date, b.slot_or_session, 
                 b.booking_id_gen, h.hall_name, d.department_name, u.email
          FROM bookings b
          JOIN hall_details h ON b.hall_id = h.hall_id
          LEFT JOIN departments d ON h.department_id = d.department_id
          JOIN users u ON b.user_id = u.user_id
          LEFT JOIN hall_feedback hf ON b.booking_id = hf.booking_id
          WHERE b.end_date = ?
          AND b.status = 'approved'
          AND hf.booking_id IS NULL
          AND b.is_active = 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $sevenDaysAgo);
$stmt->execute();
$result = $stmt->get_result();

$slot_start_times = [
    1 => '9:30 AM', 2 => '10:30 AM', 3 => '11:30 AM', 4 => '12:30 PM',
    5 => '1:30 PM', 6 => '2:30 PM', 7 => '3:30 PM', 8 => '4:30 PM'
];

$emailsSent = 0;
$emailsFailed = 0;

while ($booking = $result->fetch_assoc()) {
    // Process slots
    $slots = explode(',', $booking['slot_or_session']);
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
            return isset($slot_start_times[$slot]) ? $slot_start_times[$slot] : "Invalid Slot";
        }, $slots));
    }
    
    if ($booking['start_date'] == $booking['end_date']) {
        $date_info = $booking['start_date'];
    } else {
        $date_info = "from " . $booking['start_date'] . " to " . $booking['end_date'];
    }
    
    // Generate feedback link
    $feedbackLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/submit_feedback.php?booking_id=" . $booking['booking_id'];
    
    // Send feedback reminder email
    $msg = getFeedbackReminderEmail(
        $booking['organiser_name'],
        $booking['hall_name'],
        $booking['department_name'],
        $date_info,
        $session_type,
        $slot_time,
        $booking['booking_id_gen'],
        $feedbackLink
    );
    
    if (smtp_mailer($booking['email'], 'Feedback Request - Your Hall Booking Experience', $msg)) {
        $emailsSent++;
        // Optional: Mark reminder as sent in database
        // UPDATE bookings SET feedback_reminder_sent = 1 WHERE booking_id = ?
    } else {
        $emailsFailed++;
    }
}

$stmt->close();
$conn->close();

// Log results (optional)
echo "Feedback reminders sent: $emailsSent\n";
echo "Failed: $emailsFailed\n";
?>

