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

    foreach ($all_related_ids as $b_id) {
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
