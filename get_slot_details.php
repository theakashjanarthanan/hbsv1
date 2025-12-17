<?php
include 'assets/conn.php';

// Set the correct content type for JSON output
header('Content-Type: application/json');

// Check that all required GET parameters are provided
if (isset($_GET['hall_id'], $_GET['day_of_week'], $_GET['slot_number'], $_GET['start_date'], $_GET['end_date'])) {
    $hall_id = $_GET['hall_id'];
    $day_of_week = $_GET['day_of_week'];
    $slot_number = $_GET['slot_number'];
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];

    // Query to get detailed booking information for the specific slot
    $query = "
        SELECT 
            b.purpose_name,
            b.event_type,
            b.organiser_name,
            b.organiser_department,
            b.organiser_mobile,
            b.organiser_email,
            b.start_date,
            b.end_date,
            b.day_of_week,
            b.slot_or_session,
            b.status
        FROM 
            bookings b 
        WHERE 
            b.hall_id = ?
            AND b.day_of_week = ?
            AND FIND_IN_SET(?, b.slot_or_session)
            AND b.start_date >= ?
            AND b.end_date <= ?
            AND b.status = 'approved'
            AND b.is_active = 1
        LIMIT 1
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $hall_id, $day_of_week, $slot_number, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        echo json_encode([
            "status" => "approved",
            "booking" => $booking
        ]);
    } else {
        echo json_encode([
            "status" => "available",
            "message" => "Slot is available for booking"
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["error" => "Required parameters not provided"]);
}
?>
