<?php
include "assets/conn.php"; // Database connection
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $booking_id = $_POST["booking_id"];
    $action = $_POST["action"] ?? 'submit';
    $overall = $_POST["overall"];
    $cleanliness = $_POST["cleanliness"];
    $seating = $_POST["seating"];
    $lighting = $_POST["lighting"];
    $audio = $_POST["audio"];
    $ac = $_POST["ac"];
    $additional_feedback = $_POST["additional_feedback"];

    // Fetch booking details for email
    $fetch_query = "SELECT b.organiser_name, h.hall_name, d.department_name, u.email
                    FROM bookings b
                    JOIN hall_details h ON b.hall_id = h.hall_id
                    LEFT JOIN departments d ON h.department_id = d.department_id
                    JOIN users u ON b.user_id = u.user_id
                    WHERE b.booking_id = ?";
    $fetch_stmt = $conn->prepare($fetch_query);
    $fetch_stmt->bind_param("i", $booking_id);
    $fetch_stmt->execute();
    $fetch_result = $fetch_stmt->get_result();
    $booking_data = $fetch_result->fetch_assoc();
    $fetch_stmt->close();

    if ($action === 'update') {
        // Update existing feedback
        $stmt = $conn->prepare("UPDATE hall_feedback 
                                SET overall_rating = ?, cleanliness = ?, seating = ?, lighting = ?, audio = ?, ac = ?, additional_feedback = ?
                                WHERE booking_id = ?");
        $stmt->bind_param("sssssssi", $overall, $cleanliness, $seating, $lighting, $audio, $ac, $additional_feedback, $booking_id);
        
        if ($stmt->execute()) {
            // Send thank you email
            if ($booking_data) {
                $msg = getFeedbackThankYouEmail(
                    $booking_data['organiser_name'],
                    $booking_data['hall_name'],
                    $booking_data['department_name']
                );
                smtp_mailer($booking_data['email'], 'Thank You for Your Feedback', $msg);
            }
            
            $redirect = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '' ? $_SERVER['HTTP_REFERER'] : 'view_modify_booking.php';
            echo "<script>alert('Feedback updated successfully!'); window.location.href='" . $redirect . "';</script>";
        } else {
            echo "<script>alert('Error updating feedback.'); window.history.back();</script>";
        }
    } else {
        // Insert new feedback
        $stmt = $conn->prepare("INSERT INTO hall_feedback (booking_id, overall_rating, cleanliness, seating, lighting, audio, ac, additional_feedback) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $booking_id, $overall, $cleanliness, $seating, $lighting, $audio, $ac, $additional_feedback);
        
        if ($stmt->execute()) {
            // Send thank you email
            if ($booking_data) {
                $msg = getFeedbackThankYouEmail(
                    $booking_data['organiser_name'],
                    $booking_data['hall_name'],
                    $booking_data['department_name']
                );
                smtp_mailer($booking_data['email'], 'Thank You for Your Feedback', $msg);
            }
            
            $redirect = isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] !== '' ? $_SERVER['HTTP_REFERER'] : 'view_modify_booking.php';
            echo "<script>alert('Feedback submitted successfully!'); window.location.href='" . $redirect . "';</script>";
        } else {
            echo "<script>alert('Error submitting feedback.'); window.history.back();</script>";
        }
    }

    $stmt->close();
    $conn->close();
}
?>
