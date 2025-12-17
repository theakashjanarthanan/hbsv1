<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');  // Ensure the JSON response

require 'assets/conn.php';  // Include database connection

// Check database connection
if (!$conn) {
    error_log("Database connection failed");
    echo json_encode(['available' => false, 'message' => 'Database connection failed']);
    exit;
}

    // Check if required tables exist
    $tables = ['bookings', 'hall_details'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            error_log("Table $table does not exist");
            echo json_encode(['available' => false, 'message' => "Required table '$table' does not exist"]);
            exit;
        }
    }
    
    // Check table structure for debugging
    try {
        $bookingsStructure = $conn->query("DESCRIBE bookings");
        $hallDetailsStructure = $conn->query("DESCRIBE hall_details");
        
        error_log("Bookings table structure: " . print_r($bookingsStructure->fetch_all(MYSQLI_ASSOC), true));
        error_log("Hall details table structure: " . print_r($hallDetailsStructure->fetch_all(MYSQLI_ASSOC), true));
    } catch (Exception $e) {
        error_log("Error checking table structure: " . $e->getMessage());
    }

// Add artificial delay for loading simulation (remove in production)
// usleep(500000); // 0.5 second delay - commented out for debugging

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug logging
    error_log("POST data received: " . print_r($_POST, true));
    
    $hallId = isset($_POST['hall_id']) ? intval($_POST['hall_id']) : 0;
    $organiserId = isset($_POST['organiser_id']) ? intval($_POST['organiser_id']) : 0;
    $startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $bookingType = isset($_POST['booking_type']) ? $_POST['booking_type'] : '';
    $sessionOrSlots = isset($_POST['session_or_slots']) ? $_POST['session_or_slots'] : '';

    // Debug logging
    error_log("Parsed values - hallId: $hallId, organiserId: $organiserId, startDate: $startDate, endDate: $endDate, bookingType: $bookingType, sessionOrSlots: $sessionOrSlots");

    if ($hallId && $organiserId && $startDate && $endDate && $bookingType && $sessionOrSlots) {
        try {
            $availabilityResult = checkHallAvailability(
                $conn,
                $hallId,
                $organiserId,
                $startDate,
                $endDate,
                $bookingType,
                $bookingType === 'slots' ? $slots : $sessionOrSlots
            );            
            echo json_encode($availabilityResult);
        } catch (Exception $e) {
            error_log("Error in checkHallAvailability: " . $e->getMessage());
            echo json_encode(['available' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    } else {
        $missingParams = [];
        if (!$hallId) $missingParams[] = 'hall_id';
        if (!$organiserId) $missingParams[] = 'organiser_id';
        if (!$startDate) $missingParams[] = 'start_date';
        if (!$endDate) $missingParams[] = 'end_date';
        if (!$bookingType) $missingParams[] = 'booking_type';
        if (!$sessionOrSlots) $missingParams[] = 'session_or_slots';
        
        echo json_encode([
            'available' => false, 
            'message' => 'Missing required parameters: ' . implode(', ', $missingParams),
            'debug' => [
                'hallId' => $hallId,
                'organiserId' => $organiserId,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'bookingType' => $bookingType,
                'sessionOrSlots' => $sessionOrSlots
            ]
        ]);
    }
} else {
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}

function checkHallAvailability($conn, $hall_id, $organiser_id, $start_date, $end_date, $booking_type, $session_or_slots) {
    try {
            // Debug logging
    error_log("checkHallAvailability called with: hall_id=$hall_id, organiser_id=$organiser_id, start_date=$start_date, end_date=$end_date, booking_type=$booking_type, session_or_slots=$session_or_slots");
    
    // Validate input parameters
    if (!is_numeric($hall_id) || $hall_id <= 0) {
        error_log("Invalid hall_id: $hall_id");
        return ['available' => false, 'message' => 'Invalid hall ID'];
    }
    
    if (!is_numeric($organiser_id) || $organiser_id <= 0) {
        error_log("Invalid organiser_id: $organiser_id");
        return ['available' => false, 'message' => 'Invalid organizer ID'];
    }
    
    if (empty($start_date) || empty($end_date)) {
        error_log("Empty dates: start_date=$start_date, end_date=$end_date");
        return ['available' => false, 'message' => 'Invalid dates'];
    }
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        error_log("Invalid date format: start_date=$start_date, end_date=$end_date");
        return ['available' => false, 'message' => 'Invalid date format. Please use YYYY-MM-DD format.'];
    }
    
    // MODIFIED: Only check for approved/booked status, allow pending status to create conflicts
    $query = "SELECT DISTINCT b.slot_or_session, b.status, b.booking_id_gen, b.organiser_name, b.purpose_name 
              FROM bookings b
              WHERE b.hall_id = ? 
              AND b.status IN ('approved', 'booked')  -- Removed 'pending' to allow pending bookings
              AND NOT (b.end_date < ? OR b.start_date > ?)
              ORDER BY b.start_date DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return ['available' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $bindResult = $stmt->bind_param("iss", $hall_id, $start_date, $end_date);
    if (!$bindResult) {
        error_log("Bind failed: " . $stmt->error);
        return ['available' => false, 'message' => 'Database bind error: ' . $stmt->error];
    }
    
    $executeResult = $stmt->execute();
    if (!$executeResult) {
        error_log("Execute failed: " . $stmt->error);
        return ['available' => false, 'message' => 'Database execute error: ' . $stmt->error];
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("Get result failed: " . $stmt->error);
        return ['available' => false, 'message' => 'Database result error: ' . $stmt->error];
    }
    
    // Debug: Log the actual query being executed
    error_log("Executed query: " . $query);
    error_log("Bound parameters: hall_id=$hall_id, start_date=$start_date, end_date=$end_date");
    error_log("Query result rows: " . $result->num_rows);

    $bookedSlots = [];
    $conflictingBookings = [];
    
    while ($row = $result->fetch_assoc()) {
        // Parse the slot_or_session field which may contain multiple slots
        $slots = explode(',', $row['slot_or_session']);
        foreach ($slots as $slot) {
            $slot = trim($slot);
            if (!empty($slot)) {
                $bookedSlots[] = $slot;
                $conflictingBookings[] = [
                    'slot' => $slot,
                    'status' => $row['status'],
                    'booking_id' => $row['booking_id_gen'],
                    'organiser' => $row['organiser_name'],
                    'purpose' => $row['purpose_name']
                ];
            }
        }
    }
    
    $bookedSlots = array_unique($bookedSlots);

    // Determine requested slots based on the booking type
    $requestedSlots = ($booking_type === 'session') ? getSessionSlots($session_or_slots) : explode(',', $session_or_slots);
    $requestedSlots = array_map('trim', $requestedSlots);
    if ($booking_type === 'session') {
        $requestedSlots = getSessionSlots($session_or_slots);
    } else {
        // Already an array if from checkboxes
        $requestedSlots = is_array($session_or_slots)
            ? $session_or_slots
            : explode(',', $session_or_slots);
    }
    

    // Compare booked slots with requested slots
    $conflictingSlots = array_intersect($bookedSlots, $requestedSlots);

    if (!empty($conflictingSlots)) {
        // Filter conflicting bookings for the specific slots
        $relevantConflicts = array_filter($conflictingBookings, function($booking) use ($conflictingSlots) {
            return in_array($booking['slot'], $conflictingSlots);
        });

        $conflictMessages = [];
        foreach ($relevantConflicts as $conflict) {
            $statusColor = $conflict['status'] === 'approved' ? 'text-danger' : 'text-warning';
            $conflictMessages[] = "<span class='{$statusColor}'><b>Slot {$conflict['slot']}</b>: {$conflict['organiser']} ({$conflict['purpose']})</span>";
        }

        return [
            'available' => false,
            'message' => 'Hall is not available for the selected date/slot.',
            'conflicting_slots' => array_values($conflictingSlots),
            'conflicting_bookings' => $relevantConflicts
        ];
    }

    // Check for pending bookings that will create conflicts (informational only)
    $pendingConflictQuery = "SELECT DISTINCT b.slot_or_session, b.status, b.booking_id_gen, b.organiser_name, b.purpose_name 
                            FROM bookings b
                            WHERE b.hall_id = ? 
                            AND b.status = 'pending'
                            AND NOT (b.end_date < ? OR b.start_date > ?)
                            ORDER BY b.start_date DESC";
    
    $stmt = $conn->prepare($pendingConflictQuery);
    if ($stmt) {
        $stmt->bind_param("iss", $hall_id, $start_date, $end_date);
        $stmt->execute();
        $pendingResult = $stmt->get_result();
        
        $pendingConflicts = [];
        while ($row = $pendingResult->fetch_assoc()) {
            $slots = explode(',', $row['slot_or_session']);
            foreach ($slots as $slot) {
                $slot = trim($slot);
                if (!empty($slot) && in_array($slot, $requestedSlots)) {
                    $pendingConflicts[] = [
                        'slot' => $slot,
                        'organiser' => $row['organiser_name'],
                        'purpose' => $row['purpose_name']
                    ];
                }
            }
        }
        
        // If there are pending conflicts, show warning but allow booking
        if (!empty($pendingConflicts)) {
            $pendingMessages = [];
            foreach ($pendingConflicts as $conflict) {
                $pendingMessages[] = "<span class='text-warning'><b>Slot {$conflict['slot']}</b>: {$conflict['organiser']} ({$conflict['purpose']}) - Pending</span>";
            }
            
            return [
                'available' => true,
                'message' => 'Hall is available for the selected date/slot. <br><strong>Note:</strong> This will create a booking conflict with pending requests:<br>' . implode('<br>', $pendingMessages) . '<br><br><em>The conflict will be resolved by administrators during the approval process.</em>',
                'requested_slots' => $requestedSlots,
                'total_slots' => count($requestedSlots),
                'pending_conflicts' => $pendingConflicts,
                'has_conflicts' => true
            ];
        }
    }

    // Simplified duplicate booking check
    $duplicateQuery = "SELECT b.booking_id_gen, b.booking_id, b.hall_id, b.slot_or_session, b.start_date, b.end_date, h.hall_name 
                      FROM bookings b
                      JOIN hall_details h ON b.hall_id = h.hall_id
                      WHERE b.organiser_id = ? 
                      AND b.status IN ('approved', 'allow', 'pending')
                      AND NOT (b.end_date < ? OR b.start_date > ?)";

    $stmt = $conn->prepare($duplicateQuery);
    if (!$stmt) {
        error_log("Duplicate query prepare failed: " . $conn->error);
        return ['available' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $bindResult = $stmt->bind_param("iss", $organiser_id, $start_date, $end_date);
    if (!$bindResult) {
        error_log("Duplicate query bind failed: " . $stmt->error);
        return ['available' => false, 'message' => 'Database bind error: ' . $stmt->error];
    }
    
    $executeResult = $stmt->execute();
    if (!$executeResult) {
        error_log("Duplicate query execute failed: " . $stmt->error);
        return ['available' => false, 'message' => 'Database execute error: ' . $stmt->error];
    }
    
    $duplicateResult = $stmt->get_result();
    if (!$duplicateResult) {
        error_log("Duplicate query get result failed: " . $stmt->error);
        return ['available' => false, 'message' => 'Database result error: ' . $stmt->error];
    }
    
    // Debug: Log duplicate query results
    error_log("Duplicate query result rows: " . $duplicateResult->num_rows);

    $duplicateBookings = [];
    while ($row = $duplicateResult->fetch_assoc()) {
        // Check if the booking slots overlap
        $bookedSlotsInRow = explode(',', $row['slot_or_session']);
        $bookedSlotsInRow = array_map('trim', $bookedSlotsInRow);
        $conflictingSlots = array_intersect($bookedSlotsInRow, $requestedSlots);

        // If there are overlapping slots, mark as a conflict
        if (!empty($conflictingSlots)) {
            $duplicateBookings[] = [
                'booking_id' => $row['booking_id'],
                'booking_id_gen' => $row['booking_id_gen'],
                'date' => $row['start_date'] . ' to ' . $row['end_date'],
                'slot' => $row['slot_or_session'],
                'hall_name' => $row['hall_name'],
                'conflicting_slots' => array_values($conflictingSlots)
            ];
        }
    }

    // If there are duplicate bookings, return the message
    if (!empty($duplicateBookings)) {
        $duplicateBookingsMessage = array_map(function($booking) {
            $conflictingSlotsStr = implode(', ', $booking['conflicting_slots']);
            return '<br><b>' . $booking['hall_name'] . '</b> (' . $booking['date'] . ') Slot: <b>' . $booking['slot'] . '</b> (Conflict: ' . $conflictingSlotsStr . ')';
        }, $duplicateBookings);

        return [
            'available' => false,
            'message' => 'Duplicate booking detected in:' . implode('; ', $duplicateBookingsMessage) . '
                <button id="cancel_booking_btn" class="btn btn-sm btn-outline-danger ms-2" data-booking-id="' . $duplicateBookings[0]['booking_id'] . '">Cancel</button>',
            'duplicate_bookings' => $duplicateBookings
        ];
    }

    return [
        'available' => true, 
        'message' => 'Hall is available for the selected date/slot.',
        'requested_slots' => $requestedSlots,
        'total_slots' => count($requestedSlots)
    ];
    } catch (Exception $e) {
        error_log("Error in checkHallAvailability: " . $e->getMessage());
        return [
            'available' => false,
            'message' => 'Database error occurred. Please try again.',
            'error' => $e->getMessage()
        ];
    }
}

function getSessionSlots($session) {
    // Define session slots mapping
    $sessionSlots = [
        'fn' => ['1', '2', '3', '4'], // Forenoon
        'an' => ['5', '6', '7', '8'], // Afternoon
        'full' => ['1', '2', '3', '4', '5', '6', '7', '8'] // Full day
    ];
    
    return isset($sessionSlots[$session]) ? $sessionSlots[$session] : [];
}

if (isset($conn)) {
    $conn->close();
}
?>
