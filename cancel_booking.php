<?php
include('assets/conn.php');  // Include database connection

if (!isset($_POST['booking_ids']) || !isset($_POST['reason'])) {
    die("Missing required fields.");
}

$booking_ids = $_POST['booking_ids']; // Still sent as array with one ID
$cancellation_reason = trim($_POST['reason']);

if (!is_array($booking_ids) || count($booking_ids) === 0) {
    die("Invalid booking selection.");
}

$all_related_ids = []; // Store all booking IDs to cancel

// Step 1: For each booking_id, get the booking_id_gen
foreach ($booking_ids as $booking_id) {
    $stmtGen = $conn->prepare("SELECT booking_id_gen FROM bookings WHERE booking_id = ?");
    $stmtGen->bind_param("i", $booking_id);
    $stmtGen->execute();
    $stmtGen->bind_result($booking_id_gen);

    if ($stmtGen->fetch()) {
        $stmtGen->close();

        // Step 2: Now fetch all booking_ids with same booking_id_gen
        $stmtIds = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id_gen = ?");
        $stmtIds->bind_param("s", $booking_id_gen);
        $stmtIds->execute();
        $result = $stmtIds->get_result();

        while ($row = $result->fetch_assoc()) {
            $all_related_ids[] = $row['booking_id'];
        }

        $stmtIds->close();
    } else {
        $stmtGen->close();
    }
}

// Step 3: Now cancel all booking_ids in $all_related_ids
if (count($all_related_ids) > 0) {
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
    
    $stmtUpdate = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = ?, is_active = ? WHERE booking_id = ?");

    // Determine if the cancellation reason should deactivate the booking
    $is_active = true; // Default to true (1)
    $inactive_reasons = [
        "Change of plans", 
        "Scheduling conflict", 
        "Requested to Change", 
        "Other"
    ];

    // Check if the cancellation reason matches any of the "inactive" reasons
    if (in_array($cancellation_reason, $inactive_reasons)) {
        $is_active = false; // Set to false (0) if the reason matches
    }

    $slot_start_times = [
        1 => '9:30 AM', 2 => '10:30 AM', 3 => '11:30 AM', 4 => '12:30 PM',
        5 => '1:30 PM', 6 => '2:30 PM', 7 => '3:30 PM', 8 => '4:30 PM'
    ];

    foreach ($all_related_ids as $b_id) {
        // Fetch booking details before cancelling
        $fetch_query = "SELECT b.organiser_name, b.start_date, b.end_date, b.slot_or_session, b.booking_id_gen,
                               h.hall_name, d.department_name, u.email
                        FROM bookings b
                        JOIN hall_details h ON b.hall_id = h.hall_id
                        LEFT JOIN departments d ON h.department_id = d.department_id
                        JOIN users u ON b.user_id = u.user_id
                        WHERE b.booking_id = ?";
        $fetch_stmt = $conn->prepare($fetch_query);
        $fetch_stmt->bind_param("i", $b_id);
        $fetch_stmt->execute();
        $fetch_result = $fetch_stmt->get_result();
        
        if ($booking_data = $fetch_result->fetch_assoc()) {
            $organiser_name = $booking_data['organiser_name'];
            $start_date = $booking_data['start_date'];
            $end_date = $booking_data['end_date'];
            $slot_or_session = $booking_data['slot_or_session'];
            $booking_id_gen = $booking_data['booking_id_gen'];
            $hall_name = $booking_data['hall_name'];
            $department_name = $booking_data['department_name'];
            $user_email = $booking_data['email'];
            
            // Process slots
            $slots = explode(',', $slot_or_session);
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
            
            if ($start_date == $end_date) {
                $date_info = $start_date;
            } else {
                $date_info = "from $start_date to $end_date";
            }
            
            // Send cancellation email
            $msg = getBookingCancelledEmail($organiser_name, $hall_name, $department_name, $date_info, $session_type, $slot_time, $cancellation_reason, $booking_id_gen);
            smtp_mailer($user_email, 'Booking Cancelled', $msg);
        }
        $fetch_stmt->close();
        
        // Update booking status
        $stmtUpdate->bind_param("sii", $cancellation_reason, $is_active, $b_id);
        $stmtUpdate->execute();
    }

    if ($stmtUpdate->affected_rows > 0) {
        echo "success";
    } else {
        echo "No bookings were updated.";
    }

    $stmtUpdate->close();
} else {
    echo "No related bookings found.";
}

$conn->close();
?>
