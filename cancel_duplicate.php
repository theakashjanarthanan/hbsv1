<?php
require 'assets/conn.php';  // Include database connection
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if ($bookingId && $reason) {
        // Fetch booking details before cancelling
        $fetch_query = "SELECT b.organiser_name, b.start_date, b.end_date, b.slot_or_session, b.booking_id_gen,
                               h.hall_name, d.department_name, u.email
                        FROM bookings b
                        JOIN hall_details h ON b.hall_id = h.hall_id
                        LEFT JOIN departments d ON h.department_id = d.department_id
                        JOIN users u ON b.user_id = u.user_id
                        WHERE b.booking_id = ?";
        $fetch_stmt = $conn->prepare($fetch_query);
        $fetch_stmt->bind_param("i", $bookingId);
        $fetch_stmt->execute();
        $fetch_stmt->bind_result($organiser_name, $start_date, $end_date, $slot_or_session, $booking_id_gen, $hall_name, $department_name, $user_email);
        $fetch_stmt->fetch();
        $fetch_stmt->close();

        // Update booking status
        $query = "UPDATE bookings SET status = 'cancelled', cancellation_reason = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $reason, $bookingId);

        if ($stmt->execute()) {
            // Prepare email data
            $slot_start_times = [
                1 => '9:30 AM', 2 => '10:30 AM', 3 => '11:30 AM', 4 => '12:30 PM',
                5 => '1:30 PM', 6 => '2:30 PM', 7 => '3:30 PM', 8 => '4:30 PM'
            ];
            
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
            $msg = getBookingCancelledEmail($organiser_name, $hall_name, $department_name, $date_info, $session_type, $slot_time, $reason, $booking_id_gen);
            smtp_mailer($user_email, 'Booking Cancelled', $msg);
            
            echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error cancelling booking.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    }

    $conn->close();
}
?>
