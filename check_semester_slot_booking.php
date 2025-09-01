<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');  // Ensure the JSON response
include('assets/conn.php');  // Include database connection

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Get POST data
$day_of_week = $_POST['day_of_week'] ?? '';
$slots = isset($_POST['slots']) ? explode(',', $_POST['slots']) : []; // Convert CSV slots into an array
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$hall_id = $_POST['hall_id'] ?? '';
$booking_id = $_POST['booking_id'] ?? ''; // Current booking ID being edited

if (empty($hall_id)) {
    echo json_encode(["error" => "Hall ID is required."]);
    exit;
}

// If any of the key fields are missing, we can't check conflicts
if (empty($day_of_week) || empty($slots) || empty($start_date) || empty($end_date)) {
    echo json_encode(["error" => ""]); // No error, just no conflicts to check
    exit;
}

// Get the booking_id_gen for the current booking
$booking_id_gen = '';
if (!empty($booking_id)) {
    $getBookingGenQuery = "SELECT booking_id_gen FROM bookings WHERE booking_id = ?";
    $getBookingGenStmt = $conn->prepare($getBookingGenQuery);
    $getBookingGenStmt->bind_param("i", $booking_id);
    $getBookingGenStmt->execute();
    $bookingGenResult = $getBookingGenStmt->get_result();
    
    if ($bookingGenResult->num_rows > 0) {
        $booking_id_gen = $bookingGenResult->fetch_assoc()['booking_id_gen'];
    }
    $getBookingGenStmt->close();
}

$error = [];
$uniqueBookings = [];

$query = "SELECT booking_id_gen, purpose_name, slot_or_session FROM bookings 
          WHERE hall_id = ? 
          AND day_of_week = ? 
          AND status IN ('pending', 'approved')
          AND booking_id_gen != ? 
          AND (
              (start_date <= ? AND end_date >= ?) OR 
              (start_date >= ? AND start_date <= ?)
          )";

if ($stmt = $conn->prepare($query)) {
    // Bind parameters
    $stmt->bind_param("sssssss", $hall_id, $day_of_week, $booking_id_gen, $end_date, $start_date, $start_date, $end_date);
    if (!$stmt->execute()) {
        error_log("Query execution failed: " . $stmt->error);
        echo json_encode(["error" => "Error executing query."]);
        exit;
    }

    $result = $stmt->get_result();

    // Process conflicts
    while ($row = $result->fetch_assoc()) {
        $booking_id_gen = $row['booking_id_gen'];
        $booked_slots = explode(',', $row['slot_or_session']); // Convert stored slots to array

        // Check if any booked slot matches the requested slots
        foreach ($slots as $slot) {
            if (in_array($slot, $booked_slots)) {
                $uniqueBookings["$booking_id_gen-$slot"] = "Class <b>{$row['purpose_name']}</b> is already booked for slot <b>{$slot}</b>";
            }
        }
    }

    $stmt->close();
} else {
    error_log("Query preparation failed: " . $conn->error);
    echo json_encode(["error" => "Error preparing the query."]);
    exit;
}

// Format error message if conflicts exist
if (!empty($uniqueBookings)) {
    $error = implode("<br>", array_values($uniqueBookings));
}

echo json_encode(["error" => $error]);
?>
