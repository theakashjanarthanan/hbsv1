<?php
include('assets/conn.php'); // Include database connection file

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve booking ID
    $booking_id = $_POST['booking_id'];
    
    // Validate booking ID
    if (empty($booking_id)) {
        $error_message = "Booking ID is required!";
    } else {
        // First, get the booking_id_gen for the current booking
        $getBookingGenQuery = "SELECT booking_id_gen FROM bookings WHERE booking_id = ?";
        $getBookingGenStmt = $conn->prepare($getBookingGenQuery);
        $getBookingGenStmt->bind_param("i", $booking_id);
        $getBookingGenStmt->execute();
        $bookingGenResult = $getBookingGenStmt->get_result();
        
        if ($bookingGenResult->num_rows > 0) {
            $booking_id_gen = $bookingGenResult->fetch_assoc()['booking_id_gen'];
            
            // Check if this is a semester booking (has day_of_week)
            $checkSemesterQuery = "SELECT day_of_week FROM bookings WHERE booking_id = ?";
            $checkSemesterStmt = $conn->prepare($checkSemesterQuery);
            $checkSemesterStmt->bind_param("i", $booking_id);
            $checkSemesterStmt->execute();
            $semesterResult = $checkSemesterStmt->get_result();
            
            if ($semesterResult->num_rows > 0) {
                $booking = $semesterResult->fetch_assoc();
                
                if (!empty($booking['day_of_week'])) {
                    // This is a semester booking - delete all related records with the same booking_id_gen
                    $deleteQuery = "DELETE FROM bookings WHERE booking_id_gen = ?";
                    $deleteStmt = $conn->prepare($deleteQuery);
                    $deleteStmt->bind_param("s", $booking_id_gen);
                    
                    if ($deleteStmt->execute()) {
                        $affected_rows = $deleteStmt->affected_rows;
                        $success_message = "Semester booking deleted successfully! ($affected_rows records deleted)";
                    } else {
                        $error_message = "Error deleting semester booking: " . $deleteStmt->error;
                    }
                    
                    $deleteStmt->close();
                } else {
                    $error_message = "This is not a semester booking!";
                }
            } else {
                $error_message = "Booking not found!";
            }
            
            $checkSemesterStmt->close();
        } else {
            $error_message = "Booking not found!";
        }
        
        $getBookingGenStmt->close();
    }
} else {
    $error_message = "Invalid request method!";
}

// Return response
if (!empty($error_message)) {
    echo $error_message;
} else {
    echo "success";
}

$conn->close();
?>
