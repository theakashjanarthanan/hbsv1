<?php
// Check if the 'id' parameter is set in the URL
if (isset($_GET['id'])) {
    // Include database connection
    include 'assets/conn.php';
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

    // Get the booking ID from the query string
    $booking_id = $_GET['id'];

    // Fetch booking details before deleting
    $fetch_query = "SELECT b.organiser_name, b.start_date, b.end_date, b.slot_or_session, b.booking_id_gen,
                           h.hall_name, d.department_name, u.email
                    FROM bookings b
                    JOIN hall_details h ON b.hall_id = h.hall_id
                    LEFT JOIN departments d ON h.department_id = d.department_id
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.booking_id = ?";
    $fetch_stmt = $conn->prepare($fetch_query);
    $fetch_stmt->bind_param("i", $booking_id);
    $fetch_stmt->execute();
    $fetch_result = $fetch_stmt->get_result();
    
    $booking_data = null;
    if ($fetch_result->num_rows > 0) {
        $booking_data = $fetch_result->fetch_assoc();
    }
    $fetch_stmt->close();

    // SQL to delete the booking
    $sql = "DELETE FROM bookings WHERE booking_id = ?";

    // Prepare the statement
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $booking_id);
        
        // Execute the query
        if ($stmt->execute()) {
            // Send deletion email if booking data was found
            if ($booking_data) {
                $slot_start_times = [
                    1 => '9:30 AM', 2 => '10:30 AM', 3 => '11:30 AM', 4 => '12:30 PM',
                    5 => '1:30 PM', 6 => '2:30 PM', 7 => '3:30 PM', 8 => '4:30 PM'
                ];
                
                $slots = explode(',', $booking_data['slot_or_session']);
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
                
                if ($booking_data['start_date'] == $booking_data['end_date']) {
                    $date_info = $booking_data['start_date'];
                } else {
                    $date_info = "from " . $booking_data['start_date'] . " to " . $booking_data['end_date'];
                }
                
                $msg = getBookingDeletedEmail(
                    $booking_data['organiser_name'],
                    $booking_data['hall_name'],
                    $booking_data['department_name'],
                    $date_info,
                    $session_type,
                    $slot_time,
                    $booking_data['booking_id_gen']
                );
                smtp_mailer($booking_data['email'], 'Booking Deleted', $msg);
            }
            
            echo "Booking deleted successfully!";
            // Optionally, redirect back to the bookings list or confirmation page
            header("Location: view_modify_booking.php");
            exit;
        } else {
            echo "Error deleting booking: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    $conn->close();
} else {
    echo "No booking ID provided.";
}
?>
