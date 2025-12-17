<?php
include "assets/conn.php"; // Database connection

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

    if ($action === 'update') {
        // Update existing feedback
        $stmt = $conn->prepare("UPDATE hall_feedback 
                                SET overall_rating = ?, cleanliness = ?, seating = ?, lighting = ?, audio = ?, ac = ?, additional_feedback = ?
                                WHERE booking_id = ?");
        $stmt->bind_param("sssssssi", $overall, $cleanliness, $seating, $lighting, $audio, $ac, $additional_feedback, $booking_id);
        
        if ($stmt->execute()) {
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
