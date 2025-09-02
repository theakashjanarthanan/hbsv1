<?php
// Include database connection and header files
include('assets/conn.php'); // Include the Database Connectivity File
include 'assets/header.php'; // Include the Header File

// Fetch latest semester start and end date
$query = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latestSemester = mysqli_fetch_assoc($result);

$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));

// Retrieve parameters from GET or POST request with default empty values
$hall_id = $_GET['hall_id'] ?? '';
$school_name = $_GET['school_name'] ?? '';
$department_name = $_GET['department_name'] ?? '';
$hall_name = $_GET['hall_name'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$booking_type = trim($_GET['booking_type'] ?? '');
$session_choice = trim($_GET['session_choice'] ?? $_POST['session_choice'] ?? '');
// Slots may be passed via GET or POST and can be a comma-separated string or an array
$selectedSlots = $_GET['slots'] ?? $_POST['slots'] ?? [];

// If slots come as a comma-separated string, convert them into an array
if (is_string($selectedSlots)) {
    $selectedSlots = explode(',', $selectedSlots);
}

// Prepare SQL to fetch hall details along with related school and department names
$sql = "SELECT h.*, s.school_name, d.department_name
        FROM hall_details h
        LEFT JOIN schools s ON h.school_id = s.school_id
        LEFT JOIN departments d ON h.department_id = d.department_id
        WHERE h.hall_id = ?";
$stmt = $conn->prepare($sql);

// Handle SQL preparation errors
if (!$stmt) {
    echo '<p>Database error: ' . htmlspecialchars($conn->error) . '</p>';
    exit;
}

// Bind hall_id parameter and execute the query
$stmt->bind_param("i", $hall_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the hall data if found; else display an error and exit
if ($result->num_rows > 0) {
    $hall = $result->fetch_assoc();
} else {
    echo '<p>Hall not found.</p>';
    exit;
}

/**
 * Returns the number of days in a given month of a year
 * @param int $year - The year
 * @param int $month - The month (1-12)
 * @return int - Number of days in the month
 */
function getDaysInMonth($year, $month)
{
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}

/**
 * Retrieves all booked slots for a hall on a specific date
 * @param mysqli $conn - Database connection
 * @param int $hall_id - Hall ID
 * @param string $date - Date in 'YYYY-MM-DD' format
 * @return array - List of booked slots with organizer details
 */
function getBookedSlots($conn, $hall_id, $date)
{
    // SQL query to get bookings with status approved or pending on the given date
    $query = "SELECT slot_or_session, status, booking_id_gen, booking_date, students_count, organiser_name, organiser_email, organiser_department, organiser_mobile, purpose, event_type, purpose_name 
              FROM bookings 
              WHERE hall_id = ? 
              AND ? BETWEEN start_date AND end_date
              AND status IN ('approved', 'pending')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $hall_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookedSlots = [];
    // Loop through each booking and separate multiple slots if any
    while ($row = $result->fetch_assoc()) {
        $slots = explode(',', $row['slot_or_session']);
        foreach ($slots as $slot) {
            $organiserDetails = [
                'id_gen' => htmlspecialchars($row['booking_id_gen']),
                'date' => htmlspecialchars($row['booking_date']),
                'name' => htmlspecialchars($row['organiser_name']),
                'department' => htmlspecialchars($row['organiser_department']),
                'mobile' => htmlspecialchars($row['organiser_mobile']),
                'email' => htmlspecialchars($row['organiser_email']),
                'capacity' => htmlspecialchars($row['students_count']),
                'purpose' => htmlspecialchars($row['purpose']),
                'event_type' => htmlspecialchars($row['event_type']),
                'purpose_name' => htmlspecialchars($row['purpose_name'])
            ];
            $bookedSlots[] = [
                'slot' => intval($slot),
                'status' => $row['status'],
                'organiserDetails' => $organiserDetails
            ];
        }
    }
    return $bookedSlots;
}

// Initialize calendar data array
$calendar = [];

// Parse semester start and end dates
$semesterStartObj = new DateTime($semesterStart);
$semesterEndObj = new DateTime($semesterEnd);

// Get the month and year of semester start and end
$startMonth = (int)$semesterStartObj->format('n');
$startYear = (int)$semesterStartObj->format('Y');
$endMonth = (int)$semesterEndObj->format('n');
$endYear = (int)$semesterEndObj->format('Y');

// Calculate months to view based on semester range
$monthsToView = 0;
$currentMonth = $startMonth;
$currentYear = $startYear;

// Loop through the months within semester range to build calendar data
while (($currentYear < $endYear) || ($currentYear == $endYear && $currentMonth <= $endMonth)) {
    // Get number of days in the current month
    $daysInMonth = getDaysInMonth($currentYear, $currentMonth);

    // Initialize month calendar structure
    $monthCalendar = [
        'year' => $currentYear,
        'month' => $currentMonth,
        'days' => []
    ];

    // Loop through each day of the month and retrieve booked slots
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
        
        // Check if the date is within semester range
        $dateObj = new DateTime($date);
        $isWithinSemester = ($dateObj >= $semesterStartObj) && ($dateObj <= $semesterEndObj);
        
        $bookedSlots = getBookedSlots($conn, $hall_id, $date);
        $monthCalendar['days'][] = [
            'date' => $date,
            'bookedSlots' => $bookedSlots,
            'isWithinSemester' => $isWithinSemester
        ];
    }

    // Add the month calendar to the overall calendar array
    $calendar[] = $monthCalendar;
    $monthsToView++;

    // Move to next month
    $currentMonth++;
    if ($currentMonth > 12) {
        $currentMonth = 1;
        $currentYear++;
    }
}

// Encode the calendar data as pretty-printed JSON for use in frontend
$calendarJson = json_encode($calendar, JSON_PRETTY_PRINT);

// Store the current date and time for reference or logging
$currentDateTime = date('Y-m-d H:i:s');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Booking - Check Availability</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> <!-- Import Flatpickr CSS for date/time picker styling -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> <!-- Import Flatpickr JavaScript library for date/time picking functionality -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Import jQuery library (version 3.6.0) for DOM manipulation and event handling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Import Bootstrap -->
    <link rel="stylesheet" href="assets/design.css" /> <!-- Import Custom CSS File-->
    <style>
        h3,
        h4,
        h5 {
            font-family: "Lato", sans-serif;

        }

        .calendar-time-column {
            width: 80px;
            /* Set fixed width for the time column */
            text-align: left;
            padding: 2px;
            font-size: 0.8rem;
        }

        .calendar-cell {
            width: 20px;
            height: 20px;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            margin: 2px;
            font-size: 0.9rem;
        }

        .calendar-time-row {
            display: flex;
            flex-direction: row;
        }

        #calendar-container {
            justify-content: space-around !important;
            overflow-x: scroll;
        }

        /* Available cells (green) */
        .calendar-cell.available {
            background-color: rgb(85, 255, 122);
            /* Available */
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.available:hover {
            background-color: rgb(20, 255, 71);
            /* Hover color for available */
            border: 1px solid black;
            transform: scale(1.05);
            /* Slight scale effect */
            box-shadow: 0 2px 5px rgba(0, 180, 39, 0.6);
            /* Subtle shadow effect */
            color: white;
            /* Change text color on hover */
        }

        /* Pending cells (yellow) */
        .calendar-cell.pending {
            background-color: rgb(244, 255, 91);
            /* Pending */
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.pending:hover {
            background-color: rgb(244, 255, 91);
            /* Hover color for pending */
            border: 1px solid black;
            transform: scale(1.05);
            /* Slight scale effect */
            box-shadow: 0 2px 5px rgba(243, 255, 6, 0.6);
            /* Subtle shadow effect */
            color: black;
            /* Change text color on hover */
        }

        /* Approved cells (red) */
        .calendar-cell.approved {
            background-color: rgb(255, 103, 115);
            /* Booked */
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.approved:hover {
            background-color: rgb(255, 40, 58);
            /* Hover color for approved */
            border: 1px solid black;
            transform: scale(1.05);
            /* Slight scale effect */
            box-shadow: 0 2px 5px rgba(255, 40, 58, 0.6);
            /* Subtle shadow effect */
            color: white;
            /* Change text color on hover */
        }



        /* Past cells */
        .calendar-cell.past {
            background-color: rgb(162, 162, 162);
            /* Past */
            border: 1px solid black;
            cursor: not-allowed;
            /* Disable active state */
        }

        /* White cells */
        .white-cell {
            background-color: white;
            /* border: 1px solid #ddd; */
            cursor: none;

        }

        .calendar-cell.whitish-cell {
            background-color: rgba(255, 255, 200, 0.3);
            /* Light yellow overlay */
            border: 1px solid #f0e68c;
            /* Optional border */
        }

        /* Apply whitish-cell class to the statuses as well */

        .calendar-cell.approved.whitish-cell {
            background-color: rgba(255, 0, 0, 0.58);
            /* Approved with weekend overlay */
        }

        .calendar-cell.pending.whitish-cell {
            background-color: rgba(255, 251, 0, 0.48);
            /* Pending with weekend overlay */
        }

        .calendar-cell.available.whitish-cell {
            background-color:rgba(144, 238, 144, 0.64);
            /* Available with weekend overlay */
        }

        /* Past cells with light color */
        .calendar-cell.past.whitish-cell {
            background-color: rgba(162, 162, 162, 0.5);
            /* Past with weekend overlay */
            cursor: not-allowed;
        }

        .feature-icon {
            font-size: 1.2em;
            color: #007bff;
        }

        .day-name {
            font-size: 0.6em;
            position:relative;
            bottom:4px
        }

        .calendar-cell.weekend-cell {
            /* background-color:rgb(212, 212, 212);  */
            color: #333;
            /* Optional: Adjust text color for better contrast */
        }

        .calendar-cell.white-cell {
            background-color: #ffffff;
        }

        .weekend-cell {
            /* background-color: #f0f0f0;  */
            color: red;
            /* Change the text color for weekends */
        }

        .day-number.weekend-cell,
        .day-name.weekend-cell {
            color: red;
            /* Apply same color to both day and name */
        }


        /* Make the time column's width fixed */
        .time-slot-row {
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .time-slot-row:first-of-type {
            margin-bottom: 20px;
        }

        /* For calendar cells, use flex to ensure layout alignment */
        .time-slot-container {
            display: flex;
            flex-direction: column;
            border:2px solid black;
        }


        /* Status indicator for approved and pending */
        .status-indicator {
            display: none;
            /* Hide the status indicator */
        }

        /* Container for the month header */
        .month-header {
            display: flex;
            justify-content: space-between;
            /* Space between elements (buttons and month name) */
            align-items: center;
            /* Align items vertically */
            width: 100%;
            /* Make the header take up full width */
            max-width: 300px;
            /* Optional: You can set a max-width if you want a limit */
            margin: 0 auto;
            /* This centers the container horizontally */
        }

        /* Style for the buttons */
        .month-header button {
            font-size: 1.5rem;
            padding: 5px 10px;
            cursor: pointer;
            border: 0px;
            background-color: transparent;
        }

        /* Style for the disabled button */
        .month-header button:disabled {
            cursor: not-allowed;
        }

        .calendar-container {

            display: flex;
            justify-content: center;
            flex-direction: column;
            /* align-items: center; */
            overflow-y: scroll;
        }

        #calendar-container {

            display: flex;
            justify-content: center;
            align-items: center;
        }


        #organizer-details-heading {
            display: block;
            /* Keep heading visible */
        }

        .details-container {
            width: 100%;
            /* Set width to 80% */
            padding: 0;
            /* Remove any padding */
        }

        #organizer-details-table {
            width: 100%;
            /* Set width to 80% */
            padding: 0;
            /* Remove any padding */
        }

        #organizer-details-table th,
        #organizer-details-table td {
            margin: 0;
            /* Remove any margin */
            text-align: center;
            /* Center-align text */
            padding: 5px;
            font-size: 0.9rem;

            white-space: normal;
            /* Allow text to wrap */
            word-wrap: break-word;
            /* Break long words if necessary */
            overflow: visible;
            /* Ensure all content is shown */
        }

        #main {
            margin-left: 250px;
            transition: margin-left .5s;
            padding: 16px;
            padding-top: 50px;
        }

        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            /* Centers the legend items horizontally */
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 20px;
        }

        .legend-color {
            width: 15px;
            height: 15px;
            margin-right: 8px;
            border-radius: 3px;
            /* Optional: rounds the corners for a softer look */
        }

        .legend-item span {
            font-size: 14px;
            color: #333;
            /* Text color for the legend items */
        }

        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .warning-message {
            color: orange;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .activeAttachment {
            background-color: rgb(0, 123, 255) !important;
            box-shadow: 0 0 5px rgba(4, 170, 253, 0.5);
            transform: scale(1.05);
            /* Slight scale effect */
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }

        .modal {
            display: none;
            /* Hidden by default */
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            /* Background overlay */
        }

        .close-modal {
            color: #aaa;
            margin-right: 20px;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            /* Position relative to the modal-content */
            top: 10px;
            /* Adjust the distance from the top */
            right: 10px;
            /* Adjust the distance from the right */
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            position: relative;
            /* This is needed for absolute positioning of the close button */
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }

        .loading-content {
            text-align: center;
            background-color: rgba(0, 0, 0, 0.9);
            padding: 40px;
            border-radius: 15px;
            border: 2px solid #007bff;
            box-shadow: 0 0 30px rgba(0, 123, 255, 0.5);
        }

        .loading-content h5 {
            font-weight: 600;
            margin-bottom: 20px;
        }

        .progress {
            height: 25px;
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            border-radius: 15px;
            font-weight: 600;
            font-size: 14px;
            line-height: 25px;
        }

        .slot-checkbox:checked + label {
            background-color: #007bff !important;
            color: white !important;
            border-color: #007bff !important;
        }

        .slot-checkbox:checked + label::after {
            content: " ✓";
            font-weight: bold;
        }

        .slot-container {
            transition: all 0.3s ease;
        }

        .slot-label {
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            background-color: #f8f9fa;
            display: block;
            text-align: center;
            font-weight: 500;
        }

        .slot-label:hover {
            background-color: #e3f2fd;
            border-color: #2196f3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(33, 150, 243, 0.2);
        }

        .slot-checkbox:checked + .slot-label {
            background-color: #007bff !important;
            color: white !important;
            border-color: #007bff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
        }

        .slot-checkbox:checked + .slot-label::after {
            content: " ✓";
            font-weight: bold;
            margin-left: 5px;
        }

        .slot-checkbox {
            display: none;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
        }

        .slot-selection-feedback {
            position: fixed;
            margin-top:80px;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .slot-selection-feedback.show {
            transform: translateX(0);
        }

        .availability-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
            background: rgba(0, 123, 255, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(0, 123, 255, 0.2);
        }

        .time-slot-container {
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .time-slot-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }

        .time-slot-container:hover::before {
            left: 100%;
        }

        .time-slot-container:hover {
            border: 2px solid red;
        }

        .time-slot-row {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
            /* transition: all 0.3s ease; */
        }

        .time-slot-row:hover {
            /* background: rgba(0, 123, 255, 0.05); */
            /* border-radius: 6px;
            padding: 2px 4px; */
        }

        .calendar-time-column {
            width: 80px;
            text-align: left;
            padding: 8px 4px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #495057;
            /* background: rgba(255, 255, 255, 0.7); */
            border-radius: 4px;
            margin-right: 8px;
            transition: all 0.3s ease;
            
        }

        .calendar-time-column:hover {
            background: rgba(255, 255, 255, 0.7);
        }

        .calendar-cell {
            width: 24px;
            height: 24px;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            margin: 2px;
            font-size: 0.8rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .calendar-cell::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }

        .calendar-cell:hover::before {
            width: 100%;
            height: 100%;
        }

        .calendar-cell.available {
            background:rgb(85, 255, 122);
            border: 1px solid #28a745;
            color: white;
            font-weight: 600;
            border: 2px solid black;
        }

        .calendar-cell.available:hover {
            background: linear-gradient(135deg, #20c997, #17a2b8);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .calendar-cell.pending {
            background: rgb(244, 255, 91);
            border: 1px solid #ffc107;
            color: #212529;
            font-weight: 600;
            border:2px solid black;
        }

        .calendar-cell.pending:hover {
            background: linear-gradient(135deg, #fd7e14, #e83e8c);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }

        .calendar-cell.approved {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: 1px solid #dc3545;
            color: white;
            font-weight: 600;
        }

        .calendar-cell.approved:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .calendar-cell.past {
            background: rgb(162, 162, 162);
            border: 1px solid #6c757d;
            color: #adb5bd;
            cursor: not-allowed;
            border: 2px solid black;
        }

        .calendar-cell.past:hover {
            transform: none;
            box-shadow: none;
        }
        
        /* Semester info styling */
        .semester-info {
            background: rgba(0, 123, 255, 0.1);
            border: 1px solid rgba(0, 123, 255, 0.2);
            border-radius: 8px;
            padding: 8px 12px;
        }
        
        .semester-info small {
            font-weight: 500;
            color: #495057;
        }
        
        /* Semester banner styling */
        .semester-banner .alert {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 2px solid #2196f3;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
        }
        
        .semester-banner .alert strong {
            color: #1565c0;
        }
        
        .semester-banner .alert small {
            color: #1976d2;
            font-weight: 500;
        }

        .calendar-cell.activeAttachment {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
            border: 2px solid #fff !important;
            box-shadow: 0 0 15px rgba(0, 123, 255, 0.6) !important;
            transform: scale(1.15) !important;
            z-index: 10;
            animation: slotSelected 0.3s ease-in-out;
        }

        .calendar-cell.activeAttachment::after {
            content: '✓';
            position: absolute;
            top: -2px;
            right: -2px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .booking-form-loading {
            position: relative;
            min-height: 200px;
        }

        .booking-form-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
        }

        .booking-form-loading::before {
            content: 'Loading booking form...';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 6;
            color: #007bff;
            font-weight: 600;
        }

        @keyframes slotSelected {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1.15); }
        }

        @keyframes multipleSlotSelected {
            0% { transform: scale(1); }
            25% { transform: scale(1.1); }
            50% { transform: scale(1.05); }
            75% { transform: scale(1.1); }
            100% { transform: scale(1.15); }
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slotDeselected {
            0% { transform: scale(1); }
            50% { transform: scale(0.95); }
            100% { transform: scale(1); }
        }

        .booking-form-container {
            transition: all 0.5s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .booking-form-container.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Time-slot-container loading overlay */
        .time-slot-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }

        .time-slot-loading-content {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 2px solid #007bff;
        }

        .time-slot-loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        .time-slot-loading-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .time-slot-loading-progress {
            width: 100%;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .time-slot-loading-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 4px;
            position: relative;
        }

        .time-slot-loading-progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 1.5s infinite;
        }

        .time-slot-loading-status {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Enhanced form styling */
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            transition: all 0.3s ease;
        }

        .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        /* Performance optimizations */
        .calendar-cell {
            will-change: transform, box-shadow;
        }

        .slot-label {
            will-change: transform, background-color;
        }

        /* Loading states */
        .loading-state {
            position: relative;
            pointer-events: none;
        }

        .loading-state::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        #multiple-slot-feedback{
            
        }

        /* Conflict warning styling */
        .conflict-warning {
            border-left: 4px solid #ffc107;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .conflict-warning .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }

        .conflict-warning .alert-warning strong {
            color: #856404;
        }

        .conflict-warning .alert-warning em {
            color: #6c5ce7;
            font-style: italic;
        }

        /* Enhanced conflict display */
        .conflict-slot {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 8px;
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .conflict-slot .slot-number {
            font-weight: bold;
            color: #856404;
        }

        .conflict-slot .organizer-info {
            color: #6c5ce7;
        }

    </style>
</head>

<body>

    <div id="main">
        <div class="row justify-content-center">
            <div class="col-md-9 mt-5">
                <div class="col-md-12">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <center>
                                <h3 style="color:#0e00a3">Hall Booking</h3><br>
                            </center>
                            <form id="userDetailsForm" method="POST" action="confirm_book.php"
                                onsubmit="return validateForm()" enctype="multipart/form-data">
                                <input type="hidden" name="user_id"
                                    value="<?php echo isset($user_id) ? $user_id : ''; ?>">
                                <div class="mb-4">
                                    <div class="form-group mb-3">
                                        <label class="form-label" style="font-weight:normal;">Hall Type</label><br>
                                        <div class="btn-group" role="group" aria-label="Hall Type">
                                            <input type="radio" class="btn-check" name="type_id" id="seminar" value="1"
                                                <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '1') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="seminar">Seminar Hall</label>

                                            <input type="radio" class="btn-check" name="type_id" id="auditorium"
                                                value="2" <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '2') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="auditorium">Auditorium</label>

                                            <input type="radio" class="btn-check" name="type_id" id="lecture" value="3"
                                                <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '3') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="lecture">Lecture Hall</label>

                                            <input type="radio" class="btn-check" name="type_id" id="conference"
                                                value="4" <?php echo (isset($_GET['type_id']) && $_GET['type_id'] == '4') ? 'checked' : ''; ?> disabled>
                                            <label class="btn btn-outline-primary" for="conference">Conference
                                                Hall</label>
                                        </div>
                                    </div>
                                    <input type="hidden" name="hall_id" value="<?php echo $hall_id; ?>">

                                    <div class="form-group">
                                        <label for="school_name">School</label>
                                        <input type="text" name="school_name" id="school_name" class="form-control"
                                            value="<?php echo $school_name; ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label for="department_name">Department</label>
                                        <input type="text" name="department_name" id="department_name"
                                            class="form-control" value="<?php echo $department_name; ?>" readonly>
                                    </div>

                                    <div class="form-group">
                                        <label for="hall">Hall Name</label>
                                        <input type="text" name="hall" id="hall" class="form-control"
                                            value="<?php echo $hall_name; ?>" readonly>
                                    </div>
                                </div>
                                <div class="details-container">
                                    <div class="calendar-container" id="calendar">
                                        
                                        <!-- Semester Range Banner -->
                                        <div class="semester-banner mb-3">
                                            <div class="alert alert-info text-center" role="alert">
                                                <i class="bi bi-calendar-event me-2"></i>
                                                <strong>Semester Period:</strong> 
                                                <?= date('M d, Y', strtotime($semesterStart)) ?> - <?= date('M d, Y', strtotime($semesterEnd)) ?>
                                                <br>
                                                <small class="text-muted">Only dates within this period can be booked</small>
                                            </div>
                                        </div>

                                        <center>
                                            <div id="calendar-container"></div>
                                        </center>

                                        <div id="organizer-details-container">
                                            <span style="display: none;" id="organizer-details-heading"><b>Booking
                                                    Details: </b></span>
                                            <br>
                                            <table id="organizer-details-table" border="1"
                                                style="display: none; margin: 0 auto 20px auto; order:2"
                                                class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th style="width:30%">Organiser Details</th>
                                                        <th style="width:40%">Purpose</th>
                                                        <th style="width:10%">Participants</th>
                                                        <th style="width:20%">Booked on</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                        <!-- Date Picker -->
                                        <div id="booking" style="display:block;">
                                                <!-- Semester Info Message -->
                                                <!-- <div class="alert alert-info text-center mb-3" role="alert">
                                                    Classes can be booked between <strong><?= htmlspecialchars($semesterStart) ?></strong> and <strong><?= htmlspecialchars($semesterEnd) ?></strong>.
                                                </div> -->
                                            <div class="mb-2">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="start_date" class="form-label">From:</label>
                                                        <input
                                                            type="date"
                                                            id="start_date"
                                                            name="start_date"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($semesterStart) ?>"
                                                            min="<?= htmlspecialchars($semesterStart) ?>"
                                                            max="<?= htmlspecialchars($semesterEnd) ?>"
                                                            onchange="handleDateChange()"
                                                            onkeydown="return false"
                                                            required
                                                        />

                                                        <div id="start_date_error" class="error-message"></div>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="end_date" class="form-label">To:</label>
                                                        <input
                                                            type="date"
                                                            id="end_date"
                                                            name="end_date"
                                                            class="form-control"
                                                            value="<?= htmlspecialchars($semesterEnd) ?>"
                                                            min="<?= htmlspecialchars($semesterEnd) ?>"
                                                            max="<?= htmlspecialchars($semesterEnd) ?>"
                                                            onchange="handleDateChange()"
                                                            onkeydown="return false"
                                                            required
                                                        />
                                                        <div id="end_date_error" class="error-message"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Slot Options -->
                                        <div class="form-group mb-3" id="slot_options">
                                            <label class="form-label">Choose Slot(s):</label>
                                            <div class="btn-group w-100" role="group">
                                                <input class="btn-check session-checkbox" type="checkbox" id="fn"
                                                    value="fn" autocomplete="off" onchange="updateSlotsBasedOnSession()">
                                                <label class="btn btn-outline-primary" for="fn">Forenoon</label>
                                                <input class="btn-check session-checkbox" type="checkbox" id="an"
                                                    value="an" autocomplete="off" onchange="updateSlotsBasedOnSession()">
                                                <label class="btn btn-outline-primary" for="an">Afternoon</label>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <!-- <div class="alert alert-info" role="alert">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <strong>Multiple Slot Selection:</strong> You can select multiple time slots by clicking on different slots in the calendar or using the checkboxes below. All selected slots will be booked together.
                                                </div> -->

                                                <div class="mt-2">

                                                 </div>
                                            
                                            </div>
                                            <?php
                                            $slots = [
                                                1 => '09:30am',
                                                2 => '10:30am',
                                                3 => '11:30am',
                                                4 => '12:30pm',
                                                5 => '01:30pm',
                                                6 => '02:30pm',
                                                7 => '03:30pm',
                                                8 => '04:30pm'
                                            ];
                                            foreach ($slots as $slotId => $slotLabel) {
                                                echo "
                                                <div class='col-md-3 mb-2'>
                                                    <div class='form-check slot-container'>
                                                        <input class='form-check-input slot-checkbox' type='checkbox' name='slots[]' id='slot{$slotId}' value='{$slotId}'>
                                                        <label class='form-check-label slot-label' for='slot{$slotId}'>{$slotLabel}</label>
                                                    </div>
                                                </div>";
                                            }
                                            ?>
                                        </div>
                                        
                                        <div class="row mt-2">
                                            <div class="col-12 text-center">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSlotSelection()">
                                                    <i class="bi bi-x-circle me-1"></i>Clear Selection
                                                </button>
                                            </div>
                                        </div>

                                        <div id="slot_warning" class="warning-message"></div>
                                        <center>
                                            <div id="availability_message"></div>
                                        </center>
                                        
                                        <!-- Conflict Information Section -->
                                        <div id="conflict_info" class="alert alert-info" style="display: none;">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>About Booking Conflicts:</strong><br>
                                            <small>
                                                • You can book slots that are currently for the approval of the respective HOD<br>
                                                • This creates a booking conflict that concern HOD will resolve<br>
                                                <!-- • Conflicts are automatically detected and grouped for easy management<br> -->
                                                <!-- • The first approved booking will automatically reject conflicting pending requests -->
                                            </small>
                                        </div>
                                        
                                             <span id="booking2" style="display:none;" class="booking-form-container">

                                            <div class="mb-3">
                                                <label class="form-label">Purpose of Booking</label>
                                                <div class="btn-group w-100" role="group"
                                                    aria-label="Purpose of Booking">
                                                    <input type="radio" class="btn-check" id="purpose_event"
                                                        name="purpose" value="event" required>
                                                    <label class="btn btn-outline-primary"
                                                        for="purpose_event">Event</label>
                                                    <input type="radio" class="btn-check" id="purpose_class"
                                                        name="purpose" value="class" required>
                                                    <label class="btn btn-outline-primary"
                                                        for="purpose_class">Class</label>
                                                </div>
                                            </div>

                                            <div class="mb-3" id="event-type-group" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="event_type" class="form-label">Event Type</label>
                                                        <select class="form-select" id="event_type" name="event_type">
                                                            <option value="guest_lectures_seminars">Guest Lectures,
                                                                Seminars</option>
                                                            <option value="meetings_ceremonies">Meetings, Ceremonies
                                                            </option>
                                                            <option value="workshops_training">Workshops, Training
                                                            </option>
                                                            <option value="conferences_symposiums">Conferences,
                                                                Symposiums</option>
                                                            <option value="examinations_admissions_interviews">
                                                                Examinations, Admissions, Interviews</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="event_invitation" class="form-label">Upload
                                                            Invitation</label>
                                                        <input class="form-control" type="file" id="event_invitation"
                                                            name="event_invitation" accept=".jpeg, .jpg, .png, .pdf"
                                                            onchange="validateFileSize()" />
                                                        <small id="file-size-message">Supported formats: JPEG, JPG, PNG,
                                                            PDF. Max size: 1MB.</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="purpose_name" class="form-label"
                                                    id="label-purpose-name">Name of the Event</label>

                                                <textarea class="form-control" id="purpose_name" name="purpose_name"
                                                    rows="3" required></textarea>
                                            </div>


                                            <div class="mb-3">
                                                <label for="students_count" class="form-label"
                                                    id="label-students-count">
                                                    Number of Participants Expected 
                                                </label>
                                                (Maximum - <?php echo $hall['capacity']; ?>)
                                                <input type="number" class="form-control" id="students_count" min="5"
                                                    name="students_count" onchange="checkCapacity()" required>
                                                <div id="alert" style="display: none; color: red;" role="alert">
                                                    The number of participants exceeds the hall capacity -
                                                    <?php echo $hall['capacity']; ?>.
                                                </div>
                                            </div>

                                            <script>

                                                window.addEventListener('DOMContentLoaded', () => {
                                                    const startInput = document.getElementById('start_date');
                                                    const endInput = document.getElementById('end_date');

                                                    const semesterStart = '<?= $semesterStart ?>';
                                                    const semesterEnd = '<?= $semesterEnd ?>';

                                                    // Set min and max again (just in case)
                                                    startInput.min = semesterStart;
                                                    startInput.max = semesterEnd;
                                                    endInput.min = semesterStart;
                                                    endInput.max = semesterEnd;

                                                    // Optional: Disable manual typing (if you want)
                                                    startInput.addEventListener('keydown', e => e.preventDefault());
                                                    endInput.addEventListener('keydown', e => e.preventDefault());

                                                    // Add validation on date change to reset invalid dates
                                                    startInput.addEventListener('change', () => {
                                                        if (startInput.value < semesterStart) startInput.value = semesterStart;
                                                        if (startInput.value > semesterEnd) startInput.value = semesterEnd;

                                                        // Also update end date min to start date
                                                        if (endInput.value < startInput.value) endInput.value = startInput.value;
                                                        endInput.min = startInput.value;
                                                    });

                                                    endInput.addEventListener('change', () => {
                                                        if (endInput.value > semesterEnd) endInput.value = semesterEnd;
                                                        if (endInput.value < semesterStart) endInput.value = semesterStart;

                                                        // Also update start date max to end date
                                                        if (startInput.value > endInput.value) startInput.value = endInput.value;
                                                        startInput.max = endInput.value;
                                                    });
                                                });

                                            </script>

                                            <script>
                                                document.addEventListener("DOMContentLoaded", function () {
                                                    const purposeRadios = document.querySelectorAll('input[name="purpose"]');
                                                    const eventTypeGroup = document.getElementById("event-type-group");
                                                    const labelPurposeName = document.getElementById("label-purpose-name");
                                                    const labelStudentsCount = document.getElementById("label-students-count");

                                                    purposeRadios.forEach(radio => {
                                                        radio.addEventListener("change", function () {
                                                            if (this.value === "event") {
                                                                eventTypeGroup.style.display = "block";
                                                                labelPurposeName.textContent = "Name of the Event";
                                                                labelStudentsCount.innerHTML = `Number of Participants Expected`;
                                                            } else if (this.value === "class") {
                                                                eventTypeGroup.style.display = "none";
                                                                labelPurposeName.textContent = "Name of the Course and Year";
                                                                labelStudentsCount.textContent = "Number of Students Attending";
                                                            }
                                                        });
                                                    });
                                                });
                                            </script>

                                            <?php
                                            // Assuming $user_id is set and you have a database connection ($conn)
                                            $user_id = isset($user_id) ? $user_id : '';
                                            // Step 1: Retrieve the department_id from the users table
                                            $sql = "SELECT department_id FROM users WHERE user_id = ?";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->bind_param("i", $user_id);
                                            $stmt->execute();
                                            $stmt->bind_result($department_id);
                                            $stmt->fetch();
                                            $stmt->close();

                                            // Step 2: Retrieve the department_name from the departments table using department_id
                                            $department_name = '';
                                            if ($department_id) {
                                                $sql = "SELECT department_name FROM departments WHERE department_id = ?";
                                                $stmt = $conn->prepare($sql);
                                                $stmt->bind_param("i", $department_id);
                                                $stmt->execute();
                                                $stmt->bind_result($department_name);
                                                $stmt->fetch();
                                                $stmt->close();
                                            }


                                            if (isset($_SESSION['user_id'])) {
                                                $user_id = $_SESSION['user_id'];  // Retrieve the user_id from the session
                                                $user_role = $_SESSION['role'];
                                                $username = $_SESSION['username'];

                                                // Fetch the school_id for the user from the 'users' table
                                                $school_query = "SELECT department_id FROM users WHERE user_id = ?";
                                                $stmt = $conn->prepare($school_query);

                                                if ($stmt === false) {
                                                    die('Prepare failed: ' . htmlspecialchars($conn->error));
                                                }

                                                $stmt->bind_param("i", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                $user_data = $result->fetch_assoc();

                                                if ($user_data) {
                                                    $user_dept_id = $user_data['department_id'];
                                                } else {
                                                    die('User not found');
                                                }
                                                // Initialize variables
                                                $employee_id = null;
                                                $organizer_name = $username;
                                                $organizer_email = '';
                                                $organizer_phone = '';
                                                
                                                if (isset($user_role) && ($user_role === 'prof' || $user_role === 'hod' || $user_role === 'dean')) {
                                                    $organizer_query = "SELECT o.*, s.* 
                                                        FROM employee o
                                                        JOIN departments s ON o.department_id = s.department_id
                                                        WHERE o.department_id = ? AND o.employee_name = ?";

                                                    $stmt = $conn->prepare($organizer_query);

                                                    if ($stmt === false) {
                                                        die('Prepare failed: ' . htmlspecialchars($conn->error));
                                                    }

                                                    $stmt->bind_param("is", $user_dept_id, $username);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    $organizer = $result->fetch_assoc();

                                                    if ($organizer) {
                                                        $employee_id = $organizer['employee_id'];
                                                        $organizer_name = $organizer['employee_name'];
                                                        $organizer_email = $organizer['employee_email'];
                                                        $organizer_phone = $organizer['employee_mobile'];
                                                    }
                                                }
                                                
                                                // Fallback: try to get employee_id from session if not set
                                                if (!$employee_id) {
                                                    $employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : null;
                                                }
                                                
                                                // For admin users, we might need to handle differently
                                                if (!$employee_id && $user_role === 'admin') {
                                                    // For admin, we can use a default or create a temporary ID
                                                    $employee_id = 1; // Default admin employee ID
                                                }
                                                
                                                // If still no employee_id, show error
                                                if (!$employee_id) {
                                                    echo '<div class="alert alert-danger">Error: Could not determine employee ID. Please contact administrator.</div>';
                                                }
                                            }
                                            ?>
                                            <div class="mb-3">
                                                <!-- <label for="organiser_department" class="form-label">Organiser's Department</label> -->
                                                <input type="hidden" class="form-control" id="user_role"
                                                    name="user_role" value="<?php echo htmlspecialchars($user_role); ?>"
                                                    readonly required>
                                            </div>

                                            <div class="mb-3">
                                                <!-- <label for="organiser_department" class="form-label">Organiser's Department</label> -->
                                                <input type="hidden" class="form-control" id="organiser_department"
                                                    name="organiser_department"
                                                    value="<?php echo htmlspecialchars($department_name); ?>" readonly
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <!-- <label for="organiser_name" class="form-label">Organiser's Name</label> -->
                                                <input type="hidden" class="form-control" id="organiser_name"
                                                    name="organiser_name" required
                                                    value="<?php echo htmlspecialchars($organizer_name); ?>" readonly>
                                            </div>
                                            <input type="hidden" id="employee_id" name="employee_id"
                                                value="<?php echo htmlspecialchars($employee_id ?? '1'); ?>">
                                            <!-- Debug info (remove in production) -->
                                            <script>
                                                console.log('Employee ID set to:', '<?php echo htmlspecialchars($employee_id ?? "NULL"); ?>');
                                            </script>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <!-- <label for="organiser_mobile" class="form-label">Organiser's Contact Number</label> -->
                                                    <input type="hidden" class="form-control" id="organiser_mobile"
                                                        name="organiser_mobile" required
                                                        value="<?php echo htmlspecialchars($organizer_phone); ?>"
                                                        readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <!-- <label for="organiser_email" class="form-label">Organiser's Email ID</label> -->
                                                    <input type="hidden" class="form-control" id="organiser_email"
                                                        name="organiser_email" required
                                                        value="<?php echo htmlspecialchars($organizer_email); ?>"
                                                        readonly>
                                                </div>
                                            </div>
                                            <div id="duplicate_booking_message" class="alert alert-danger"
                                                style="display: none;">
                                                Duplicate booking detected! Please modify your selection.
                                            </div>
                                            <input type="hidden" id="slot_or_session" name="slot_or_session" value="">
                                            <div id="booking-status" class="mt-2"></div>
                                            <!-- Booking status message will be displayed here -->

                                            <div class="text-center">
                                                <a href="javascript:history.back()" style="padding:7px 30px;"
                                                    class="btn btn-primary fs-5">Back</a>
                                                <button type="submit" id="submit_button" class="btn btn-success btn-lg"
                                                    style="display:none;" disabled>Book Now</button>
                                            </div>
                            </form>


                        </div>
                    </div>
                </div>
                </span>
            </div>
        </div>
    </div>
    </div>
    </div>
    <?php include 'assets/footer.php' ?>
    <!-- Add a hidden pop-up modal -->

    <div id="cancelBookingModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" id="close_modal">&times;</span>
            <center>
                <h3 style="color: #007bff; margin-bottom:15px;">Cancel Booking</h3>
            </center>
            <h4 class="form-section-title"></h4>

            <form onsubmit="return handleCancelBooking(event);">
                <input type="hidden" name="cancel_booking_id" id="cancel_booking_id"
                    value="<?php echo $booking['booking_id']; ?>"> <!-- Stores selected hall ID -->

                <div class="form-group mt-3">
                    <select name="reason" id="cancel_reason" required onchange="toggleOtherReason()">
                        <option value="" disabled selected>Select a reason</option>
                        <option value="Change of plans">Change of plans</option>
                        <option value="Scheduling conflict">Scheduling conflict</option>
                        <option value="Requested to Change">Requested to Change</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <textarea name="other_reason" id="other_reason" placeholder="Please specify..."
                        style="display:none; width: 100%; height: 50px; resize: vertical;"></textarea>
                </div>
                <center><button class="btn btn-primary mt-4" type="button" id="confirm_cancel"
                        type="submit">Submit</button></center>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Event delegation for dynamically added cancel button
            document.body.addEventListener("click", function (event) {
                if (event.target.id === "cancel_booking_btn") {
                    let bookingId = event.target.getAttribute("data-booking-id");
                    document.getElementById("cancel_booking_id").value = bookingId;
                    document.getElementById("cancelBookingModal").style.display = "block";
                }
            });

            document.getElementById("confirm_cancel").addEventListener("click", function (event) {
                event.preventDefault(); // Prevents form submission
                let bookingId = document.getElementById("cancel_booking_id").value;
                let reason = document.getElementById("cancel_reason").value;

                if (!reason.trim()) {
                    alert("Please enter a reason for cancellation.");
                    return;
                }

                fetch("cancel_duplicate.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `booking_id=${bookingId}&reason=${encodeURIComponent(reason)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        // alert(data.message);
                        document.getElementById("cancelBookingModal").style.display = "none";
                        checkAvailability();
                    })
                    .catch(error => console.error("Error:", error));
            });


            // Close modal
            document.getElementById("close_modal").addEventListener("click", function () {
                document.getElementById("cancelBookingModal").style.display = "none";
            });

        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.addEventListener("change", function () {
                window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
            });
        });
    </script>


    <script>

        function validateFileSize() {
            const fileInput = document.getElementById('event_invitation');
            const file = fileInput.files[0];
            const message = document.getElementById('file-size-message');

            if (file) {
                const maxSize = 1048576; // 1MB in bytes (1048576 bytes)
                if (file.size > maxSize) {
                    message.style.color = 'red';  // Change text color to red
                    message.textContent = 'File size exceeds 1MB. Please upload a smaller file.';
                    fileInput.value = ''; // Reset the file input
                } else {
                    message.style.color = '';  // Reset text color
                    message.textContent = 'Supported formats: JPEG, JPG, PNG, PDF. Max size: 1MB.';
                }
            }
        }
        // Function to check if all form fields are filled
        function checkFormFields() {
            const organiserDepartment = document.getElementById('organiser_department').value.trim();
            const organiserName = document.getElementById('organiser_name').value.trim();
            const organiserMobile = document.getElementById('organiser_mobile').value.trim();
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const selectedSlots = document.querySelectorAll('.slot-checkbox:checked').length;
            const capacity = document.getElementById('students_count').value.trim();

            // Check if purpose radio buttons are selected
            const purposeEvent = document.getElementById('purpose_event').checked;
            const purposeClass = document.getElementById('purpose_class').checked;

            // Return true if all required fields are filled
            return organiserDepartment && organiserName && organiserMobile && startDate && endDate && selectedSlots > 0 && capacity && (purposeEvent || purposeClass);
        }

        // Function to handle duplicate booking response
        function handleDuplicateBooking(xhr) {
            try {
                const response = JSON.parse(xhr.responseText);
                const duplicateMessage = document.getElementById('duplicate_booking_message');
                const submitBtn = document.getElementById('submit_button');

                if (!submitBtn) {
                    console.error('Submit button not found!');
                    return;
                }

                if (response.isDuplicate) {
                    duplicateMessage.style.display = 'block';
                    duplicateMessage.textContent = 'Duplicate booking detected! Please modify your selection.';
                    submitBtn.style.display = 'none';
                    submitBtn.disabled = true;
                } else {
                    duplicateMessage.style.display = 'none';
                    updateSubmitButtonState(); // Ensure button is updated after duplicate check
                }
            } catch (error) {
                console.error('Error processing response:', error);
            }
        }

        // Function to update the state of the submit button
        function updateSubmitButtonState() {
            const submitBtn = document.getElementById('submit_button');
            const duplicateMessage = document.getElementById('duplicate_booking_message');

            if (checkFormFields()) {
                submitBtn.style.display = 'inline-block';
                submitBtn.disabled = false;
                duplicateMessage.style.display = 'none';
            } else {
                submitBtn.style.display = 'none';
                submitBtn.disabled = true;
                duplicateMessage.style.display = 'block';
                duplicateMessage.textContent = 'Please fill out all required fields.';
            }
        }

        // Event listeners to check form fields on changes
        document.getElementById('start_date').addEventListener('change', updateSubmitButtonState);
        document.getElementById('end_date').addEventListener('change', updateSubmitButtonState);
        document.querySelectorAll('.slot-checkbox').forEach(function (slot) {
            slot.addEventListener('change', updateSubmitButtonState);
        });
        document.getElementById('purpose_event').addEventListener('change', updateSubmitButtonState);
        document.getElementById('purpose_class').addEventListener('change', updateSubmitButtonState);
        document.getElementById('students_count').addEventListener('input', updateSubmitButtonState);
        document.getElementById('organiser_department').addEventListener('input', updateSubmitButtonState);
        document.getElementById('organiser_name').addEventListener('input', updateSubmitButtonState);
        document.getElementById('organiser_mobile').addEventListener('input', updateSubmitButtonState);

        document.addEventListener('DOMContentLoaded', () => {
            const organiserInput = document.getElementById('organiser_name');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const slotCheckboxes = document.querySelectorAll('.slot-checkbox');

            organiserInput.addEventListener('input', triggerDuplicateCheck);
            startDateInput.addEventListener('change', triggerDuplicateCheck);
            endDateInput.addEventListener('change', triggerDuplicateCheck);
            slotCheckboxes.forEach(checkbox => checkbox.addEventListener('change', triggerDuplicateCheck));
        });

        function triggerDuplicateCheck() {
            const organiserName = document.getElementById('organiser_name').value;
            const organiserId = document.getElementById('employee_id').value;
            const organiserEmail = document.getElementById('organiser_email').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const selectedSlots = Array.from(document.querySelectorAll('.slot-checkbox:checked')).map(slot => slot.value);

            if (organiserName && organiserId && startDate && endDate && selectedSlots.length > 0) {
                checkDuplicateBooking(organiserName, organiserId, startDate, endDate, selectedSlots);
            }
        }

        function checkDuplicateBooking(organiserName, organiserId, startDate, endDate, selectedSlots) {

            const duplicateMessage = document.getElementById('duplicate_booking_message');
            const submitBtn = document.getElementById('submit_button');

            if (!submitBtn) {
                console.error('Submit button not found!');
                return;
            }

            duplicateMessage.style.display = 'none';
            updateSubmitButtonState(); // Ensure button is updated after duplicate check

        }
    </script>
    <script>
        function validateForm() {
            const studentsCount = parseInt(document.getElementById("students_count").value, 10);
            if (studentsCount > hallCapacity) {
                // Show the alert
                document.getElementById("alert").style.display = "block";
                return false;

            } else {
                // Hide the alert if the input is valid
                document.getElementById("alert").style.display = "none";
            }
            return true;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const slotCheckboxes = document.querySelectorAll('.slot-checkbox');
            const sessionCheckboxes = document.querySelectorAll('.session-checkbox');

            // Function to update session checkboxes based on selected slots
            function updateSessionCheckboxes() {
                const selectedSlots = Array.from(slotCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => parseInt(checkbox.value));

                const forenoonSelected = selectedSlots.every(slot => slot >= 1 && slot <= 4) && selectedSlots.length === 4;
                const afternoonSelected = selectedSlots.every(slot => slot >= 5 && slot <= 8) && selectedSlots.length === 4;

                // Update 'Forenoon' checkbox
                document.querySelector('#fn').checked = forenoonSelected;

                // Update 'Afternoon' checkbox
                document.querySelector('#an').checked = afternoonSelected;

                // If all slots are selected, both 'fn' and 'an' should be checked
                if (selectedSlots.length === 8) {
                    document.querySelector('#fn').checked = true;
                    document.querySelector('#an').checked = true;
                }
                
                // Update calendar visualization
                const selectedSlotStrings = selectedSlots.map(slot => slot.toString());
                updateCalendarForSelectedSlots(selectedSlotStrings);
                
                checkAvailability();
            }



            // Add event listeners to slot checkboxes
            slotCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSessionCheckboxes);
            });


            checkAvailability();
            triggerDuplicateCheck();
        });
    </script>

    <script>
        const hallCapacity = <?php echo $hall['capacity']; ?>;

        function checkCapacity() {
            const studentsCount = parseInt(document.getElementById("students_count").value, 10);

            // Check if students count exceeds the capacity
            if (studentsCount > hallCapacity) {
                // Show the alert
                document.getElementById("alert").style.display = "block";
            } else {
                // Hide the alert if the input is valid
                document.getElementById("alert").style.display = "none";
            }
        }
        document.addEventListener("DOMContentLoaded", function () {
            const calendarSection = document.getElementById("calendar");
            if (calendarSection) {
                calendarSection.scrollIntoView({ behavior: "smooth", block: "center" });
            }


            const startDateInput = document.getElementById("start_date");
            const endDateInput = document.getElementById("end_date");

            const today = new Date().toISOString().split('T')[0];
            startDateInput.setAttribute('min', today);
            endDateInput.setAttribute('min', today);

            startDateInput.addEventListener("input", function () {
                // Get the selected start date
                const startDate = new Date(startDateInput.value);
                // Set the end date to the start date if it's empty or less than start date
                if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
                    endDateInput.value = startDateInput.value;
                }
                // Update the min attribute of end date to ensure it can't be before the start date
                endDateInput.setAttribute('min', startDateInput.value);
            });

            endDateInput.addEventListener("input", function () {
                // If the selected end date is before the start date, reset it
                if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                    endDateInput.value = startDateInput.value;
                }

            });

            document.querySelectorAll('input[name="slots[]"]').forEach(slot => {
                slot.addEventListener('click', function () {
                    autoSelectSlots(slot);
                });
            });


            const eventRadio = document.getElementById('purpose_event');
            const classRadio = document.getElementById('purpose_class');
            const eventTypeGroup = document.getElementById('event-type-group');

            function toggleEventType() {
                if (eventRadio.checked) {
                    eventTypeGroup.style.display = 'block';
                } else {
                    eventTypeGroup.style.display = 'none';
                }
            }

            // Attach event listeners to the radio buttons
            eventRadio.addEventListener('change', toggleEventType);
            classRadio.addEventListener('change', toggleEventType);


            const organiserInput = document.getElementById('organiser_name');
            const suggestionsBox = document.getElementById('suggestions');
            const mobileField = document.getElementById('organiser_mobile');
            const emailField = document.getElementById('organiser_email');
            const employeeIdField = document.getElementById('employee_id');

            let currentIndex = -1; // Tracks the currently highlighted suggestion
            let previousInputLength = 0; // Track the previous input length

            // Event listener for when the name input changes
            organiserInput.addEventListener('input', function () {
                const query = this.value.trim();

                // Check if the length of input is smaller than the previous length (indicating a deletion)
                if (query.length < previousInputLength) {
                    // Clear the fields if a letter is deleted
                    mobileField.value = '';
                    emailField.value = '';
                    employeeIdField.value = '';
                }

                // Update the previous input length for the next input event
                previousInputLength = query.length;

                if (query.length > 0) {
                    fetch('get_employee.php?department=<?php echo urlencode($department_name); ?>&query=' + encodeURIComponent(query))
                        .then(response => response.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            if (data.length > 0) {
                                suggestionsBox.style.display = 'block';
                                currentIndex = -1; // Reset the index when new suggestions are loaded
                                data.forEach((employee, index) => {
                                    const suggestion = document.createElement('li');
                                    suggestion.textContent = employee.employee_name;
                                    suggestion.className = 'list-group-item';
                                    suggestion.style.cursor = 'pointer';
                                    suggestion.setAttribute('data-index', index); // Add an index for reference
                                    suggestion.setAttribute('data-id', employee.employee_id); // Store the employee_id

                                    // Click event for mouse interaction
                                    suggestion.addEventListener('click', () => {
                                        organiserInput.value = employee.employee_name;
                                        suggestionsBox.style.display = 'none';
                                        fetchEmployeeDetails(employee.employee_id);  // Pass employee ID to fetch details
                                    });

                                    suggestionsBox.appendChild(suggestion);
                                });
                            } else {
                                suggestionsBox.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching suggestions:', error);
                        });
                } else {
                    suggestionsBox.style.display = 'none';
                }
            });

            // Keyboard navigation for suggestions
            organiserInput.addEventListener('keydown', function (e) {
                const suggestions = suggestionsBox.querySelectorAll('li');
                if (suggestions.length > 0) {
                    if (e.key === 'ArrowDown') {
                        // Move down
                        e.preventDefault();
                        if (currentIndex < suggestions.length - 1) {
                            currentIndex++;
                            updateHighlight(suggestions);
                        }
                    } else if (e.key === 'ArrowUp') {
                        // Move up
                        e.preventDefault();
                        if (currentIndex > 0) {
                            currentIndex--;
                            updateHighlight(suggestions);
                        }
                    } else if (e.key === 'Enter') {
                        // Select highlighted suggestion
                        e.preventDefault();
                        if (currentIndex >= 0 && currentIndex < suggestions.length) {
                            organiserInput.value = suggestions[currentIndex].textContent;
                            suggestionsBox.style.display = 'none';
                            const employeeId = suggestions[currentIndex].getAttribute('data-id');
                            fetchEmployeeDetails(employeeId);  // Pass the employee ID to fetch details
                        }
                    }
                }
            });

            // Function to update highlight
            function updateHighlight(suggestions) {
                suggestions.forEach((suggestion, index) => {
                    if (index === currentIndex) {
                        suggestion.classList.add('active'); // Highlight the current suggestion
                        suggestion.style.backgroundColor = '#007bff'; // Optional: Add a visual highlight
                        suggestion.style.color = '#fff'; // Optional: Change text color
                    } else {
                        suggestion.classList.remove('active');
                        suggestion.style.backgroundColor = ''; // Reset styles
                        suggestion.style.color = ''; // Reset styles
                    }
                });
            }

            // Close suggestions box on outside click
            document.addEventListener('click', function (e) {
                if (!suggestionsBox.contains(e.target) && e.target !== organiserInput) {
                    suggestionsBox.style.display = 'none';
                }
            });

            // Fetch employee details based on employee_id
            function fetchEmployeeDetails(employeeId) {
                if (!employeeId || employeeId <= 0) {
                    console.error('Invalid employee_id:', employeeId);
                    return;
                }

                fetch('get_employee_details.php?employee_id=' + encodeURIComponent(employeeId))
                    .then(response => response.text()) // Get raw text response
                    .then(data => {
                        try {
                            const jsonData = JSON.parse(data); // Parse the JSON
                            if (jsonData && !jsonData.error) {
                                // Autofill the mobile, email, and employee_id fields
                                mobileField.value = jsonData.employee_mobile || '';
                                emailField.value = jsonData.employee_email || '';
                                employeeIdField.value = jsonData.employee_id || ''; // Autofill the employee_id
                                triggerDuplicateCheck();
                                // Automatically jump to the next field after autofilling
                                if (mobileField.value) {
                                    emailField.focus();
                                } else if (emailField.value) {
                                    document.getElementById('start_date').focus();
                                }

                            } else {
                                console.error('Error fetching employee details:', jsonData.error || 'Unknown error');
                            }
                        } catch (error) {
                            console.error('Error parsing JSON response:', error);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching employee details:', error);
                    });
            }

        });


        const calendarData = <?php echo $calendarJson; ?>;
        const currentDateTime = new Date('<?php echo $currentDateTime; ?>');
        const timeSlots = [
            { slot: 1, time: '09:30 AM' }, { slot: 2, time: '10:30 AM' }, { slot: 3, time: '11:30 AM' }, { slot: 4, time: '12:30 PM' },
            { slot: 5, time: '01:30 PM' }, { slot: 6, time: '02:30 PM' }, { slot: 7, time: '03:30 PM' }, { slot: 8, time: '04:30 PM' }
            // ,{slot: 9, time: '05:30 PM'}, {slot: 10, time: '06:30 PM'}, {slot: 11, time: '07:30 PM'}, {slot: 12, time: '08:30 PM'}, {slot: 13, time: '09:30 PM'}
        ];

        let currentMonthIndex = 0;

        function renderCalendar(calendarData, startIndex = 0) {
            const container = document.getElementById('calendar-container');
            container.innerHTML = '';   

            const monthElement = document.createElement('div');
            monthElement.className = 'month-calendar';

            const month = calendarData[startIndex];

            const prevButton = startIndex === 0
                ? '<button class="prev-month" disabled>⬅️</button>'
                : '<button class="prev-month" onclick="showPrevMonth()">⬅️</button>';

            const nextButton = startIndex === calendarData.length - 1
                ? '<button class="next-month" disabled>➡️</button>'
                : '<button class="next-month" onclick="showNextMonth()">➡️</button>';

            monthElement.innerHTML = `
            <div class="time-slot-container" style="position: relative;">
                <div class="time-slot-loading-overlay" id="timeSlotLoadingOverlay">
                    <div class="time-slot-loading-content">
                        <div class="time-slot-loading-spinner"></div>
                        <div class="time-slot-loading-title">Processing Time Slot</div>
                        <div class="time-slot-loading-progress">
                            <div class="time-slot-loading-progress-bar" id="timeSlotLoadingProgress"></div>
                        </div>
                        <div class="time-slot-loading-status" id="timeSlotLoadingStatus">Initializing...</div>
                    </div>
                </div>
                ${renderTimeSlots(month.days, month.year, month.month)}
            </div>

                <div class="calendar-legend">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgb(85, 255, 122); border: 1px solid black;"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgb(244, 255, 91); border: 1px solid black;"></div>
                        <span>Pending</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgb(255, 103, 115); border: 1px solid black;"></div>
                        <span>Booked</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgb(162, 162, 162); border: 1px solid black;"></div>
                        <span>Past/Outside Semester</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: rgb(0, 123, 255); border: 1px solid black;"></div>
                        <span>Selected</span>
                    </div>
                </div>

                <div class="month-header">
                    ${prevButton}
                    <h5 style='margin:0; padding: 5px 10px;'>${new Date(month.year, month.month - 1).toLocaleString('default', { month: 'long', year: 'numeric' })}</h5>
                    ${nextButton}
                </div>
                
               

                
            `;

            container.appendChild(monthElement);
        }
        
        function renderTimeSlots(days, year, month) {
            const daysInMonth = new Date(year, month, 0).getDate();
            const allDays = Array.from({ length: 31 }, (_, i) => i + 1);

            // Create the header row for dates
            const dateRow = `
                    <div class="time-slot-row">
                        <div class="calendar-time-column"></div>
                        ${allDays.map(day => {
                const foundDay = days.find(d => new Date(d.date).getDate() === day);
                if (foundDay) {
                    const dayDate = new Date(foundDay.date);
                    const dayName = dayDate.toLocaleString('en-US', { weekday: 'short' });
                    const isWeekend = dayDate.getDay() === 0 || dayDate.getDay() === 6; // Sunday or Saturday
                    const weekendClass = isWeekend ? 'weekend-cell' : '';
                    
                    // Check if the date is within semester range
                    const isWithinSemester = foundDay.isWithinSemester;
                    const semesterClass = !isWithinSemester ? 'past' : '';

                    return `
                                    <div class="calendar-cell ${weekendClass} ${semesterClass}">
                                        <div class="day-number ${weekendClass} ${semesterClass}">${day}</div>
                                        <div class="day-name ${weekendClass} ${semesterClass}">${dayName}</div>
                                    </div>`;
                } else {
                    return `<div class="calendar-cell white-cell"></div>`;
                }
            }).join('')}
                    </div>
                `;

            // Create the rows for time slots
            const timeRows = timeSlots.map(({ slot, time }) => `
            <div class="time-slot-row">
                <div class="calendar-time-column">${time}</div>
                ${allDays.map(day => {
                const foundDay = days.find(d => new Date(d.date).getDate() === day);
                const dayDate = foundDay ? new Date(foundDay.date) : null;

                if (dayDate) {
                    const [hours, minutes, period] = time.match(/(\d+):(\d+)\s(AM|PM)/).slice(1);
                    let hour = parseInt(hours);
                    if (period === 'PM' && hour !== 12) hour += 12;
                    if (period === 'AM' && hour === 12) hour = 0;

                    const formattedTime = `${String(hour).padStart(2, '0')}:${minutes}`;
                    const slotDateTime = new Date(`${dayDate.toISOString().split('T')[0]}T${formattedTime}`);
                    const currentDateTime = new Date();

                    let cellClass = '';
                    const dayOfWeek = dayDate.getDay();
                    
                    // Check if the date is within semester range
                    const isWithinSemester = foundDay.isWithinSemester;

                    if (!isWithinSemester) {
                        // Date is outside semester range - mark as past
                        cellClass = 'past';
                    } else if (slotDateTime <= currentDateTime) {
                        // Date is in the past (within semester)
                        cellClass = 'past';
                    } else {
                        const bookedSlots = foundDay.bookedSlots.filter(bs => bs.slot === slot);
                        if (bookedSlots.length > 0) {
                            const statuses = [...new Set(bookedSlots.map(bs => bs.status))];
                            const status = statuses.includes('approved') ? 'approved' : 'pending';

                            return `
                                    <div class="calendar-cell ${status} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                                        data-organiser='${JSON.stringify(bookedSlots.map(bs => bs.organiserDetails))}'
                                        onclick="${status === 'pending'
                                    ? `handlePendingClick(this, '${dayDate.toISOString().split('T')[0]}', '${slot}')`
                                    : `showOrganizerDetails(this)`}">
                                    </div>`;
                        } else {
                            cellClass = 'available';
                        }
                    }

                    return `
                            <div class="calendar-cell ${cellClass} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                                onclick="${!isWithinSemester ? 'return false;' : `selectDate('${dayDate.toISOString().split('T')[0]}', this, '${slot}')`}"
                                data-date="${dayDate.toISOString().split('T')[0]}"
                                data-slot="${slot}">
                            </div>`;
                } else {
                    return `<div class="calendar-cell white-cell"></div>`;
                }
            }).join('')}
            </div>
        `).join('');

            return dateRow + timeRows;
        }


        function handlePendingClick(cell, date, slot) {
            // Check if the date is within semester range
            if (!isDateWithinSemester(date)) {
                alert('This date is outside the semester period and cannot be booked.');
                return;
            }
            
            // Show loading overlay for pending slot selection
            showTimeSlotLoading();
            simulateTimeSlotProcessing();
            
            setTimeout(() => {
                showOrganizerDetails(cell);
                selectDate(date, cell, slot);
                checkAvailability();
            }, 1000); // Wait for loading animation to complete
        }

        function selectDate(date, element, slot) {
            // Check if the date is within semester range
            if (!isDateWithinSemester(date)) {
                alert('This date is outside the semester period and cannot be booked.');
                return;
            }
            
            // Show loading overlay for time slot selection
            showTimeSlotLoading();
            simulateTimeSlotProcessing();
            
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const slotInput = document.getElementById('slot_or_session');
            const fnCheckbox = document.getElementById('fn');
            const anCheckbox = document.getElementById('an');
            fnCheckbox.checked = false;
            anCheckbox.checked = false;

            // Set the date inputs
            if (!startDateInput.value && !endDateInput.value) {
                startDateInput.value = date;
                endDateInput.value = date;
            } else {
                startDateInput.value = date;
                endDateInput.value = date;
            }
            
            // Update slot input with current selection
            const currentSelectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                .map(slot => slot.value);
            
            // Add the newly clicked slot if not already selected
            if (!currentSelectedSlots.includes(slot)) {
                currentSelectedSlots.push(slot);
            }
            
            slotInput.value = currentSelectedSlots.join(',');
            document.getElementById('booking').style.display = 'block';

            const status = element.classList.contains('pending') ? 'pending' : 'other';

            if (status === 'pending') {
                document.getElementById('organizer-details-table').style.display = 'table';
                document.getElementById('organizer-details-heading').style.display = 'block';
            } else {
                document.getElementById('organizer-details-table').style.display = 'none';
                document.getElementById('organizer-details-heading').style.display = 'none';
            }

            // Delay the slot checkbox update to allow loading animation to complete
            setTimeout(() => {
                updateSlotCheckboxes(slot, true); // true means add to selection
            }, 1000); // Wait for loading animation to complete
        }


        function updateSlotCheckboxes(selectedSlot, addToSelection = false) {
            const checkboxes = document.querySelectorAll('.slot-checkbox');
            
            if (addToSelection) {
                // Add to existing selection (multiple selection mode)
                const targetCheckbox = document.getElementById(`slot${selectedSlot}`);
                if (targetCheckbox) {
                    targetCheckbox.checked = true;
                }
            } else {
                // Single selection mode (original behavior)
                checkboxes.forEach(checkbox => {
                    checkbox.checked = checkbox.value === selectedSlot;
                });
            }
            
            // Update the hidden slot input with all selected slots
            const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                .map(slot => slot.value);
            document.getElementById('slot_or_session').value = selectedSlots.join(',');
            
            // Update the selected slots display
            updateSelectedSlotsDisplay(selectedSlots);
            
            // Update calendar visualization for selected slots
            updateCalendarForSelectedSlots(selectedSlots);
            
            // Call checkAvailability only once after updating all checkboxes
            checkAvailability();
        }


        let currentlyActiveCell = null;
        function showOrganizerDetails(element) {
            // Show loading overlay for organizer details
            showTimeSlotLoading();
            simulateTimeSlotProcessing();
            
            const organiserData = element.getAttribute('data-organiser');
            let organiserDetails;

            const previouslySelected = document.querySelector('.activeAttachment');
            if (previouslySelected) {
                previouslySelected.classList.remove('activeAttachment');
            }

            element.classList.add('activeAttachment');

            try {
                organiserDetails = JSON.parse(organiserData);
            } catch (e) {
                console.error("Invalid JSON in data-organiser:", organiserData);
                return;
            }

            const tableBody = document.querySelector("#organizer-details-table tbody");
            tableBody.innerHTML = "";

            if (Array.isArray(organiserDetails) && organiserDetails.length > 0) {
                const status = element.classList.contains('approved') ? 'approved' :
                    element.classList.contains('pending') ? 'pending' : 'available';

                organiserDetails.forEach(details => {
                    const row = document.createElement("tr");

                    // Combine organiser details
                    const organiserInfo = `
                ${details.name || 'N/A'}<br>
                <b><span style="color:#0e00a3">${details.department || 'N/A'}</b></span><br>
                ${details.email || 'N/A'}<br>
                ${details.mobile || 'N/A'}
            `;

                    // Combine purpose details
                    const purpose = details.purpose ? details.purpose.toUpperCase() : 'N/A';

                    // Format event type (replace '_' with '/' and capitalize)
                    const eventType = details.event_type
                        ? details.event_type.replace(/_/g, '/').replace(/\b\w/g, char => char.toUpperCase())
                        : '';

                    // Format purpose name (capitalize first letter)
                    const purposeName = details.purpose_name
                        ? details.purpose_name.charAt(0).toUpperCase() + details.purpose_name.slice(1)
                        : '';

                    // Combine purpose details
                    const purposeInfo = `
                        <span style="color:#0e00a3"><b>${purpose}</b></span><br>
                    <b> ${(details.purpose === 'event' ? eventType + '<br>' : '')}</b>
                        ${purposeName}
                    `;
                    const bookingInfo = `
                ${details.date || 'N/A'}<br>
               <span style="color:#0e00a3"> ${details.id_gen || ''}</span>
            `;
                    row.innerHTML = `
                <td>${organiserInfo}</td>
                <td>${purposeInfo}</td>
                <td>${details.capacity || 'N/A'}</td>
                <td>${bookingInfo}</td>
            `;
                    tableBody.appendChild(row);
                });

                document.getElementById('organizer-details-table').style.display = 'table';
                document.getElementById('organizer-details-heading').style.display = 'block';
                document.getElementById('booking').style.display = (status === 'approved' ? 'none' : 'block');
            } else {
                document.getElementById('organizer-details-table').style.display = 'none';
                document.getElementById('organizer-details-heading').style.display = 'none';
                document.getElementById('booking').style.display = 'block'; // Default to showing booking form
            }
            
            // Delay the availability check to allow loading animation to complete
            setTimeout(() => {
                checkAvailability();
            }, 1000); // Wait for loading animation to complete
        }
        function showPrevMonth() {
            if (currentMonthIndex > 0) {
                currentMonthIndex--;
                renderCalendar(calendarData, currentMonthIndex);
            }
        }

        function showNextMonth() {
            if (currentMonthIndex < calendarData.length - 1) {
                currentMonthIndex++;
                renderCalendar(calendarData, currentMonthIndex);
            }
        }
        
        // Function to get semester info for display
        function getSemesterInfo() {
            const semesterStart = '<?= $semesterStart ?>';
            const semesterEnd = '<?= $semesterEnd ?>';
            return { semesterStart, semesterEnd };
        }

        // Initial render
        renderCalendar(calendarData);

        function autoSelectSlots(checkbox) {
            const slots = document.querySelectorAll('input[name="slots[]"]');
            let start = null, end = null;

            slots.forEach((slot, index) => {
                if (slot.checked) {
                    if (start === null) start = index;
                    end = index;
                }
            });

            if (start !== null && end !== null) {
                for (let i = start; i <= end; i++) {
                    slots[i].checked = true;
                }
            }
            checkAvailability();
        }

        // Debounce mechanism to prevent rapid successive calls
        let checkAvailabilityTimeout;
        
        function checkAvailability() {
            // Clear any pending timeout
            if (checkAvailabilityTimeout) {
                clearTimeout(checkAvailabilityTimeout);
            }
            
            // Set a new timeout to delay the actual check
            checkAvailabilityTimeout = setTimeout(() => {
                performAvailabilityCheck();
            }, 300); // 300ms delay
        }
        
        function performAvailabilityCheck() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const hallId = document.querySelector('input[name="hall_id"]').value;
            const organiserId = document.getElementById('employee_id').value;

            const bookingType = 'slot';

            // Collect selected slots
            const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                .map(slot => slot.value);

            const sessionOrSlots = selectedSlots.join(',');

            // Debug logging
            console.log('checkAvailability called with:', {
                startDate,
                endDate,
                hallId,
                organiserId,
                bookingType,
                sessionOrSlots,
                selectedSlots
            });

            // Check if all required fields are filled
            if (startDate && endDate && bookingType && sessionOrSlots && organiserId && organiserId !== '') {
                // Show availability checking loading
                showAvailabilityLoading();
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'check_availability_modify.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.timeout = 10000; // 10 second timeout

                xhr.onload = function () {
                    hideAvailabilityLoading();
                    if (xhr.status === 200) {
                        try {
                            console.log('Raw response:', xhr.responseText);
                            
                            // Check if response is empty
                            if (!xhr.responseText.trim()) {
                                throw new Error('Empty response from server');
                            }
                            
                            const response = JSON.parse(xhr.responseText);
                            console.log('Parsed response:', response);
                            
                            const messageDiv = document.getElementById('availability_message');
                            
                            if (response.available) {
                                // Check if there are pending conflicts
                                if (response.has_conflicts && response.pending_conflicts) {
                                    // Show warning styling for conflicts with enhanced formatting
                                    const conflictSlots = response.pending_conflicts.map(conflict => 
                                        `<div class="conflict-slot">
                                            <span class="slot-number">Slot ${conflict.slot}</span>: 
                                            <span class="organizer-info">${conflict.organiser} (${conflict.purpose}) - Pending</span>
                                        </div>`
                                    ).join('');
                                    
                                    messageDiv.innerHTML = `<div class="alert alert-warning conflict-warning" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Booking Available with Conflicts</strong><br><br>
                                        <strong>Note:</strong> This will create a booking conflict with pending requests:<br>
                                        ${conflictSlots}
                                        <br><em>The conflict will be resolved by administrators during the approval process.</em>
                                    </div>`;
                                    
                                    // Show conflict information section
                                    document.getElementById('conflict_info').style.display = 'block';
                                } else {
                                    // Show success styling for no conflicts
                                    messageDiv.innerHTML = `<div class="alert alert-success" role="alert">
                                        <i class="bi bi-check-circle me-2"></i>
                                        ${response.message}
                                    </div>`;
                                    
                                    // Hide conflict information section
                                    document.getElementById('conflict_info').style.display = 'none';
                                }
                                showBookingFormWithAnimation();
                            } else {
                                messageDiv.innerHTML = `<div class="alert alert-danger" role="alert">
                                    <i class="bi bi-x-circle me-2"></i>
                                    ${response.message}
                                </div>`;
                                hideBookingForm();
                            }
                        } catch (error) {
                            console.error('Error parsing response:', error);
                            console.error('Response text:', xhr.responseText);
                            document.getElementById('availability_message').innerHTML = 
                                '<span class="text-danger">Error processing response. Please try again.</span>';
                        }
                    } else {
                        console.error('Request failed with status:', xhr.status);
                        console.error('Response text:', xhr.responseText);
                        document.getElementById('availability_message').innerHTML = 
                            '<span class="text-danger">Network error. Please check your connection.</span>';
                    }
                };

                xhr.onerror = function() {
                    hideAvailabilityLoading();
                    console.error('XHR error occurred');
                    document.getElementById('availability_message').innerHTML = 
                        '<span class="text-danger">Network error. Please check your connection.</span>';
                };

                xhr.ontimeout = function() {
                    hideAvailabilityLoading();
                    console.error('XHR timeout occurred');
                    document.getElementById('availability_message').innerHTML = 
                        '<span class="text-danger">Request timed out. Please try again.</span>';
                };

                const requestData = `hall_id=${hallId}&start_date=${startDate}&end_date=${endDate}&booking_type=${bookingType}&session_or_slots=${sessionOrSlots}&organiser_id=${organiserId}`;
                console.log('Sending request data:', requestData);
                
                // Include organiser_id in the request
                xhr.send(requestData);
            } else {
                console.log('Missing required fields:', {
                    startDate: !!startDate,
                    endDate: !!endDate,
                    bookingType: !!bookingType,
                    sessionOrSlots: !!sessionOrSlots,
                    organiserId: !!organiserId
                });
                
                const messageDiv = document.getElementById('availability_message');
                messageDiv.innerHTML = '<span class="text-warning">Please select all required fields (date, slots, and ensure organizer is set).</span>';
            }
        }

        function showAvailabilityLoading() {
            const messageDiv = document.getElementById('availability_message');
            messageDiv.innerHTML = `
                <div class="availability-loading">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="text-primary">Checking availability...</span>
                </div>
            `;
        }

        function hideAvailabilityLoading() {
            // Loading will be replaced by the actual message
        }

        function showBookingFormWithAnimation() {
            const bookingForm = document.getElementById('booking2');
            bookingForm.style.display = 'block';
            bookingForm.classList.add('show');
            
            // Scroll to the booking form for better UX
            setTimeout(() => {
                bookingForm.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300);
        }

        function hideBookingForm() {
            const bookingForm = document.getElementById('booking2');
            bookingForm.style.display = 'none';
        }

        const today = new Date().toISOString().split('T')[0];

        // Retrieve the DOM elements, NOT their values
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        // Ensure both inputs have a minimum value of today
        startDateInput.setAttribute('min', today);
        endDateInput.setAttribute('min', today);

        startDateInput.addEventListener("input", function () {
            // Get the selected start date
            const startDate = new Date(startDateInput.value);

            // If the end date is empty or set to a date before the start date, update it to match the start date.
            if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
                endDateInput.value = startDateInput.value;
            }

            // Update the min attribute of the end date to ensure it cannot be set to a day before the start date.
            endDateInput.setAttribute('min', startDateInput.value);
        });

        endDateInput.addEventListener("input", function () {
            // If there's no start date, fill the start date with the value of the end date.
            if (!startDateInput.value) {
                startDateInput.value = endDateInput.value;
            }
            // If the selected end date is before the start date, reset it to the start date.
            else if (new Date(endDateInput.value) < new Date(startDateInput.value)) {
                endDateInput.value = startDateInput.value;
            }
        });
    </script>
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay" style="display: none;">
            <div class="loading-content">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="text-white mb-2">Processing Slot Selection</h5>
                <div class="progress mb-3" style="width: 300px;">
                    <div id="loadingProgress" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        0%
                    </div>
                </div>
                <p id="loadingStatus" class="text-white-50 mb-0">Initializing...</p>
            </div>
        </div>

        <script>
            // Add loading functionality to individual slot selection
            document.addEventListener('DOMContentLoaded', function() {
                const slotCheckboxes = document.querySelectorAll('input[name="slots[]"]');
                
                slotCheckboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        updateSlotCounter();
                        checkAvailability();
                    });
                });

                // Initialize performance optimizations
                optimizeSlotSelection();
                enhanceFormValidation();
                
                // Add smooth scrolling for better UX
                const smoothScrollToElement = (element) => {
                    if (element) {
                        element.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                };

                // Enhanced slot selection with visual feedback
                slotCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        const label = document.querySelector(`label[for="${this.id}"]`);
                        if (this.checked) {
                            label.style.animation = 'slotSelected 0.3s ease';
                        } else {
                            label.style.animation = 'slotDeselected 0.3s ease';
                        }
                    });
                });
            });

            function showLoadingOverlay() {
                const overlay = document.getElementById('loadingOverlay');
                const progressBar = document.getElementById('loadingProgress');
                const statusText = document.getElementById('loadingStatus');
                
                overlay.style.display = 'flex';
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                statusText.textContent = 'Initializing slot selection...';
            }

            function hideLoadingOverlay() {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.display = 'none';
            }

            function simulateSlotProcessing() {
                const progressBar = document.getElementById('loadingProgress');
                const statusText = document.getElementById('loadingStatus');
                let progress = 0;
                
                const steps = [
                    { progress: 20, status: 'Validating slot availability...' },
                    { progress: 40, status: 'Checking for conflicts...' },
                    { progress: 60, status: 'Updating calendar...' },
                    { progress: 80, status: 'Finalizing selection...' },
                    { progress: 100, status: 'Slot selected successfully!' }
                ];
                
                let currentStep = 0;
                
                const interval = setInterval(() => {
                    if (currentStep < steps.length) {
                        const step = steps[currentStep];
                        progress = step.progress;
                        
                        progressBar.style.width = progress + '%';
                        progressBar.textContent = progress + '%';
                        statusText.textContent = step.status;
                        
                        currentStep++;
                    } else {
                        clearInterval(interval);
                        
                        // Hide overlay after a short delay to show completion
                        setTimeout(() => {
                            hideLoadingOverlay();
                            // Trigger availability check after slot selection
                            setTimeout(() => {
                                checkAvailability();
                            }, 300);
                        }, 800);
                    }
                }, 200);
            }

            // Enhanced slot selection with visual feedback
            function selectSlot(slotId) {
                const checkbox = document.getElementById('slot' + slotId);
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            }

            // Add click event to slot labels for better UX
            document.addEventListener('DOMContentLoaded', function() {
                const slotLabels = document.querySelectorAll('label[for^="slot"]');
                slotLabels.forEach(function(label) {
                    label.style.cursor = 'pointer';
                    label.addEventListener('click', function() {
                        const checkbox = document.getElementById(this.getAttribute('for'));
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            checkbox.dispatchEvent(new Event('change'));
                        }
                    });
                });

                // Add keyboard navigation for slots
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        const focusedElement = document.activeElement;
                        if (focusedElement && focusedElement.classList.contains('slot-label')) {
                            e.preventDefault();
                            focusedElement.click();
                        }
                    }
                });
            });

            // Add slot selection counter
            function updateSlotCounter() {
                const selectedSlots = document.querySelectorAll('input[name="slots[]"]:checked');
                const counter = document.getElementById('slotCounter');
                if (counter) {
                    counter.textContent = selectedSlots.length;
                    if (selectedSlots.length > 0) {
                        counter.style.display = 'inline-block';
                    } else {
                        counter.style.display = 'none';
                    }
                }
            }

            // Enhanced slot processing with real-time feedback
            function simulateSlotProcessing() {
                const progressBar = document.getElementById('loadingProgress');
                const statusText = document.getElementById('loadingStatus');
                let progress = 0;
                
                const steps = [
                    { progress: 10, status: 'Initializing slot selection...' },
                    { progress: 25, status: 'Validating slot availability...' },
                    { progress: 40, status: 'Checking for conflicts...' },
                    { progress: 55, status: 'Updating calendar...' },
                    { progress: 70, status: 'Processing selection...' },
                    { progress: 85, status: 'Finalizing...' },
                    { progress: 100, status: 'Slot selected successfully!' }
                ];
                
                let currentStep = 0;
                
                const interval = setInterval(() => {
                    if (currentStep < steps.length) {
                        const step = steps[currentStep];
                        progress = step.progress;
                        
                        progressBar.style.width = progress + '%';
                        progressBar.textContent = progress + '%';
                        statusText.textContent = step.status;
                        
                        // Add pulse effect to progress bar
                        progressBar.style.animation = progress === 100 ? 'none' : 'pulse 1s infinite';
                        
                        currentStep++;
                    } else {
                        clearInterval(interval);
                        
                        // Hide overlay after a short delay to show completion
                        setTimeout(() => {
                            hideLoadingOverlay();
                            showSlotSelectionSuccess();
                            // Trigger availability check after slot selection with optimized delay
                            setTimeout(() => {
                                checkAvailability();
                            }, 200);
                        }, 600);
                    }
                }, 120); // Faster processing
            }

            // Optimized calendar rendering with performance improvements
            function optimizeCalendarRendering() {
                const calendarContainer = document.getElementById('calendar-container');
                if (calendarContainer) {
                    // Use requestAnimationFrame for smooth rendering
                    requestAnimationFrame(() => {
                        calendarContainer.style.opacity = '0';
                        calendarContainer.style.transform = 'scale(0.95)';
                        
                        setTimeout(() => {
                            calendarContainer.style.transition = 'all 0.3s ease';
                            calendarContainer.style.opacity = '1';
                            calendarContainer.style.transform = 'scale(1)';
                        }, 50);
                    });
                }
            }

            // Enhanced form validation with real-time feedback
            function enhanceFormValidation() {
                const form = document.getElementById('userDetailsForm');
                const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
                
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                    
                    input.addEventListener('input', function() {
                        if (this.classList.contains('is-invalid')) {
                            validateField(this);
                        }
                    });
                });
            }

            function validateField(field) {
                const value = field.value.trim();
                const isValid = value.length > 0;
                
                if (isValid) {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                } else {
                    field.classList.remove('is-valid');
                    field.classList.add('is-invalid');
                }
            }

            // Performance optimization for slot selection
            function optimizeSlotSelection() {
                const slotCheckboxes = document.querySelectorAll('input[name="slots[]"]');
                let lastChecked = null;
                
                slotCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function(e) {
                        if (this.checked && lastChecked && lastChecked !== this && e.shiftKey) {
                            // Select range of slots
                            const checkboxes = Array.from(slotCheckboxes);
                            const start = checkboxes.indexOf(lastChecked);
                            const end = checkboxes.indexOf(this);
                            const range = checkboxes.slice(Math.min(start, end), Math.max(start, end) + 1);
                            
                            range.forEach(cb => {
                                cb.checked = true;
                                cb.dispatchEvent(new Event('change'));
                            });
                        }
                        lastChecked = this;
                    });
                });
            }

            // Show success notification for slot selection
            function showSlotSelectionSuccess() {
                const notification = document.createElement('div');
                notification.className = 'slot-selection-feedback';
                notification.innerHTML = `
                    <i  class="bi bi-check-circle-fill me-2"></i>
                    Slot selected successfully!
                `;
                document.body.appendChild(notification);
                
                // Show notification
                setTimeout(() => {
                    notification.classList.add('show');
                }, 100);
                
                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }

            // Time-slot loading overlay functions
            function showTimeSlotLoading() {
                const overlay = document.getElementById('timeSlotLoadingOverlay');
                const progressBar = document.getElementById('timeSlotLoadingProgress');
                const statusText = document.getElementById('timeSlotLoadingStatus');
                
                if (overlay) {
                    overlay.style.display = 'flex';
                    progressBar.style.width = '0%';
                    statusText.textContent = 'Initializing time slot selection...';
                }
            }

            function hideTimeSlotLoading() {
                const overlay = document.getElementById('timeSlotLoadingOverlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
            }

            function updateTimeSlotLoadingProgress(progress, status) {
                const progressBar = document.getElementById('timeSlotLoadingProgress');
                const statusText = document.getElementById('timeSlotLoadingStatus');
                
                if (progressBar && statusText) {
                    progressBar.style.width = progress + '%';
                    statusText.textContent = status;
                }
            }

            function simulateTimeSlotProcessing() {
                const progressBar = document.getElementById('timeSlotLoadingProgress');
                const statusText = document.getElementById('timeSlotLoadingStatus');
                let progress = 0;
                
                const steps = [
                    { progress: 15, status: 'Validating date selection...' },
                    { progress: 30, status: 'Checking slot availability...' },
                    { progress: 45, status: 'Updating calendar display...' },
                    { progress: 60, status: 'Processing time slot...' },
                    { progress: 75, status: 'Finalizing selection...' },
                    { progress: 90, status: 'Almost done...' },
                    { progress: 100, status: 'Time slot processed successfully!' }
                ];
                
                let currentStep = 0;
                
                const interval = setInterval(() => {
                    if (currentStep < steps.length) {
                        const step = steps[currentStep];
                        progress = step.progress;
                        
                        updateTimeSlotLoadingProgress(progress, step.status);
                        currentStep++;
                    } else {
                        clearInterval(interval);
                        
                        // Hide overlay after a short delay to show completion
                        setTimeout(() => {
                            hideTimeSlotLoading();
                            // Show success notification
                            showSlotSelectionSuccess();
                        }, 500);
                    }
                }, 150); // Faster processing for better UX
            }

            // Add event listeners for slot checkboxes to handle multiple selection
            document.addEventListener('DOMContentLoaded', function() {
                const slotCheckboxes = document.querySelectorAll('.slot-checkbox');
                
                slotCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        // Update the hidden slot input with all selected slots
                        const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                            .map(slot => slot.value);
                        document.getElementById('slot_or_session').value = selectedSlots.join(',');
                        
                        // Update the selected slots display
                        updateSelectedSlotsDisplay(selectedSlots);
                        
                        // Update calendar visualization for selected slots
                        updateCalendarForSelectedSlots(selectedSlots);
                        
                        // Call availability check when slots change
                        checkAvailability();
                    });
                });
            });

                    // Function to handle date changes and update calendar
        function handleDateChange() {
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const startDateStr = startInput.value;
            const endDateStr = endInput.value;

            const startDate = new Date(startDateStr);
            const endDate = new Date(endDateStr);
            const minDate = new Date(startInput.min);
            const maxDate = new Date(startInput.max);

            // Reset error messages
            document.getElementById('start_date_error').textContent = '';
            document.getElementById('end_date_error').textContent = '';

            // Validation: start date within semester range
            if (startDateStr && (startDate < minDate || startDate > maxDate)) {
                document.getElementById('start_date_error').textContent = 'Start date must be within the semester.';
                startInput.value = startInput.min;
                return;
            }

            // Validation: end date within semester range
            if (endDateStr && (endDate < minDate || endDate > maxDate)) {
                document.getElementById('end_date_error').textContent = 'End date must be within the semester.';
                endInput.value = endInput.max;
                return;
            }

            // Validation: end date is not before start date
            if (startDateStr && endDateStr && startDate > endDate) {
                document.getElementById('end_date_error').textContent = 'End date cannot be before start date.';
                endInput.value = startInput.value;
                return;
            }

            // If all validation passed, proceed with calendar updates
            if (startDateStr && endDateStr) {
                // Update calendar for selected date range
                updateCalendarForDateRange(startDateStr, endDateStr);

                // Update selected slots in calendar visualization
                const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                    .map(slot => slot.value);
                updateCalendarForSelectedSlots(selectedSlots);

                // Check availability
                checkAvailability();
            }
        }
        
        // Function to check if a date is within semester range
        function isDateWithinSemester(dateStr) {
            const semesterStart = '<?= $semesterStart ?>';
            const semesterEnd = '<?= $semesterEnd ?>';
            
            const date = new Date(dateStr);
            const start = new Date(semesterStart);
            const end = new Date(semesterEnd);
            
            return date >= start && date <= end;
        }


            // Function to update calendar for selected date range
            function updateCalendarForDateRange(startDate, endDate) {
                // Find the month that contains the start date
                const startDateObj = new Date(startDate);
                const currentYear = startDateObj.getFullYear();
                const currentMonth = startDateObj.getMonth() + 1; // JavaScript months are 0-indexed
                
                // Find the corresponding month in calendarData
                const targetMonthIndex = calendarData.findIndex(month => 
                    month.year === currentYear && month.month === currentMonth
                );
                
                if (targetMonthIndex !== -1) {
                    // Update current month index and re-render calendar
                    currentMonthIndex = targetMonthIndex;
                    renderCalendar(calendarData, currentMonthIndex);
                }
            }

            // Function to update calendar visualization for selected slots
            function updateCalendarForSelectedSlots(selectedSlots) {
                const startDate = document.getElementById('start_date').value;
                const endDate = document.getElementById('end_date').value;
                
                if (!startDate || !endDate || selectedSlots.length === 0) {
                    // Clear all active attachments if no date or slots selected
                    const activeCells = document.querySelectorAll('.activeAttachment');
                    activeCells.forEach(cell => {
                        cell.classList.remove('activeAttachment');
                    });
                    return;
                }
                
                // Clear existing active attachments
                const activeCells = document.querySelectorAll('.activeAttachment');
                activeCells.forEach(cell => {
                    cell.classList.remove('activeAttachment');
                });
                
                // Add active attachment to cells that match the selected date and slots
                const calendarCells = document.querySelectorAll('.calendar-cell');
                calendarCells.forEach(cell => {
                    const cellDate = cell.getAttribute('data-date');
                    const cellSlot = cell.getAttribute('data-slot');
                    
                    if (cellDate && cellSlot) {
                        // Check if this cell is within the selected date range
                        const cellDateObj = new Date(cellDate);
                        const startDateObj = new Date(startDate);
                        const endDateObj = new Date(endDate);
                        
                        if (cellDateObj >= startDateObj && cellDateObj <= endDateObj) {
                            // Check if this cell's slot is selected
                            if (selectedSlots.includes(cellSlot)) {
                                cell.classList.add('activeAttachment');
                                
                                // Add special animation for multiple selections
                                if (selectedSlots.length > 1) {
                                    cell.style.animation = 'multipleSlotSelected 0.5s ease-in-out';
                                }
                            }
                        }
                    }
                });
                
                // Show visual feedback for multiple slot selection
                if (selectedSlots.length > 1) {
                    showMultipleSlotFeedback(selectedSlots.length);
                }
            }

            // Function to show visual feedback for multiple slot selection
            function showMultipleSlotFeedback(slotCount) {
                // Find or create the feedback element
                let feedbackElement = document.getElementById('multiple-slot-feedback');
                if (!feedbackElement) {
                    feedbackElement = document.createElement('div');
                    feedbackElement.id = 'multiple-slot-feedback';
                    feedbackElement.className = 'alert alert-info mt-2';
                    feedbackElement.style.position = 'fixed';
                    feedbackElement.style.top = '150px';
                    feedbackElement.style.right = '20px';
                    feedbackElement.style.zIndex = '1000';
                    feedbackElement.style.minWidth = '250px';
                    feedbackElement.style.animation = 'slideInRight 0.3s ease-out';
                    document.body.appendChild(feedbackElement);
                }
                
                feedbackElement.innerHTML = `
                    <div>
                     <i class="bi bi-check-circle-fill me-2"></i>
                     <strong>Multiple Slots Selected:</strong> ${slotCount} time slot(s) selected
                    </div>
                `;
                feedbackElement.style.display = 'block';
                
                // Hide the feedback after 3 seconds
                setTimeout(() => {
                    feedbackElement.style.display = 'none';
                }, 3000);
            }

            // Function to update selected slots display and handle selection/deselection
            function updateSelectedSlotsDisplay(selectedSlots) {
                const slotLabels = {
                    1: '09:30am',
                    2: '10:30am',
                    3: '11:30am',
                    4: '12:30pm',
                    5: '01:30pm',
                    6: '02:30pm',
                    7: '03:30pm',
                    8: '04:30pm'
                };

                const selectedLabels = selectedSlots.map(slot => slotLabels[slot]).filter(label => label);

                // Find or create the selected slots display element
                let displayElement = document.getElementById('selected-slots-display');
                if (!displayElement) {
                    displayElement = document.createElement('div');
                    displayElement.id = 'selected-slots-display';
                    displayElement.className = 'alert alert-success mt-2';
                    displayElement.style.display = 'none';

                    // Insert after the slot checkboxes
                    const slotContainer = document.querySelector('.row');
                    if (slotContainer) {
                        slotContainer.parentNode.insertBefore(displayElement, slotContainer.nextSibling);
                    }
                }

                // Display the selected slots
                if (selectedLabels.length > 0) {

                    // For debugging the Multiple Slot Selection
                    // displayElement.innerHTML = `
                    //     <i class="bi bi-check-circle me-2"></i>
                    //     <strong>Selected Slots:</strong> ${selectedLabels.join(', ')}
                    // `;
                    // displayElement.style.display = 'block';
                } else {
                    displayElement.style.display = 'none';
                }
            }

            // Function to handle the slot checkbox click
            function toggleSlotSelection(slotId) {
                const slotCheckbox = document.getElementById(`slot${slotId}`);
                slotCheckbox.checked = !slotCheckbox.checked; // Toggle the checkbox state

                // Get all selected slots
                const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked'))
                    .map(slot => slot.value);

                // Update the hidden input and the selected slots display
                document.getElementById('slot_or_session').value = selectedSlots.join(',');
                updateSelectedSlotsDisplay(selectedSlots);
            }

            // Add event listeners to each slot checkbox
            document.querySelectorAll('.slot-checkbox').forEach(checkbox => {
                checkbox.addEventListener('click', (event) => {
                    const slotId = event.target.value;
                    toggleSlotSelection(slotId);
                });
            });         


            // Function to clear all slot selections
            function clearSlotSelection() {
                const slotCheckboxes = document.querySelectorAll('.slot-checkbox');
                slotCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Clear the hidden slot input
                document.getElementById('slot_or_session').value = '';
                
                // Update the display
                updateSelectedSlotsDisplay([]);
                
                // Clear calendar visualization
                updateCalendarForSelectedSlots([]);
                
                // Call availability check
                checkAvailability();
            }

            // Global function to update slots based on session checkboxes
            window.updateSlotsBasedOnSession = function() {
                const fnSelected = document.querySelector('#fn').checked;
                const anSelected = document.querySelector('#an').checked;
                const slotCheckboxes = document.querySelectorAll('.slot-checkbox');

                slotCheckboxes.forEach(checkbox => {
                    const slotValue = parseInt(checkbox.value);
                    if (fnSelected && slotValue >= 1 && slotValue <= 4) {
                        checkbox.checked = true;
                    }
                    if (anSelected && slotValue >= 5 && slotValue <= 8) {
                        checkbox.checked = true;
                    }
                    if (!fnSelected && slotValue >= 1 && slotValue <= 4) {
                        checkbox.checked = false;
                    }
                    if (!anSelected && slotValue >= 5 && slotValue <= 8) {
                        checkbox.checked = false;
                    }
                });
                
                // Update calendar visualization
                const selectedSlots = Array.from(slotCheckboxes)
                    .filter(checkbox => checkbox.checked)
                    .map(checkbox => checkbox.value);
                updateCalendarForSelectedSlots(selectedSlots);
                
                // Update the hidden slot input
                document.getElementById('slot_or_session').value = selectedSlots.join(',');
                
                // Update the selected slots display
                updateSelectedSlotsDisplay(selectedSlots);
                
                checkAvailability();
            };
        </script>
    </body>

</html>