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
    $purpose = $_POST['purpose'];
    $event_type = $_POST['event_type'];
    $purpose_name = $_POST['purpose_name'];
    $students_count = isset($_POST['students_count']) && $_POST['students_count'] !== '' ? (int)$_POST['students_count'] : 0; //students_count is set to default to 0 if not provided
    $organiser_name = $_POST['organiser_name'];
    $organiser_id = $_POST['employee_id'];
    $organiser_department = $_POST['organiser_department'];
    $organiser_mobile = $_POST['organiser_mobile'];
    $organiser_email = $_POST['organiser_email'];
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
        // No conflict checking needed since slots and day_of_week are no longer editable
        // The existing booking's time slots and day of week will remain unchanged
            // Build dynamic update query based on provided fields
            $updateFields = [];
            $updateParams = [];
            $updateTypes = "";
            
            // Start date and end date are not updated in the database - they remain as semester dates
            // if (!empty($start_date)) {
            //     $updateFields[] = "start_date = ?";
            //     $updateParams[] = $start_date;
            //     $updateTypes .= "s";
            // }
            
            // if (!empty($end_date)) {
            //     $updateFields[] = "end_date = ?";
            //     $updateParams[] = $end_date;
            //     $updateTypes .= "s";
            // }
            
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
            
            if (!empty($event_type)) {
                $updateFields[] = "event_type = ?";
                $updateParams[] = $event_type;
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
                // Always redirect to the semester bookings list with success flag
                header("Location: view_modify_booking.php?semester=1&success=1");
                exit();
            } else {
                $error_message = "Error updating booking: " . $updateStmt->error;
            }

            $updateStmt->close();
        }
    }
}

// If there's an error, redirect back with error message
if (!empty($error_message)) {
    // Always redirect back to the semester bookings list with error flag
    header("Location: view_modify_booking.php?semester=1&error=" . urlencode($error_message));
    exit();
}

$conn->close();
?>