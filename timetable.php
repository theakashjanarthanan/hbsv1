<?php
include "assets/conn.php";
include 'assets/header.php';

// Check if employee_id exists in session
if (!isset($_SESSION['employee_id'])) {
    die("Error: Employee ID not found in session. Please log in again.");
}

$employee_id = $_SESSION['employee_id'];

// Fetch latest semester start and end date
$semesterQuery = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$semesterResult = mysqli_query($conn, $semesterQuery);

if (!$semesterResult) {
    die("Error fetching semester data: " . mysqli_error($conn));
}

$latestSemester = mysqli_fetch_assoc($semesterResult);

if (!$latestSemester) {
    die("Error: No semester data found. Please configure semester dates first.");
}

$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));


$slot_times = [
    1 => '09:30:00',
    2 => '10:30:00',
    3 => '11:30:00',
    4 => '12:30:00',
    5 => '13:30:00',
    6 => '14:30:00',
    7 => '15:30:00',
    8 => '16:30:00'
];

$sql = "
   SELECT b.*, h.hall_name, ht.type_name
FROM bookings b
JOIN hall_details h ON b.hall_id = h.hall_id
LEFT JOIN hall_type ht ON h.type_id = ht.type_id
WHERE b.organiser_id = ?
AND (
    b.status = 'approved'
    OR (b.status = 'pending' AND day_of_week IS NOT NULL)
)
AND (
    (b.day_of_week IS NULL AND b.start_date >= ? AND b.end_date <= ?)
    OR (b.day_of_week IS NOT NULL AND b.start_date <= ? AND b.end_date >= ?)
);
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

// Bind semester date range for filtering
$stmt->bind_param("issss", $employee_id, $semesterStart, $semesterEnd, $semesterEnd, $semesterStart);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error executing query: " . $stmt->error);
}

$events = [];
$dayNames = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 0];

while ($row = $result->fetch_assoc()) {
    $hall_name = $row['hall_name'] ?? 'Unknown Hall';
    $purpose = $row['purpose_name'] ?? 'No Purpose';
    $start_date = $row['start_date'];
    $end_date = $row['end_date'];
    $day_of_week = $row['day_of_week'];
    $status = $row['status'];

    // Ensure 'slot_or_session' field exists
    if (!isset($row['slot_or_session']) || empty($row['slot_or_session'])) {
        continue; // Skip entries without slots
    }

    $slot_array = array_filter(array_map('trim', explode(",", $row['slot_or_session'])));
    
    if (empty($slot_array)) {
        continue; // Skip if no valid slots
    }

    // Handle semester bookings (recurring weekly)
    if (!empty($day_of_week)) {
        $daysOfWeekArray = array_filter(array_map('trim', explode(",", $day_of_week)));
        
        foreach ($daysOfWeekArray as $dayName) {
            if (!isset($dayNames[$dayName])) {
                continue;
            }
            
            // Generate events for each week day within semester range
            // Start from the later of start_date or semesterStart
            $actualStartDate = max(strtotime($start_date), strtotime($semesterStart));
            $actualEndDate = min(strtotime($end_date), strtotime($semesterEnd));
            
            if ($actualStartDate > $actualEndDate) {
                continue; // Skip if outside semester range
            }
            
            // Find first occurrence of the day
            $dayNum = $dayNames[$dayName];
            $currentDate = $actualStartDate;
            
            // Find the first occurrence of this day of week on or after start date
            $dayOfWeekCurrent = (int)date('w', $currentDate);
            if ($dayOfWeekCurrent != $dayNum) {
                // Calculate days to add to reach the target day
                $daysToAdd = ($dayNum - $dayOfWeekCurrent + 7) % 7;
                if ($daysToAdd > 0) {
                    $currentDate = strtotime('+' . $daysToAdd . ' days', $currentDate);
                }
            }
            
            // Generate events for each occurrence of this day
            while ($currentDate <= $actualEndDate) {
                $dateString = date('Y-m-d', $currentDate);
                
                foreach ($slot_array as $slot) {
                    $slot = trim($slot);
                    if (!isset($slot_times[$slot])) {
                        continue;
                    }

                    $start_time = $dateString . "T" . $slot_times[$slot];
                    // Each slot is 1 hour long
                    $end_time = $dateString . "T" . date('H:i:s', strtotime($slot_times[$slot] . ' +1 hour'));

                    $events[] = [
                        'title' => $hall_name . " - " . $purpose,
                        'start' => $start_time,
                        'end' => $end_time,
                        'color' => $status === 'approved' ? '#28a745' : '#ffc107',
                        'extendedProps' => [
                            'status' => $status,
                            'isSemester' => true
                        ]
                    ];
                }
                
                // Move to next week
                $currentDate = strtotime('+7 days', $currentDate);
            }
        }
    } else {
        // Handle regular bookings (single or date range)
        $currentDate = strtotime($start_date);
        $endDateTimestamp = strtotime($end_date);
        
        while ($currentDate <= $endDateTimestamp) {
            $dateString = date('Y-m-d', $currentDate);
            
            foreach ($slot_array as $slot) {
                $slot = trim($slot);
                if (!isset($slot_times[$slot])) {
                    continue;
                }

                $start_time = $dateString . "T" . $slot_times[$slot];
                // Each slot is 1 hour long
                $end_time = $dateString . "T" . date('H:i:s', strtotime($slot_times[$slot] . ' +1 hour'));

                $events[] = [
                    'title' => $hall_name . " - " . $purpose,
                    'start' => $start_time,
                    'end' => $end_time,
                    'color' => $status === 'approved' ? '#007bff' : '#ffc107',
                    'extendedProps' => [
                        'status' => $status,
                        'isSemester' => false
                    ]
                ];
            }
            
            // Move to next day
            $currentDate = strtotime('+1 day', $currentDate);
        }
    }
}

// Remove any duplicate events
$uniqueEvents = [];
$seenKeys = [];
foreach ($events as $event) {
    $key = $event['start'] . '-' . $event['title'];
    if (!isset($seenKeys[$key])) {
        $uniqueEvents[] = $event;
        $seenKeys[$key] = true;
    }
}

$events_json = json_encode($uniqueEvents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Booking Calendar</title>
   
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <link rel="stylesheet" href="assets/design.css?v=2.0" />

    <style>
        .navbar-user-info {
            color: white;
            margin-right: 30px;
            display: inline;
        }

        .fc {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 10px;
        }

        .fc-day-header {
            background-color: #007bff !important;
            color: black !important;
            font-weight: bold;
            text-transform: uppercase;
            padding: 8px;

        }

        #calendar {
            height: 525px;
        }

        #main a {
            color: black !important;

        }

        /* 🔳 Add borders to each time slot */
        .fc-timegrid-slot {
            border-bottom: 1px solid #ddd !important;
            /* Light gray border */
        }

        .fc-daygrid-dot-event .fc-event-title {
            flex-grow: 1;
            flex-shrink: 1;
            min-width: 0;
            overflow: hidden;
            font-weight: 700;
            color: #007bff;
        }

        /* 🌟 Highlight the current day */
        .fc-day-today {
            background-color: rgba(255, 215, 0, 0.3) !important;
            /* Light yellow */
            border: 2px solid #ff9800 !important;
            /* Orange border */
        }

        /* 🖌 Add border around events */
        .fc-event {
            border: 1px solid #343a40 !important;
            /* Dark border */
            border-radius: 5px;
            font-size: 14px;
        }

        /* 📅 Month view: add border around dates */
        .fc-daygrid-day {
            border: 1px solid #ccc !important;
        }

        /* 🏷️ Style the title bar */
        .fc-toolbar-title {
            font-size: 20px;
            font-weight: bold;
            color: #2b00be;
        }

        /* ⏰ Style time labels */
        .fc-timegrid-slot-label {
            font-weight: bold;
            color: #007bff;
        }

        #calendar button {
            text-transform: capitalize;
        }

        .fc-event-time,
        .fc-timegrid-slot-label {
            text-transform: uppercase;
        }

        .fc th {
            height: 50px;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            color: darkblue !important;
            background-color: #f0f8ff;
        }
       /* Target FullCalendar day names */
.fc-col-header-cell a ,
.fc-daygrid-day-number {
    color: darkblue !important;
}


    </style>
</head>

<body>
    <div id="main">
        
        <!-- Timetable -->
        <div class="container mt-5">
            <br>
            
            <!-- Semester Range Banner -->
            <div class="semester-banner mb-3">
                <div class="alert alert-info text-center" role="alert">
                    <strong>Semester Period:</strong>
                    <?= date('M d, Y', strtotime($semesterStart)) ?> - <?= date('M d, Y', strtotime($semesterEnd)) ?>
                    <br>
                    <small class="text-muted">
                        <span style="color: #007bff;">■</span> Approved Bookings | 
                        <span style="color: #ffc107;">■</span> Pending Bookings | 
                        <span style="color: #28a745;">■</span> Approved Semester Bookings
                    </small>
                    <br>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="location.reload()" title="Refresh calendar data">
                        <i class="fa-solid fa-rotate-right"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div id="calendar"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            if (!calendarEl) {
                console.error("Calendar element not found! Check your HTML.");
                return;
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                firstDay: 1,
                allDaySlot: false,
                slotMinTime: "09:30:00",
                slotMaxTime: "17:30:00",
                slotDuration: "00:30:00",
                aspectRatio: 1.5,
                eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
                slotLabelFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
                eventMaxStack: 2,
                views: {
                    timeGridWeek: { dayMaxEvents: true, eventMinHeight: 20, displayEventTime: false },
                    timeGridDay: { dayMaxEvents: true, eventMinHeight: 20, displayEventTime: false },
                    dayGridMonth: { dayMaxEvents: true, eventinHeight: 20 }
                },
                events: <?php echo $events_json; ?>,
                eventDidMount: function(info) {
                    // Add tooltip with booking details
                    const status = info.event.extendedProps.status || 'unknown';
                    const isSemester = info.event.extendedProps.isSemester || false;
                    const statusText = status === 'approved' ? 'Approved' : 'Pending';
                    const semesterText = isSemester ? ' (Semester Booking)' : '';
                    info.el.setAttribute('title', info.event.title + ' - ' + statusText + semesterText);
                    
                    // Add visual indicator for pending bookings
                    if (status === 'pending') {
                        info.el.style.borderStyle = 'dashed';
                        info.el.style.opacity = '0.8';
                    }
                },
                datesSet: function (info) {
                    if (info.view.type === 'dayGridMonth') {
                        calendarEl.style.height = "700px"; // Increase height for month view
                    } else {
                        calendarEl.style.height = "535px"; // Default height for other views
                    }
                },
                loading: function(isLoading) {
                    if (isLoading) {
                        // Show loading indicator if needed
                        console.log('Loading calendar events...');
                    }
                },
                eventClick: function(info) {
                    // Optional: Add click handler for event details
                    const status = info.event.extendedProps.status || 'unknown';
                    const isSemester = info.event.extendedProps.isSemester ? 'Yes' : 'No';
                    alert(
                        'Booking Details:\n\n' +
                        'Title: ' + info.event.title + '\n' +
                        'Start: ' + info.event.start.toLocaleString() + '\n' +
                        'End: ' + info.event.end.toLocaleString() + '\n' +
                        'Status: ' + status + '\n' +
                        'Semester Booking: ' + isSemester
                    );
                    info.jsEvent.preventDefault();
                }
            });

            calendar.render();



        });



    </script>

</body>

</html>