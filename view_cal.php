<?php
include('assets/conn.php');
include 'assets/header.php'; 

// Fetch latest semester start and end date
$query = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latestSemester = mysqli_fetch_assoc($result);

$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));

if (isset($_GET['hall_id'])) {
    $hall_id = intval($_GET['hall_id']);
    $sql = "SELECT h.*, s.*, d.*
    FROM hall_details h
    LEFT JOIN schools s ON h.school_id = s.school_id    
    LEFT JOIN departments d ON h.department_id = d.department_id
    WHERE h.hall_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo '<p>Database error: ' . htmlspecialchars($conn->error) . '</p>';
        exit;
    }
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $hall = $result->fetch_assoc();
    } else {
        echo '<p>Hall not found.</p>';
        exit;
    }$feature_columns = ['wifi', 'ac', 'projector', 'computer', 'audio_system', 'podium', 'ramp', 'smart_board', 'lift', 'white_board', 'blackboard'];

    $features = [];
    foreach ($feature_columns as $column) {
        if (!empty($hall[$column]) && $hall[$column] != "No") {
            // Convert column name to a user-friendly label
            $features[] = ucfirst(str_replace('_', ' ', $column));
        }
    }

} else {
    echo '<p>No hall selected.</p>';
    exit;
}

function getDaysInMonth($year, $month) {
    return date('t', mktime(0, 0, 0, $month, 1, $year));
}


function getBookedSlots($conn, $hall_id, $date) {
    $query = "SELECT slot_or_session, status, booking_id_gen,booking_date,students_count, organiser_name, organiser_email, organiser_department, organiser_mobile, organiser_department, purpose, event_type, purpose_name 
              FROM bookings 
              WHERE hall_id = ? 
              AND ? BETWEEN start_date AND end_date
              AND status IN ('approved', 'pending', 'allow')";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $hall_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bookedSlots = [];
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
                'dept' => htmlspecialchars($row['organiser_department']),
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

$calendarJson = json_encode($calendar, JSON_PRETTY_PRINT);
$currentDateTime = date('Y-m-d H:i:s');

// Debug output
echo "<!-- Debug: Calendar Data -->\n";
echo "<!-- " . print_r($calendar, true) . " -->\n";
echo "<!-- End Debug -->\n";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hall['hall_name']); ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: white;
        }

        #main {
            margin-left: 250px;
            transition: margin-left .5s;
            padding: 16px;
            padding-top: 0px;
        }

        .hall-info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            flex: 1;
            min-width: 250px;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .hall-details-box {
            background-color: #e3f2fd; /* Light blue */
        }

        .features-box {
            background-color: #e8f5e9; /* Light green */
        }

        .incharge-box {
            background-color: #fff3e0; /* Light orange */
        }

        .calendar-container {
            width: 100%;
            margin: 0 auto;
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

        /* Available cells (green) */
        .calendar-cell.available {
            background-color: rgb(85, 255, 122);
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.available:hover {
            background-color: rgb(20, 255, 71);
            border: 1px solid black;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(0, 180, 39, 0.6);
            color: white;
        }

        /* Pending cells (yellow) */
        .calendar-cell.pending {
            background-color: rgb(244, 255, 91);
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.pending:hover {
            background-color: rgb(242, 255, 0);
            border: 1px solid black;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(243, 255, 6, 0.6);
            color: black;
        }

        /* Approved cells (red) */
        .calendar-cell.approved {
            background-color: rgb(255, 103, 115);
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.approved:hover {
            background-color: rgb(255, 40, 58);
            border: 1px solid black;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(255, 40, 58, 0.6);
            color: white;
        }

        /* Forward Booking cells (orange) */
        .calendar-cell.allow {
            background-color: rgb(255, 214, 102);
            border: 1px solid black;
            transition: background-color 0.3s, transform 0.2s, box-shadow 0.3s ease-in-out;
        }

        .calendar-cell.allow:hover {
            background-color: rgb(255, 193, 7);
            border: 1px solid black;
            transform: scale(1.05);
            box-shadow: 0 2px 5px rgba(255, 193, 7, 0.6);
            color: black;
        }

        /* Past cells */
        .calendar-cell.past {
            background-color: rgb(162, 162, 162);
            border: 1px solid black;
            cursor: not-allowed;
        }

        /* White cells */
        .white-cell {
            background-color: white;
            cursor: none;
        }

        .feature-icon {
            font-size: 1.2em;
            color: #007bff;
        }

        .day-name {
            font-size: 0.6em;
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
        .calendar-cell.allow.whitish-cell {
            background-color: rgba(255, 214, 102, 0.6);
            /* Forward Booking with weekend overlay */
        }

        .calendar-cell.past.whitish-cell {
            background-color: rgba(162, 162, 162, 0.5);
            /* Past with weekend overlay */
            cursor: not-allowed;
        }

        .activeAttachment {
            background-color: rgb(0, 123, 255) !important;
            box-shadow: 0 0 5px rgba(4, 170, 253, 0.5);
            transform: scale(1.05);
            /* Slight scale effect */
        }

        .calendar-cell.weekend-cell {
            /* background-color:rgb(212, 212, 212);  */
            color: #333;
            /* Optional: Adjust text color for better contrast */
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
            margin-left:300px;
        }

        .month-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }

        .month-header button {
            font-size: 1.5rem;
            padding: 5px 10px;
            cursor: pointer;
            border: 0px;
            background-color: transparent;
        }

        .month-header button:disabled {
            cursor: not-allowed;
        }

        .calendar-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 20px;
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
        }

        .legend-item span {
            font-size: 14px;
            color: #333;
        }

        @media (max-width: 900px) {
            .calendar-cell{
                width: 10px;
                height: 10px;           
           font-size: 0.5rem !important;
          }
          .calendar-time-column{
           font-size: 0.5rem !important;
          }
        }
            @media (max-width: 768px) {
            #main {
                margin-left: 0;
                padding: 10px;
            }
            
            .info-box {
                min-width: 100%;
            }
           
        }

        #organizer-details-table{
            width: 70%;
            text-align: center;
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
    </style>
</head>

<body>
    <div id="main">
        <br><br><br><br>

        <!-- Hall information boxes in a row -->
        <div class="hall-info-row">
            <!-- Hall Details Box -->
            <div class="info-box hall-details-box">
                    <h4 style="color:#0e00a3;font-weight: bold;">
                    <center>Hall Details</h4></center>
              <br>
                <b><span style="color:blue;"><?= htmlspecialchars($hall['hall_name']) ?></span></b><br>
                <b><span style="color:black;"><?= htmlspecialchars($hall['school_name']) ?></b></span><br>
                <?= htmlspecialchars($hall['department_name']) ?><br>
                <strong>Capacity:</strong> <?php echo $hall['capacity']; ?><br>
                <strong>Floor:</strong> <?php echo $hall['floor']; ?><br>
                <strong>Zone:</strong> <?php echo $hall['zone']; ?>
            </div>

            <!-- Features Box -->
            <div class="info-box features-box">
                    <h4 style="color:#0e00a3;font-weight: bold;">
                    <center>Features</h4></center>
               <br>
                <div class="feature-grid ml-5">
                 <?php if (!empty($features)): ?>
                        <?php foreach ($features as $feature): ?>
                            <div class="feature-item">
                                <i class="feature-icon fas fa-check-circle"></i>
                                <span style="padding-left:10px;" class="feature-name"><?php echo htmlspecialchars(trim($feature)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No features available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- In-Charge Box -->
            <div class="info-box incharge-box">
                    <h4 style="color:#0e00a3;font-weight: bold;">
                    <center>In-Charge Details</h4></center>
                <br>
                <strong>Name:</strong> <?= htmlspecialchars($hall['incharge_name']) ?><br>
                <strong>Designation:</strong> <?= htmlspecialchars($hall['designation']) ?><br>
                <strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($hall['incharge_email']) ?>" style="color: blue;">
                    <?= htmlspecialchars($hall['incharge_email']) ?>
                </a><br>
                <strong>Intercom:</strong> <?= htmlspecialchars($hall['incharge_intercom']) ?>
            </div>
        </div>

        <!-- Calendar at the bottom -->
        <div class="calendar-container">
            <center><h3>Booking Details</h3></center>
            
            <!-- Semester Range Banner -->
            <div class="semester-banner mb-3">
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-calendar-alt me-2"></i>
                    <strong>Semester Period:</strong> 
                    <?= date('M d, Y', strtotime($semesterStart)) ?> - <?= date('M d, Y', strtotime($semesterEnd)) ?>
                    <br>
                    <small class="text-muted">Only dates within this period are displayed</small>
                </div>
            </div>
            
            <div id="calendar-container"></div>
            
            <center>
                <a href="javascript:history.back()" class="btn btn-primary">Back</a>
                <a href="book_hall.php?hall_id=<?php echo $hall['hall_id']; ?>
                    &type_id=<?php echo $hall['type_id']; ?>
                    &school_name=<?php echo urlencode($hall['school_name']); ?>
                    &department_name=<?php echo urlencode($hall['department_name']); ?>
                    &hall_name=<?php echo urlencode($hall['hall_name']); ?>" 
                    class="btn btn-primary">Book</a>
            </center>
            
            <div id="organizer-details-container" style="margin-top: 20px; display: flex; justify-content: center; ">
            <table id="organizer-details-table" border="1" style="display: none;" class="table-wrapper table-bordered">
                    <thead>
                        <tr>
                            <th style="width:40%">Organiser Details</th>
                            <th style="width:25%">Purpose</th>
                            <th style="width:10%">Status</th>
                            <th style="width:12.5%">Booked On</th>
                            <th style="width:12.5%">Booking ID</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    const calendarData = <?php echo $calendarJson; ?>;
    const currentDateTime = new Date('<?php echo $currentDateTime; ?>');
    const timeSlots = [
        {slot: 1, time: '09:30 AM'}, {slot: 2, time: '10:30 AM'}, {slot: 3, time: '11:30 AM'}, {slot: 4, time: '12:30 PM'},
        {slot: 5, time: '01:30 PM'}, {slot: 6, time: '02:30 PM'}, {slot: 7, time: '03:30 PM'}, {slot: 8, time: '04:30 PM'}
        // ,{slot: 9, time: '05:30 PM'}, {slot: 10, time: '06:30 PM'}, {slot: 11, time: '07:30 PM'}, {slot: 12, time: '08:30 PM'}, {slot: 13, time: '09:30 PM'}
    ];

    // Find the current month's index in calendarData
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-indexed
    
    // Find the index of the current month in calendarData
    let currentMonthIndex = 0;
    for (let i = 0; i < calendarData.length; i++) {
        if (calendarData[i].year === currentYear && calendarData[i].month === currentMonth) {
            currentMonthIndex = i;
            break;
        }
    }
    
    // If current month is not found (outside semester range), default to first month
    if (currentMonthIndex === 0 && (calendarData[0].year !== currentYear || calendarData[0].month !== currentMonth)) {
        // Check if current date is before semester start - show first month
        // Check if current date is after semester end - show last month
        const semesterStart = new Date('<?php echo $semesterStart; ?>');
        const semesterEnd = new Date('<?php echo $semesterEnd; ?>');
        
        if (currentDate < semesterStart) {
            currentMonthIndex = 0; // Show first month of semester
        } else if (currentDate > semesterEnd) {
            currentMonthIndex = calendarData.length - 1; // Show last month of semester
        }
    }

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
    <div class="time-slot-container">
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
                <div class="legend-color" style="background-color: rgb(255, 214, 102); border: 1px solid black;"></div>
                <span>Forward Booking</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(255, 103, 115); border: 1px solid black;"></div>
                <span>Booked</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: rgb(162, 162, 162); border: 1px solid black;"></div>
                <span>Past/Outside Semester</span>
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
                // Add white-cell for missing days
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
                if (period === 'PM' && hour !== 12) {
                    hour += 12;
                } else if (period === 'AM' && hour === 12) {
                    hour = 0;
                }

                // Ensure that slotDateTime is created in the same timezone as currentDateTime
                const formattedTime = `${String(hour).padStart(2, '0')}:${minutes}`;
                const slotDateTime = new Date(`${dayDate.toISOString().split('T')[0]}T${formattedTime}`);

                // Compare the slot date-time with the current date-time
                const currentDateTime = new Date(); // Make sure this is the correct current time
                let cellClass = '';
                const dayOfWeek = dayDate.getDay(); // 0 for Sunday, 6 for Saturday
                
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
                        let status = 'pending';
                        if (statuses.includes('approved')) {
                            status = 'approved';
                        } else if (statuses.includes('allow')) {
                            status = 'allow';
                        }

                        // Create tooltip text based on status
                        let tooltipText = '';
                        if (status === 'available') {
                            tooltipText = 'Available for Booking';
                        } else if (status === 'pending') {
                            // Show detailed organizer info for pending slots
                            const organiserDetails = bookedSlots.map(bs => bs.organiserDetails);
                            const names = organiserDetails.map(od => od.name || 'N/A').join(', ');
                            const departments = organiserDetails.map(od => od.department || 'N/A').join(', ');
                            tooltipText = `Pending \nName: ${names}\nDepartment: ${departments}`;
                        } else if (status === 'allow') {
                            // Show detailed organizer info for forward booking slots
                            const organiserDetails = bookedSlots.map(bs => bs.organiserDetails);
                            const names = organiserDetails.map(od => od.name || 'N/A').join(', ');
                            const departments = organiserDetails.map(od => od.department || 'N/A').join(', ');
                            tooltipText = `Pending To Forward Booking\nName: ${names}\nDepartment: ${departments}`;
                        } else if (status === 'approved') {
                            // Show detailed organizer info for approved slots
                            const organiserDetails = bookedSlots.map(bs => bs.organiserDetails);
                            const names = organiserDetails.map(od => od.name || 'N/A').join(', ');
                            const departments = organiserDetails.map(od => od.department || 'N/A').join(', ');
                            tooltipText = `Booked\nName: ${names}\nDepartment: ${departments}`;
                        } else if (status === 'past') {
                            tooltipText = 'Past/Outside Semester';
                        }

                        return `
                            <div class="calendar-cell ${status} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                               data-organiser='${JSON.stringify(bookedSlots.map(bs => ({...bs.organiserDetails, status: bs.status })))}'
                               title="${tooltipText}"
                                    onclick="showOrganizerDetails(this)">
                                </div>`;
                    } else {
                        cellClass = 'available';
                    }
                }

                // Add tooltip for available and past cells
                let tooltipText = '';
                if (cellClass === 'available') {
                    tooltipText = 'Available for Booking';
                } else if (cellClass === 'past') {
                    tooltipText = 'Cannot Book the Slots Outside the Semester Range';
                }

                return `
                    <div class="calendar-cell ${cellClass} ${dayOfWeek === 0 || dayOfWeek === 6 ? 'whitish-cell' : ''}" 
                        title="${tooltipText}">
                    </div>`;
            } else {
                return `<div class="calendar-cell white-cell"></div>`;
            }
        }).join('')}
    </div>
`).join('');

    return dateRow + timeRows;
}
let currentlyActiveCell = null;

function showOrganizerDetails(element) {
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
                       element.classList.contains('allow') ? 'allow' :
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
            const bookedOn = `${details.date || 'N/A'}`;
            const bookingId = `${details.id_gen || ''}`;

            const statusBadge = (() => {
                const s = (details.status || '').toString().toLowerCase();
                let label = s ? s.charAt(0).toUpperCase() + s.slice(1) : 'N/A';
                let color = '#6c757d';
                let textColor = '#fff';
                
                if (s === 'approved') {
                    color = '#dc3545';
                    textColor = '#fff';
                } else if (s === 'pending') {
                    color = '#ffc107';
                    textColor = '#000';
                } else if (s === 'allow') {
                    label = 'Forward Booking';
                    color = '#ffd666';
                    textColor = '#000';
                }
                
                return `<span style="display:inline-block;padding:2px 8px;border-radius:12px;background:${color};color:${textColor};font-weight:600;">${label}</span>`;
            })();
            row.innerHTML = `
                <td>${organiserInfo}</td>
                <td>${purposeInfo}</td>
                <td>${statusBadge}</td>
                <td>${bookedOn}</td>
                <td><span style="color:#0e00a3">${bookingId}</span></td>
            `;
            tableBody.appendChild(row);
        });

        document.getElementById('organizer-details-table').style.display = 'table';
        document.getElementById('organizer-details-heading').style.display = 'block';
        document.getElementById('booking').style.display = (status === 'approved' ? 'none' : 'block');
        checkAvailability();
    } else {
        document.getElementById('organizer-details-table').style.display = 'none';
        document.getElementById('organizer-details-heading').style.display = 'none';
        document.getElementById('booking').style.display = 'block'; // Default to showing booking form
        checkAvailability();
    }
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
    
    // Initial render - start with current month
    renderCalendar(calendarData, currentMonthIndex);
    </script>
</body>
</html>

</body>
</html>

