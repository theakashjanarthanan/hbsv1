<?php
include 'assets/conn.php';

header('Content-Type: application/json');

if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Fetch feedback data
$sql = "SELECT overall_rating, cleanliness, seating, lighting, audio, ac, additional_feedback 
        FROM hall_feedback 
        WHERE booking_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $feedback = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No feedback found for this booking'
    ]);
}

$stmt->close();
$conn->close();
?>
