<?php
include('assets/conn.php'); // Include database connection file

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $booking_id = $_POST['booking_id'];
    $hall_id = $_POST['hall_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $slot_or_session = implode(',', $_POST['slots']);
    $purpose = $_POST['purpose'];
    $purpose_name = $_POST['purpose_name'];
    $students_count = isset($_POST['students_count']) && $_POST['students_count'] !== '' ? (int)$_POST['students_count'] : 0; //students_count is set to default to 0 if not provided
    $organiser_name = $_POST['organiser_name'];
    $organiser_id = $_POST['employee_id'];
    $organiser_department = $_POST['organiser_department'];
    $organiser_mobile = $_POST['organiser_mobile'];
    $organiser_email = $_POST['organiser_email'];
    $day_of_week = $_POST['day_of_week'];
    $booking_date = date('Y-m-d'); // Current date for booking_date

    // First, get the booking_id_gen for the current booking
    $getBookingGenQuery = "SELECT booking_id_gen FROM bookings WHERE booking_id = ?";
    $getBookingGenStmt = $conn->prepare($getBookingGenQuery);
    $getBookingGenStmt->bind_param("i", $booking_id);
    $getBookingGenStmt->execute();
    $bookingGenResult = $getBookingGenStmt->get_result();
    
    if ($bookingGenResult->num_rows > 0) {
        $booking_id_gen = $bookingGenResult->fetch_assoc()['booking_id_gen'];
    } else {
        $error_message = "Booking not found!";
        header("Location: view_modify_booking.php?semester=1&error=" . urlencode($error_message));
        exit();
    }
    $getBookingGenStmt->close();

    // Validate only essential fields
    if (empty($booking_id) || empty($hall_id)) {
        $error_message = "Booking ID and Hall ID are required!";
    } else {
        // Only check for conflicts if time slots, day of week, or dates are being changed
        $conflictingSlots = [];
        if (!empty($slot_or_session) || !empty($day_of_week) || !empty($start_date) || !empty($end_date)) {
            // Use existing values for fields that aren't being updated
            $checkStartDate = !empty($start_date) ? $start_date : null;
            $checkEndDate = !empty($end_date) ? $end_date : null;
            $checkDayOfWeek = !empty($day_of_week) ? $day_of_week : null;
            $checkSlots = !empty($slot_or_session) ? $slot_or_session : null;
            
            // Get current booking details to use as fallback
            $currentBookingQuery = "SELECT start_date, end_date, day_of_week, slot_or_session FROM bookings WHERE booking_id = ?";
            $currentBookingStmt = $conn->prepare($currentBookingQuery);
            $currentBookingStmt->bind_param("i", $booking_id);
            $currentBookingStmt->execute();
            $currentBookingResult = $currentBookingStmt->get_result();
            
            if ($currentBookingResult->num_rows > 0) {
                $currentBooking = $currentBookingResult->fetch_assoc();
                
                // Use provided values or fall back to current values
                $checkStartDate = $checkStartDate ?: $currentBooking['start_date'];
                $checkEndDate = $checkEndDate ?: $currentBooking['end_date'];
                $checkDayOfWeek = $checkDayOfWeek ?: $currentBooking['day_of_week'];
                $checkSlots = $checkSlots ?: $currentBooking['slot_or_session'];
                
                $requestedSlots = explode(',', $checkSlots);
                $requestedSlots = array_map('trim', $requestedSlots);

                // Get all existing bookings for the hall on the same day of week within the date range
                // Exclude all bookings with the same booking_id_gen
                $checkQuery = "SELECT slot_or_session, booking_id FROM bookings 
                              WHERE hall_id = ? AND day_of_week = ? 
                              AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?) OR (start_date >= ? AND end_date <= ?))
                              AND status IN ('approved', 'pending') AND booking_id_gen != ?";
                
                $checkStmt = $conn->prepare($checkQuery);
                $checkStmt->bind_param("issssssss", $hall_id, $checkDayOfWeek, $checkStartDate, $checkStartDate, $checkEndDate, $checkEndDate, $checkStartDate, $checkEndDate, $booking_id_gen);
                $checkStmt->execute();
                $result = $checkStmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $existingSlots = explode(',', $row['slot_or_session']);
                    $existingSlots = array_map('trim', $existingSlots);
                    
                    // Check for any overlap between requested and existing slots
                    $overlap = array_intersect($requestedSlots, $existingSlots);
                    if (!empty($overlap)) {
                        $conflictingSlots = array_merge($conflictingSlots, $overlap);
                    }
                }

                $conflictingSlots = array_unique($conflictingSlots);
                $checkStmt->close();
            }
            $currentBookingStmt->close();
        }

        if (!empty($conflictingSlots)) {
            $error_message = "Hall is already booked for slots: " . implode(', ', $conflictingSlots) . " on " . ($checkDayOfWeek ?? $day_of_week);
        } else {
            // Build dynamic update query based on provided fields
            $updateFields = [];
            $updateParams = [];
            $updateTypes = "";
            
            // Only add fields to update if they are provided
            if (!empty($start_date)) {
                $updateFields[] = "start_date = ?";
                $updateParams[] = $start_date;
                $updateTypes .= "s";
            }
            
            if (!empty($end_date)) {
                $updateFields[] = "end_date = ?";
                $updateParams[] = $end_date;
                $updateTypes .= "s";
            }
            
            if (!empty($purpose)) {
                $updateFields[] = "purpose = ?";
                $updateParams[] = $purpose;
                $updateTypes .= "s";
            }
            
            if (!empty($purpose_name)) {
                $updateFields[] = "purpose_name = ?";
                $updateParams[] = $purpose_name;
                $updateTypes .= "s";
            }
            
            if (!empty($students_count)) {
                $updateFields[] = "students_count = ?";
                $updateParams[] = $students_count;
                $updateTypes .= "i";
            }
            
            if (!empty($organiser_id)) {
                $updateFields[] = "organiser_id = ?";
                $updateParams[] = $organiser_id;
                $updateTypes .= "i";
            }
            
            if (!empty($organiser_name)) {
                $updateFields[] = "organiser_name = ?";
                $updateParams[] = $organiser_name;
                $updateTypes .= "s";
            }
            
            if (!empty($organiser_department)) {
                $updateFields[] = "organiser_department = ?";
                $updateParams[] = $organiser_department;
                $updateTypes .= "s";
            }
            
            if (!empty($organiser_mobile)) {
                $updateFields[] = "organiser_mobile = ?";
                $updateParams[] = $organiser_mobile;
                $updateTypes .= "s";
            }
            
            if (!empty($organiser_email)) {
                $updateFields[] = "organiser_email = ?";
                $updateParams[] = $organiser_email;
                $updateTypes .= "s";
            }
            
            if (!empty($slot_or_session)) {
                $updateFields[] = "slot_or_session = ?";
                $updateParams[] = $slot_or_session;
                $updateTypes .= "s";
            }
            
            if (!empty($day_of_week)) {
                $updateFields[] = "day_of_week = ?";
                $updateParams[] = $day_of_week;
                $updateTypes .= "s";
            }
            
            // Always update booking_date
            $updateFields[] = "booking_date = ?";
            $updateParams[] = $booking_date;
            $updateTypes .= "s";
            
            // Add booking_id_gen for WHERE clause
            $updateParams[] = $booking_id_gen;
            $updateTypes .= "s";
            
            if (empty($updateFields)) {
                $error_message = "No fields to update!";
            } else {
                $updateQuery = "UPDATE bookings SET " . implode(", ", $updateFields) . " WHERE booking_id_gen = ?";
                
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param($updateTypes, ...$updateParams);

            if ($updateStmt->execute()) {
                $success_message = "Semester booking updated successfully!";
                // Redirect after successful update
                header("Location: view_modify_booking.php?semester=1&success=1");
                exit();
            } else {
                $error_message = "Error updating booking: " . $updateStmt->error;
            }

            $updateStmt->close();
        }
    }

        $checkStmt->close();
    }
}

// If there's an error, redirect back with error message
if (!empty($error_message)) {
    header("Location: view_modify_booking.php?semester=1&error=" . urlencode($error_message));
    exit();
}

$conn->close();
?>
