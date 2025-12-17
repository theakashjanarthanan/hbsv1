<?php
include 'assets/conn.php';

header('Content-Type: application/json');

if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$booking_id = intval($_POST['booking_id']);

// Delete feedback
$sql = "DELETE FROM hall_feedback WHERE booking_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Feedback deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No feedback found to delete']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting feedback: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
