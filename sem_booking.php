<?php
include('assets/conn.php'); // Include your database connection
include 'assets/header.php';

$id = $_SESSION['user_id']; // Logged-in user's ID
$role = $_SESSION['role']; // User role from session
$department_id = $_SESSION['department_id']; // User department ID from session

// Fetch seminar halls (type_id = 2)
$query = "SELECT * FROM hall_details WHERE department_id = ? AND type_id = 1 ORDER BY hall_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result_seminar = $stmt->get_result();

// Fetch lecture halls (type_id = 3)
$query = "SELECT * FROM hall_details WHERE department_id = ? AND type_id = 3 ORDER BY hall_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result_lecture = $stmt->get_result();

$stmt->close();

// Fetch latest semester start and end date for date restrictions
$semesterQuery = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$semesterResult = mysqli_query($conn, $semesterQuery);
$latestSemester = mysqli_fetch_assoc($semesterResult);

$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pondicherry University - Hall Booking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/design.css" />
    <style>

/* .sidenav {
    height: 100%;
    width: 0px;
    position: fixed;
    z-index: 1;
    top: 0;
    left: 0;
    background-color:rgb(0, 0, 0); 
    overflow-x: hidden;
    transition: 0.5s;
    padding-top: 150px;
}

.toggle-btn {
            position: fixed;
            top: 80px;
            left: 0px;
            background-color:rgb(0, 0, 0);
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 20px;
            cursor: pointer;
            border-radius: 0 5px 5px 0;
            z-index: 2;
            transition: 0.5s;
        }
        .toggle-btn.open {
            left: 0px;
        }
        #main {
      margin-left:0px;
      transition: margin-left .5s;
      padding: 16px;
      padding-top: 50px;
    } */

    #main {
      margin-left:250px;
      transition: margin-left .5s;
      padding: 16px;
      padding-top: 50px;
    }
        #split-view {
            display: flex;
            gap: 20px;
        }
        #cards-container {
            flex: 1;
        }
        #timetable-container {
            flex: 1;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        #timetable {
            width: 100%;
            border-collapse: collapse;
        }
        #timetable th, #timetable td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        #timetable th {
            background-color: #0e00a3;
            color: white;
        }
        #timetable td {
            background-color: white;
        }
        .card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(14, 0, 163, 0.15);
            border-color: #0e00a3;
        }
        .card-title {
            color: #0e00a3;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .card-icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: #0e00a3;
            opacity: 0.7;
        }
        .card-body {
            padding: 1.5rem;
            position: relative;
        }
        .hall-type-badge {
            background: linear-gradient(135deg, #0e00a3, #1976d2);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-bottom: 0.75rem;
        }
        .no-halls-message {
            text-align: center;
            padding: 3rem 2rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            margin: 1rem 0;
        }
        .no-halls-message i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .no-halls-message h5 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .no-halls-message p {
            color: #6c757d;
            margin-bottom: 0;
        }
        .table-wrapper {
            overflow-x: auto; /* Allow horizontal scrolling */
            max-width: 100%;
            padding: 20px;
        }

        #timetable-container {
            height:600px;
            overflow-y: auto;
}

#cards-container {
    overflow-y: auto;
            height:600px;
}
.card-body {
    padding: 15px;
}
#timetable {
        width: 100%;
        table-layout: fixed; /* Ensures equal width distribution */
    }

    #timetable th, #timetable td {
        text-align: center; /* Center align content */
        padding: 5px;
        white-space: nowrap; /* Prevent text wrapping */
    }
    #timetable td:first-child {
        font-size: 15px;

    }
    #timetable td:not(:first-child) {
    text-align: center; /* Center align horizontally */
    vertical-align: middle; /* Center align vertically */
    font-size: 10px; /* Reduce text size */
    font-weight: bold; /* Make text bold */
    text-transform: uppercase;
    cursor: pointer; /* Add pointer cursor */
    transition: all 0.3s ease; /* Smooth transition */
}

/* Disallow interactions on approved cells while in booking mode */
#timetable.booking-active td.approved-slot {
    /* cursor: not-allowed !important; */
    pointer-events: none; /* Prevent clicks */
    /* opacity: 0.2; */
    background-color:rgb(230, 205, 207) !important; /* Light red background */
    color: black !important; /* Black text */
    border: 2px solid #dc3545 !important; /* Red border */
    position: relative;
}

/* Add a visual indicator for approved slots */
#timetable.booking-active td.approved-slot::after {
    /* content: "🔒"; */
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 10px;
    opacity: 0.8;
}

/* Prevent hover effects on approved cells */
#timetable td.approved-slot:hover {
    background-color: #f8d7da !important;
    transform: none !important;
    box-shadow: none !important;
    /* border: 2px solid #dc3545 !important; */
    /* cursor: not-allowed !important;*/
} 

/* Hover effect for timetable slots (excluding approved slots) */
#timetable td:not(:first-child):not(.approved-slot):hover {
    background-color: #e3f2fd !important; /* Light blue background on hover */
    transform: scale(1.05); /* Slight scale effect */
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); /* Add shadow on hover */
    border: 2px solid #2196f3 !important; /* Blue border on hover */
}

/* Selected slot highlighting */
#timetable td:not(:first-child).selected-slot {
    background-color: #c8e6c9 !important; /* Light green background for selected slots */
    border: 2px solid #4caf50 !important; /* Green border for selected slots */
    font-weight: bold !important; /* Make text bold */
    color: #2e7d32 !important; /* Dark green text */
}

/* Disabled slot styling */
.form-check-input:disabled + .form-check-label {
    color: #6c757d !important; /*  Gray text for disabled labels */
    cursor: not-allowed !important;
}

.form-check-input:disabled {
    cursor: not-allowed !important;
    opacity: 0.5 !important;
}

/* Disabled day radio buttons */
input[name="day_of_week"]:disabled + label {
    color: #6c757d !important;
    cursor: not-allowed !important;
}

input[name="day_of_week"]:disabled {
    cursor: not-allowed !important;
    opacity: 0.5 !important;
}


    #timetable th:first-child, 
    #timetable td:first-child {
        width: 10%; /* Small fixed width for Day/Time column */
    }

    #timetable th:not(:first-child), 
    #timetable td:not(:first-child) {
        width: auto; /* Equal width for all time slots */
    }

#message{
    text-align: center; /* Center align horizontally */
    vertical-align: middle; /* Center align vertically */
    font-size: 18px; /* Reduce text size */
    font-weight: 600; 
    color: #b22222; /* Dark red */
} 
.card.active {
    border: 2px solid #0e00a3; 
    box-shadow: 0px 0px 10px rgba(14, 0, 163, 0.5); 
}
.hall-meta {
	background: #f4f6ff;
	border: 1px solid #e1e5ff;
	border-radius: 8px;
	padding: 8px 12px;
	color: #0e00a3;
	font-weight: 600;
	display: block;
	width: 100%;
}
.btn-enhanced {
	position: relative;
	transition: transform .15s ease, box-shadow .15s ease, background-color .2s ease;
}
.btn-enhanced:hover {
	transform: translateY(-1px);
	box-shadow: 0 6px 16px rgba(0,0,0,.15);
}
.btn-enhanced:active {
	transform: translateY(0);
	box-shadow: 0 3px 10px rgba(0,0,0,.12);
}
.btn-primary.btn-enhanced {
	background: linear-gradient(135deg, #1976d2, #0d47a1);
	border: 0;
}
.btn-primary.btn-enhanced:hover { filter: brightness(1.03); }
.btn-success.btn-enhanced { background: linear-gradient(135deg, #2e7d32, #1b5e20); border: 0; }
.btn-success.btn-enhanced:hover { filter: brightness(1.03); }
/* Default (for large screens) */
/* No changes needed here since you already have styles */

/* Medium Screens (992px and below) */
@media (max-width: 992px) { 
    #timetable-container, #cards-container {
        width: 100%;
    }
    #split-view {
        flex-direction: column;
    }
    #timetable {
        font-size: 14px;
    }
    #timetable td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    #timetable th, #timetable td {
        padding: 6px;
        font-size: 12px;
    }

    .card {
        font-size: 14px;
    }
    #cards-container {
        height: auto;
        overflow: visible;
    }
    .card-title {
        font-size: 16px;
    }
}

/* Small Screens (768px and below) */
@media (max-width: 768px) { 
    /* Stack elements vertically */
    #split-view {
        flex-direction: column;
    }

    #cards-container, #timetable-container {
        width: 100%;
    }

    #timetable-container {
        height: auto;
        overflow-x: auto;
        padding: 10px;
    }

    #timetable {
        font-size: 12px;
        width: 100%;
    }

    #timetable th, #timetable td {
        padding: 5px;
        font-size: 10px;
    }

    /* Prevent table text from breaking */
    #timetable td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Adjust card layout */
    .card {
        padding: 10px;
        font-size: 12px;
    }

    .card-title {
        font-size: 14px;
    }

    .card-body {
        padding: 10px;
    }

    #cards-container {
        height: auto;
        overflow: visible;
    }
}

/* Very Small Screens (480px and below) */
@media (max-width: 480px) { 
    #timetable {
        font-size: 10px;
    }

    #timetable th, #timetable td {
        padding: 4px;
        font-size: 9px;
    }

    .card {
        font-size: 10px;
        padding: 8px;
    }

    .card-title {
        font-size: 12px;
    }

    .card-body {
        padding: 8px;
    }
}

</style>

</head>
<body>
<div id="main">
    <div class="table-wrapper mt-4">
    <div class="col-md-12">
        <div id="split-view" class="row">
            <!-- Left Side: Cards -->
            <div id="cards-container">
                <br>
                <br>
                <h5><b style="color: #0e00a3">Seminar Halls</b></h5>
                <div class="row">
                    <?php 
                    $seminar_count = 0;
                    while ($row = $result_seminar->fetch_assoc()) { 
                        $seminar_count++;
                    ?>
                        <div class="col-md-4 mb-4 d-flex">
                            <div class="card h-100 w-100" onclick="highlightCard(this); showTimetable('<?php echo $row['hall_id']; ?>', '<?php echo $row['hall_name']; ?>')">
                                <div class="card-body d-flex flex-column">
                                    <!-- <div class="hall-type-badge">Seminar Hall</div> -->
                                    <!-- <i class="fas fa-chalkboard-teacher card-icon"></i> -->
                                    <h6 class="card-title"><?php echo $row['hall_name']; ?></h6>
                                    <p class="card-text small mt-auto">
                                        <i class="fas fa-users me-1"></i>Capacity: <?php echo $row['capacity']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php } 
                    if ($seminar_count == 0) { ?>
                        <div class="col-12">
                            <div class="no-halls-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h5>No Seminar Halls Available</h5>
                                <p>There are currently no seminar halls available for your department.</p>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <br><h5><b style="color: #0e00a3">Class Rooms</b></h5>
                <div class="row">
                    <?php 
                    $lhc_halls = []; // Store Lecture Hall Complex halls separately
                    $classroom_count = 0;
                    while ($row = $result_lecture->fetch_assoc()) { 
                        if (strpos($row['hall_name'], 'LHC') === 0) {
                            $lhc_halls[] = $row; // Store LHC halls for later
                        } else { 
                            $classroom_count++;
                    ?>
                            <div class="col-md-3 mb-4 d-flex">
                                <div class="card h-100 w-100" onclick="highlightCard(this); showTimetable('<?php echo $row['hall_id']; ?>', '<?php echo $row['hall_name']; ?>')">
                                    <div class="card-body">
                                        <!-- <div class="hall-type-badge">Class Room</div> -->
                                        <!-- <i class="fas fa-door-open card-icon"></i> -->
                                        <h6 class="card-title"><?php echo $row['hall_name']; ?></h6>
                                        <p class="card-text small">
                                            <i class="fas fa-users me-1"></i>Capacity: <?php echo $row['capacity']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                    <?php 
                        } 
                    } 
                    if ($classroom_count == 0) { ?>
                        <div class="col-12">
                            <div class="no-halls-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h5>No Class Rooms Available</h5>
                                <p>There are currently no class rooms available for your department.</p>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <?php if (!empty($lhc_halls)) { ?>
                    <br><h5><b style="color: #0e00a3">Lecture Hall Complex</b></h5>
                    <div class="row">
                        <?php foreach ($lhc_halls as $row) { ?>
                            <div class="col-md-3 mb-4 d-flex">
                                <div class="card h-100 w-100" onclick="highlightCard(this); showTimetable('<?php echo $row['hall_id']; ?>', '<?php echo $row['hall_name']; ?>')">
                                    <div class="card-body">
                                        <!-- <div class="hall-type-badge">Lecture Hall</div> -->
                                        <!-- <i class="fas fa-university card-icon"></i> -->
                                        <h6 class="card-title"><?php echo $row['hall_name']; ?></h6>
                                        <p class="card-text small">
                                            <i class="fas fa-users me-1"></i>Capacity: <?php echo $row['capacity']; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <br><h5><b style="color: #0e00a3">Lecture Hall Complex</b></h5>
                    <div class="row">
                        <div class="col-12">
                            <div class="no-halls-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h5>No Lecture Hall Complex Available</h5>
                                <p>There are currently no lecture hall complex halls available for your department.</p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- Right Side: Timetable (Initially Hidden) -->
             
           <!-- Right Side: Timetable & Form -->
           <div id="timetable-container">
           <div class="col-md-12">
                <div id="hall-details" class="hall-meta" style="display:none;"> </div>
                <div class="d-flex justify-content-center gap-2 mt-2" id="hall-actions">
                    <a id="view-button" href="#" class="btn btn-success btn-enhanced" style="display: none; padding: 6px 12px;">View Timetable</a>
                    <button id="open-booking-button" type="button" class="btn btn-primary btn-enhanced" style="display: none; padding: 6px 12px;">
                        <span id="booking-button-text">Book the Hall for Semester Booking</span>
                        <span id="booking-button-spinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                </div>
 
 </div>
    <table class="table table-bordered mt-2" id="timetable">
        <thead>
            <tr>
                <th>Day</th>
                <?php
                $times = ["1", "2", "3", "4", "5", "6", "7", "8"];
                foreach ($times as $time) {
                    echo "<th>{$time}</th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $days = ["Mon", "Tue", "Wed", "Thu", "Fri"];
            foreach ($days as $day) {
                echo "<tr>";
                echo "<td><b>{$day}</b></td>"; // First column for day
                foreach ($times as $time) {
                    echo "<td></td>"; // Empty slots to be filled dynamically
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Semester Range Banner (below timetable) -->
    <div class="semester-banner mb-3">
        <div class="alert alert-info text-center" role="alert">
            <strong>Semester Period:</strong>
            <?= date('M d, Y', strtotime($semesterStart)) ?> - <?= date('M d, Y', strtotime($semesterEnd)) ?>
            <br>
            <small class="text-muted">
                Only dates within this period are available for semester booking.<br>
                Please contact the administrator to request an extension.
            </small>
        </div>
    </div>
    
    <!-- Loading Screen for Booking Mode -->
    <div id="booking-mode-loader" style="display:none; text-align:center; padding:20px;">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 class="text-primary">Preparing Booking Form...</h5>
        <p class="text-muted">Please wait while we set up the booking form...</p>
    </div>
    
    <!-- Message -->
    <div id="message" class="alert text-center" style="display: block;">
        Click on a hall name to view its Timetable.
    </div>


    <!-- Conflict Modal -->
    <div id="conflict-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background:#fff; width: 92%; max-width: 560px; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.2);">
            <div style="padding: 14px 18px; background:#0e00a3; color:#fff; display:flex; align-items:center; gap:10px;">
                <div style="font-size:18px; font-weight:600;">Slot Selection Issue</div>
            </div>
            <div style="padding: 18px 18px 10px 18px;">
                <p style="margin-bottom:8px; color:#333; font-weight:500;">Some selected slots cannot be booked:</p>
                <ul id="conflict-errors" style="margin:0; padding-left: 20px; color:#444;"></ul>
            </div>
            <div style="padding: 12px 18px 16px 18px; display:flex; gap:10px; justify-content:flex-end; background:#f8f9fa;">
                <button type="button" id="conflict-reset" class="btn btn-danger" style="padding: 6px 14px; font-weight:600;">Reset Selection</button>
            </div>
        </div>
    </div>
 
    <div id="booking" style="display:none;">

    <!-- Loader Spinner -->
    <div id="slot-loader" style="display:none; text-align:center; padding:20px;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Checking Slot Availability...</p>
    </div>

    <!-- Slot Status Display -->
    <div id="slot-status-display" style="display:none;">
        <div id="approved-slot-info" style="isplay:none; text-align:center; width:100%; margin: 0 auto;">
            <h5 class="text-success mb-3">The Slot is already booked for a Semester</h5>
            <table class="table table-bordered">
                <tbody id="approved-slot-details">
                    <!-- Approved slot details will be populated here -->
                </tbody>
            </table>
        </div>
        
        <div id="available-slot-info" style="display:none; text-align:center; width:100%; margin: 0 auto;">
          <h5 class="text-primary mb-3">The Slot is available for Semester Booking</h5>
        </div>

    </div>

    <!-- Booking Form -->
    <form action="save_schedule.php" method="POST" id="booking-form">

    <!-- Semester Start & End Date in the same row -->
    <div class="mb-3 d-flex gap-3">
    <?php
$today = date('Y-m-d'); // Get the current date
$semesterStartDate = isset($latestSemester['start_date']) && $latestSemester['start_date'] >= $today ? $latestSemester['start_date'] : $today;
?>

<div class="w-50">
    <label for="start_date" class="form-label">Select Date:</label>
    <!-- <small class="text-muted">Adjusted to the Current Date</small> -->
    <input type="date" id="start_date" name="start_date" class="form-control" 
           value="<?= $semesterStartDate ?>" 
           min="<?= max($semesterStart, $today) ?>" 
           max="<?= $semesterEnd ?>" 
           onclick="this.showPicker()" 
           onkeydown="return false" 
           required>
</div>

        <div class="w-50">
            <label for="end_date" class="form-label">Semester End Date:</label>
            <input type="date" id="end_date" name="end_date" class="form-control" 
                   value="<?= $latestSemester['end_date'] ?? '' ?>" 
                   min="<?= max($semesterStart, $today) ?>" 
                   max="<?= $semesterEnd ?>" 
                   onclick="this.showPicker()" 
                   onkeydown="return false" 
                   required>
        </div>
    </div>

    <!-- Day of the Week as Radio Buttons in one line -->
    <div class="mb-3">
        <label class="form-label">Select Day:</label>
        <div class="d-flex gap-3">
            <?php
            $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
            foreach ($days as $day) {
                echo "<div class='form-check'>
                        <input class='form-check-input' type='radio' name='day_of_week' id='$day' value='$day' required>
                        <label class='form-check-label' for='$day'>$day</label>
                      </div>";
            }
            ?>
        </div>
    </div>

    <!-- Time Slots as Checkboxes (4 per row) -->
    <div class="mb-3">
    <label class="form-label">Select Time Slots:</label>
    <div class="d-flex flex-wrap justify-content-start gap-3">
        <?php
        $times = ["9:30 AM", "10:30 AM", "11:30 AM", "12:30 PM", "1:30 PM", "2:30 PM", "3:30 PM", "4:30 PM"];
        foreach ($times as $index => $time) {
            $value = $index+1;
            echo "<div class='form-check' style='width: 20%;'>
                    <input class='form-check-input' type='checkbox' name='slots[]' id='slot$index' value='$value'>
                    <label class='form-check-label' for='slot$index'>$time</label>
                  </div>";
        }
        ?>
    </div>
</div>
<div id="slot_error" style="color: red; display: none;"></div>

    </div>
    <div id="slot_error" style="color: red; display: nonse;"></div>

    <input type="hidden" name="hall_id" id="selected_hall">
    <input type="hidden" name="user_id" value="<?php echo isset($user_id) ? $user_id : ''; ?>">
    
    
    <div id="organiser_details" style="display: none;">

    <input type="hidden" name="purpose" value="class">
    <div class="form-label" id="class-field">
        <label for="event_type">Class :</label>
        <input type="text" name="event_type" id="event_type" class="form-control" required>
    </div>
    <div class="form-label" id="course-code-field">
        <label for="purpose_name">Course Code :</label>
        <input type="text" name="purpose_name" id="purpose_name" class="form-control" required>
    </div>



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
?>
<div class="mb-3 position-relative" id="faculty-department-field">
    <label for="organiser_department" class="form-label">Faculty's Department</label>
    <input type="text" class="form-control" id="organiser_department" name="organiser_department"
           value="<?php echo htmlspecialchars($department_name); ?>" required
           oninput="suggestDepartments(this.value)" 
           onclick="suggestDepartments(this.value)"
           onfocus="suggestDepartments(this.value)">
    <ul id="department_suggestions" class="list-group position-absolute w-100" style="display: none;"></ul>
</div>

<div class="mb-3" id="faculty-name-field">
    <label for="organiser_name" class="form-label">Faculty's Name</label>
    <input type="text" class="form-control" id="organiser_name" name="organiser_name" placeholder="Type to search..." required
           onclick="triggerFacultySearch()" onfocus="triggerFacultySearch()">
    <ul id="suggestions" class="list-group" style="display: none; "></ul>
</div>

<div class="mb-3" id="faculty-mobile-field">
    <label for="organiser_mobile" class="form-label">Faculty's Contact Number</label>
    <input type="text" class="form-control" id="organiser_mobile" name="organiser_mobile"  readonly required>
</div>

<div class="mb-3" id="faculty-email-field">
    <label for="organiser_email" class="form-label">Faculty's Email ID</label>
    <input type="text" class="form-control" id="organiser_email" name="organiser_email"  readonly required>
</div>



<input type="hidden" id="employee_id" name="employee_id">





    <div class="d-flex justify-content-center gap-3">
        <button type="submit" class="btn btn-primary" id="book-slot-button">
            <span id="book-text">Book Slot</span>
            <span id="book-spinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
        </button>
        <button type="button" class="btn btn-secondary" id="cancel-booking-button">Cancel</button>
    </div>
    </div>
</form>

</div>
</div>
<script>
// Ensure conflict modal helpers exist globally before any usage
window.openConflictModal = window.openConflictModal || function(errors) {
    try { console.log('[Conflict] Opening dialog with errors:', errors); } catch(e) {}
    const modal = document.getElementById('conflict-modal');
    const list = document.getElementById('conflict-errors');
    if (!modal || !list) return;
    list.innerHTML = '';
    (Array.isArray(errors) ? errors : [String(errors || 'Unknown error')]).forEach(msg => {
        const li = document.createElement('li');
        li.textContent = msg;
        list.appendChild(li);
    });
    modal.style.display = 'flex';
};

window.closeConflictModal = window.closeConflictModal || function() {
    const modal = document.getElementById('conflict-modal');
    if (modal) modal.style.display = 'none';
};

window.resetSelectedSlots = window.resetSelectedSlots || function() {
    try { console.log('[Conflict] Resetting selected slots'); } catch(e) {}
    document.querySelectorAll('input[name="slots[]"]').forEach(cb => cb.checked = false);
    if (typeof selectedSlotsState !== 'undefined') {
        selectedSlotsState = [];
    }
    const errorBox = document.getElementById('slot_error');
    if (errorBox) errorBox.style.display = 'none';
    window.closeConflictModal();
};

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("#timetable tbody tr td:not(:first-child)").forEach(cell => {
        cell.addEventListener("click", function () {
            // Prevent clicks on approved slots when in booking mode
            if (bookingMode && this.classList.contains('approved-slot')) {
                return; // Do nothing if it's an approved slot in booking mode
            }
            
            let row = this.parentElement;
            let dayShort = row.querySelector("td b").innerText.trim(); // Extract "Fri", "Mon", etc.
            
            // Map short day names to full names
            let dayMap = {
                "Mon": "Monday",
                "Tue": "Tuesday",
                "Wed": "Wednesday",
                "Thu": "Thursday",
                "Fri": "Friday"
            };
 
            let dayFull = dayMap[dayShort] || dayShort; // Convert to full name
            let timeIndex = Array.from(row.children).indexOf(this) - 1;
            let times = ["9:30 AM", "10:30 AM", "11:30 AM", "12:30 PM", "1:30 PM", "2:30 PM", "3:30 PM", "4:30 PM"];
 
             if (timeIndex >= 0 && timeIndex < times.length) {
                 // Show loader
                 showSlotLoader();
                 
                 // Check slot availability after 2 seconds
                 setTimeout(() => {
                     checkSlotAvailability(dayFull, timeIndex + 1, this);
                 }, 2000);
             }
         });
     });

    // Bind conflict modal buttons
    const resetBtn = document.getElementById('conflict-reset');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            try { this.disabled = true; this.textContent = 'Resetting....'; } catch(e) {}
            window.resetSelectedSlots();
            window.location.reload();
        });
    }
 });

var bookingMode = false;

function showSlotLoader() {
    // Hide all other elements
    document.getElementById('slot-status-display').style.display = 'none';
    document.getElementById('booking-form').style.display = 'none';
    
    // Show loader
    document.getElementById('slot-loader').style.display = 'block';
}

function checkSlotAvailability(dayFull, slotNumber, clickedCell) {
    const hallId = document.getElementById("selected_hall").value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    
    if (!hallId || !startDate || !endDate) {
        hideLoader();
        alert('Please select a hall and date range first');
        return;
    }
    
    // Fetch detailed slot information from server
    fetch(`get_slot_details.php?hall_id=${hallId}&day_of_week=${dayFull}&slot_number=${slotNumber}&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                try { console.warn('[SlotCheck] Non-JSON response, treating as available:', text); } catch(_) {}
                // Gracefully handle as available slot depending on mode
                if (bookingMode) {
                    hideLoader();
                } else {
                    document.getElementById('slot-loader').style.display = 'none';
                    document.getElementById('approved-slot-info').style.display = 'none';
                    document.getElementById('booking-form').style.display = 'none';
                    document.getElementById('slot-status-display').style.display = 'block';
                    document.getElementById('available-slot-info').style.display = 'block';
                }
                return;
            }
            if (data.status === 'approved') {
                // Slot is booked
                if (bookingMode) {
                    // In booking mode, show conflict modal and allow reset
                    hideLoader();
                    try { console.log('[Validation] Clicked a booked slot during booking mode:', { day: dayFull, slot: slotNumber }); } catch(e) {}
                    window.openConflictModal('Selected slot is already booked. Please reset and choose different slots.');
                } else {
                    // Outside booking mode, show booked details
                    showApprovedSlotInfo(dayFull, slotNumber, data.booking, hallId, startDate, endDate);
                }
            } else {
                // Slot is available
                if (bookingMode) {
                    // In booking mode, allow opening the booking form
                    showAvailableSlotInfo(dayFull, slotNumber);
                } else {
                    // Before booking mode: show availability message without showing the booking form
                    document.getElementById('slot-loader').style.display = 'none';
                    document.getElementById('approved-slot-info').style.display = 'none';
                    document.getElementById('booking-form').style.display = 'none';
                    document.getElementById('slot-status-display').style.display = 'block';
                    document.getElementById('available-slot-info').style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error checking slot availability:', error);
            // Treat as available before booking; otherwise, hide loader silently
            if (bookingMode) {
                hideLoader();
            } else {
                document.getElementById('slot-loader').style.display = 'none';
                document.getElementById('approved-slot-info').style.display = 'none';
                document.getElementById('booking-form').style.display = 'none';
                document.getElementById('slot-status-display').style.display = 'block';
                document.getElementById('available-slot-info').style.display = 'block';
            }
        });
}

function showApprovedSlotInfo(dayFull, slotNumber, bookingData, hallId, startDate, endDate) {
    // Hide loader and booking form
    document.getElementById('slot-loader').style.display = 'none';
    document.getElementById('booking-form').style.display = 'none';
    
    // Hide specific booking form fields
    document.getElementById('class-field').style.display = 'none';
    document.getElementById('course-code-field').style.display = 'none';
    document.getElementById('faculty-department-field').style.display = 'none';
    document.getElementById('faculty-name-field').style.display = 'none';
    document.getElementById('faculty-mobile-field').style.display = 'none';
    document.getElementById('faculty-email-field').style.display = 'none';
    document.getElementById('book-slot-button').style.display = 'none';
    
    // Show approved slot info
    document.getElementById('slot-status-display').style.display = 'block';
    document.getElementById('approved-slot-info').style.display = 'block';
    document.getElementById('available-slot-info').style.display = 'none';
    
    // Populate approved slot details with actual booking data
    const detailsTable = document.getElementById('approved-slot-details');
    detailsTable.innerHTML = `
        <tr>
            <td><strong>Semester Start Date:</strong></td>
            <td>${bookingData.start_date}</td>
        </tr>
        <tr>
            <td><strong>Semester End Date:</strong></td>
            <td>${bookingData.end_date}</td>
        </tr>
        <tr>
            <td><strong>Select Date:</strong></td>
            <td>${bookingData.day_of_week}</td>
        </tr>
        <tr>
            <td><strong>Class:</strong></td>
            <td>${bookingData.event_type || 'N/A'}</td>
        </tr>
        <tr>
            <td><strong>Course Code:</strong></td>
            <td>${bookingData.purpose_name || 'N/A'}</td>
        </tr>
        <tr>
            <td><strong>Faculty's Department:</strong></td>
            <td>${bookingData.organiser_department || 'N/A'}</td>
        </tr>
        <tr>
            <td><strong>Faculty's Name:</strong></td>
            <td>${bookingData.organiser_name || 'N/A'}</td>
        </tr>
        <tr>
            <td><strong>Faculty's Contact Number:</strong></td>
            <td>${bookingData.organiser_mobile || 'N/A'}</td>
        </tr>
        <tr>
            <td><strong>Faculty's Email ID:</strong></td>
            <td>${bookingData.organiser_email || 'N/A'}</td>
        </tr>
    `;
}

function showAvailableSlotInfo(dayFull, slotNumber) {
    // Hide loader and approved slot info
    document.getElementById('slot-loader').style.display = 'none';
    document.getElementById('approved-slot-info').style.display = 'none';
    
    // Set booking mode to true
    bookingMode = true;
    
    // Add booking-active class to timetable to enable approved slot restrictions
    document.getElementById('timetable').classList.add('booking-active');
    
    // Show all booking form fields
    document.getElementById('class-field').style.display = 'block';
    document.getElementById('course-code-field').style.display = 'block';
    document.getElementById('faculty-department-field').style.display = 'block';
    document.getElementById('faculty-name-field').style.display = 'block';
    document.getElementById('faculty-mobile-field').style.display = 'block';
    document.getElementById('faculty-email-field').style.display = 'block';
    document.getElementById('book-slot-button').style.display = 'block';
    
    // Show the booking container and organizer details
    document.getElementById('booking').style.display = 'block';
    document.getElementById('organiser_details').style.display = 'block';
    
    // Show available slot info and booking form
    document.getElementById('slot-status-display').style.display = 'block';
    document.getElementById('available-slot-info').style.display = 'block';
    document.getElementById('booking-form').style.display = 'block';
    
    // Hide the message
    document.getElementById('message').style.display = 'none';
    
    // Auto-select the day and slot
    let dayRadio = document.querySelector(`input[name='day_of_week'][value='${dayFull}']`);
    if (dayRadio) {
        dayRadio.checked = true;
    }
    
    let timeCheckbox = document.querySelector(`input[name='slots[]'][value='${slotNumber}']`);
    if (timeCheckbox) {
        timeCheckbox.checked = true;
    }
    
    // Trigger slot checking
    checkSlots();
    
    // Highlight selected slots in timetable
    setTimeout(highlightSelectedSlots, 100);
    
    // Disable booked slots
    setTimeout(disableBookedSlots, 200);
    
    // Scroll to booking form
    document.getElementById('booking').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideLoader() {
    document.getElementById('slot-loader').style.display = 'none';
}

// Function to highlight selected slots in timetable
function highlightSelectedSlots() {
    // Clear all existing highlights first
    document.querySelectorAll('#timetable td.selected-slot').forEach(cell => {
        cell.classList.remove('selected-slot');
    });
    
    // Get selected day and slots
    const selectedDay = document.querySelector('input[name="day_of_week"]:checked');
    const selectedSlots = document.querySelectorAll('input[name="slots[]"]:checked');
    
    if (selectedDay && selectedSlots.length > 0) {
        const dayValue = selectedDay.value;
        const dayMap = {
            "Monday": 1,
            "Tuesday": 2, 
            "Wednesday": 3,
            "Thursday": 4,
            "Friday": 5
        };
        
        const rowIndex = dayMap[dayValue];
        if (rowIndex) {
            selectedSlots.forEach(slot => {
                const slotNumber = parseInt(slot.value);
                const cell = document.querySelector(`#timetable tbody tr:nth-child(${rowIndex}) td:nth-child(${slotNumber + 1})`);
                if (cell) {
                    cell.classList.add('selected-slot');
                }
            });
        }
    }
}

// Function to clear slot highlights
function clearSlotHighlights() {
    document.querySelectorAll('#timetable td.selected-slot').forEach(cell => {
        cell.classList.remove('selected-slot');
    });
}

// Function to disable booked slots based on selected day
function disableBookedSlots() {
    const hallId = document.getElementById('selected_hall').value;
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const selectedDay = document.querySelector('input[name="day_of_week"]:checked');
    
    if (!hallId || !startDate || !endDate) {
        return;
    }
    
    // First, enable all slots
    document.querySelectorAll('input[name="slots[]"]').forEach(slot => {
        slot.disabled = false;
        slot.title = '';
    });
    document.querySelectorAll('input[name="day_of_week"]').forEach(day => {
        day.disabled = false;
    });
    
    // If no day is selected, don't disable any slots
    if (!selectedDay) {
        return;
    }
    
    const selectedDayValue = selectedDay.value;
    
    // Fetch booked slots for the current hall and date range
    fetch(`fetch_timetable.php?hall_id=${hallId}&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.text())
        .then(data => {
            try {
                const jsonData = JSON.parse(data);
                if (jsonData.error) {
                    console.error('Error from server:', jsonData.error);
                    return;
                }
                
                // Check if the selected day has any bookings
                if (jsonData[selectedDayValue]) {
                    const dayBookings = jsonData[selectedDayValue];
                    
                    // Disable specific time slots that are booked for the selected day
                    for (const [slot, purpose] of Object.entries(dayBookings)) {
                        if (purpose && purpose.trim() !== '') {
                            const slotNumber = parseInt(slot);
                            const slotCheckbox = document.querySelector(`input[name="slots[]"][value="${slotNumber}"]`);
                            if (slotCheckbox) {
                                slotCheckbox.disabled = true;
                                slotCheckbox.title = `This slot is already booked on ${selectedDayValue}: ${purpose}`;
                            }
                        }
                    }
                }
                
            } catch (error) {
                console.error("Error parsing timetable data:", error);
            }
        })
        .catch(error => {
            console.error("Error fetching timetable:", error);
        });
}
</script>


<script>

document.querySelectorAll('input[name="slots[]"]').forEach(slot => {
                slot.addEventListener('click', function () {
                    // Allow individual slot selection - no auto-selection
                    // Highlight selected slots in timetable
                    setTimeout(highlightSelectedSlots, 100);
                });
            });


    document.querySelector('form').addEventListener('submit', function(e) {
    // Check if at least one checkbox is selected
    const selectedSlots = document.querySelectorAll('input[name="slots[]"]:checked');
    if (selectedSlots.length === 0) {
        // Prevent form submission
        e.preventDefault();
        
        // Show error message
        document.getElementById('slot_error').innerHTML = "Please select at least one time slot.";
        document.getElementById('slot_error').style.display = 'block';
    } else {
        // Hide error message if slots are selected
        document.getElementById('slot_error').style.display = 'none';
        
        // Show loading spinner for 2 seconds
        const bookButton = document.getElementById('book-slot-button');
        const bookText = document.getElementById('book-text');
        const bookSpinner = document.getElementById('book-spinner');
        
        bookButton.disabled = true;
        bookText.textContent = 'Booking...';
        bookSpinner.style.display = 'inline-block';
        
        // After 2 seconds, allow form submission
        setTimeout(() => {
            bookButton.disabled = false;
            bookText.textContent = 'Book Slot';
            bookSpinner.style.display = 'none';
        }, 2000);
    }
});

// Cancel button functionality
document.getElementById('cancel-booking-button').addEventListener('click', function() {
    // Reset booking mode
    bookingMode = false;
    
    // Remove booking-active class to re-enable approved slot interactions
    document.getElementById('timetable').classList.remove('booking-active');
    
    // Hide booking form and related elements
    document.getElementById('booking').style.display = 'none';
    document.getElementById('booking-form').style.display = 'none';
    document.getElementById('organiser_details').style.display = 'none';
    document.getElementById('slot-status-display').style.display = 'none';
    document.getElementById('slot-loader').style.display = 'none';
    
    // Show message
    document.getElementById('message').style.display = 'block';
    
    // Clear form fields
    document.querySelector('form').reset();
    
    // Clear any error messages
    document.getElementById('slot_error').style.display = 'none';
    
    // Clear selected slots
    document.querySelectorAll('input[name="slots[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="day_of_week"]').forEach(radio => radio.checked = false);
    
    // Clear slot highlights in timetable
    clearSlotHighlights();
});
document.addEventListener('DOMContentLoaded', () => {
    // Assuming your date inputs have the following IDs
    const startDateInput = document.getElementById('start_date');
    const endDateInput   = document.getElementById('end_date');

    // Attach event listeners to both date inputs
    startDateInput.addEventListener('change', updateTimetable);
    endDateInput.addEventListener('change', updateTimetable);
});

function updateTimetable() {
    // Retrieve stored hall information
    const hallId = document.getElementById("selected_hall").value;
    const hallName = document.getElementById("hall-details").textContent; // Adjust as necessary
    
    // Store current booking mode state
    const wasInBookingMode = bookingMode;

    if (hallId) {
        showTimetable(hallId, hallName);
        
        // Restore booking mode if it was active before
        if (wasInBookingMode) {
            bookingMode = true;
            
            // Add booking-active class to timetable to maintain approved slot restrictions
            document.getElementById('timetable').classList.add('booking-active');
            
            document.getElementById('booking').style.display = 'block';
            document.getElementById('message').style.display = 'none';
            document.getElementById('slot-loader').style.display = 'none';
            document.getElementById('slot-status-display').style.display = 'none';
            document.getElementById('booking-form').style.display = 'block';
            
            // Explicitly show all organizer details fields
            const organiser = document.getElementById('organiser_details');
            if (organiser) organiser.style.display = 'block';
            
            // Explicitly show all individual input fields
            document.getElementById('class-field').style.display = 'block';
            document.getElementById('course-code-field').style.display = 'block';
            document.getElementById('faculty-department-field').style.display = 'block';
            document.getElementById('faculty-name-field').style.display = 'block';
            document.getElementById('faculty-mobile-field').style.display = 'block';
            document.getElementById('faculty-email-field').style.display = 'block';
            document.getElementById('book-slot-button').style.display = 'block';
            
            // Disable booked slots
            setTimeout(disableBookedSlots, 100);
        }
    }
}
    function showTimetable(hallId, hallName) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        if (hallName) {
            // Update action buttons
            document.getElementById("view-button").href = `view_cal.php?hall_id=${hallId}`;
            document.getElementById("view-button").style.display = "block"; // Show button
            const openBookingBtn = document.getElementById('open-booking-button');
            if (openBookingBtn) {
                openBookingBtn.style.display = "inline-block";
                if (!openBookingBtn.dataset.bound) {
                    openBookingBtn.addEventListener('click', function() {
                        // Show loading spinner
                        const buttonText = document.getElementById('booking-button-text');
                        const buttonSpinner = document.getElementById('booking-button-spinner');
                        const bookingModeLoader = document.getElementById('booking-mode-loader');
                        
                        openBookingBtn.disabled = true;
                        buttonText.textContent = 'Loading...';
                        buttonSpinner.style.display = 'inline-block';
                        
                        // Show the loading screen below semester banner
                        bookingModeLoader.style.display = 'block';
                        
                        // After 2 seconds, show the booking form
                        setTimeout(() => {
                            bookingMode = true;
                            
                            // Add booking-active class to timetable to enable approved slot restrictions
                            document.getElementById('timetable').classList.add('booking-active');
                            
                            document.getElementById('booking').style.display = 'block';
                            document.getElementById('message').style.display = 'none';
                            document.getElementById('slot-loader').style.display = 'none';
                            document.getElementById('slot-status-display').style.display = 'none';
                            document.getElementById('booking-form').style.display = 'block';
                            
                            // Explicitly show all organizer details fields
                            const organiser = document.getElementById('organiser_details');
                            if (organiser) organiser.style.display = 'block';
                            
                            // Explicitly show all individual input fields
                            document.getElementById('class-field').style.display = 'block';
                            document.getElementById('course-code-field').style.display = 'block';
                            document.getElementById('faculty-department-field').style.display = 'block';
                            document.getElementById('faculty-name-field').style.display = 'block';
                            document.getElementById('faculty-mobile-field').style.display = 'block';
                            document.getElementById('faculty-email-field').style.display = 'block';
                            document.getElementById('book-slot-button').style.display = 'block';
                            
                            // Disable booked slots
                            setTimeout(disableBookedSlots, 100);
                            
                            // Hide the loading screen
                            bookingModeLoader.style.display = 'none';
                            
                            document.getElementById('booking').scrollIntoView({ behavior: 'smooth', block: 'start' });
                            
                            // Reset button state
                            openBookingBtn.disabled = false;
                            buttonText.textContent = 'Book the Hall for Semester Booking';
                            buttonSpinner.style.display = 'none';
                        }, 2000);
                    });
                    openBookingBtn.dataset.bound = 'true';
                }
            }
            // Fetch and render extended hall details
            fetch(`fetch_hall_details.php?hall_id=${hallId}`)
                .then(r => r.json())
                .then(json => {
                    const target = document.getElementById('hall-details');
                    if (!target) return;
                    target.style.display = 'block';
                    if (json && json.data) {
                        const h = json.data;
                        const type = h.type_name || '';
                        const cap = typeof h.capacity !== 'undefined' ? h.capacity : '';
                        const school = h.school_name || '';
                        const dept = h.department_name || '';
                        const name = h.hall_name || (typeof hallName !== 'undefined' ? hallName : '');
                        target.innerHTML = `
                <div>
                    <div style="font-size:20px; color:#d32f2f; font-weight:800; margin-bottom:8px;">${name}</div>
                    <div style="font-size:12px; color:#333; font-weight:600;">Type: <span style="color:#0e00a3;">${type}</span></div>
                    <div style="font-size:12px; color:#333; font-weight:600;">Capacity: <span style="color:#0e00a3;">${cap}</span></div>
                    <div style="font-size:12px; color:#555;">Belongs to: <span style="color:#0e00a3; font-weight:600;">${dept}</span></div>
                    <div style="font-size:12px; color:#555;">School: <span style="color:#0e00a3; font-weight:600;">${school}</span></div>
                </div>
            `;
                    } else {
                        target.innerHTML = `<b>${hallName}</b>`;
                    }
                })
                .catch(() => {
                    const target = document.getElementById('hall-details');
                    target.style.display = 'block';
                    target.innerHTML = `<b>${hallName}</b>`;
                });
        } else {
            const target = document.getElementById("hall-details");
            target.innerHTML = "";
            target.style.display = 'none';
            document.getElementById("view-button").style.display = "none"; // Hide button
            const openBookingBtn = document.getElementById('open-booking-button');
            if (openBookingBtn) openBookingBtn.style.display = "none";
            bookingMode = false;
            
            // Remove booking-active class when no hall is selected
            document.getElementById('timetable').classList.remove('booking-active');
        }

        document.getElementById("selected_hall").value = hallId; // Store hall ID for form submission
        document.getElementById('booking').style.display = 'block';
        document.getElementById('message').style.display = 'none';

        // Hide all slot-related displays initially
        document.getElementById('slot-loader').style.display = 'none';
        document.getElementById('slot-status-display').style.display = 'none';
        document.getElementById('booking-form').style.display = 'none';

        // Clear previous timetable entries
        document.querySelectorAll("#timetable tbody td:not(:first-child)").forEach(cell => cell.innerHTML = "");

        fetch(`fetch_timetable.php?hall_id=${hallId}&start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.text())
            .then(data => {
                try {
                    const jsonData = JSON.parse(data);
                    if (jsonData.error) {
                        console.error('Error from server:', jsonData.error);
                        return;
                    }

                    const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
                    const timeSlots = ["9:30 AM", "10:30 AM", "11:30 AM", "12:30 PM", "1:30 PM", "2:30 PM", "3:30 PM", "4:30 PM"];

                    for (const [day, slots] of Object.entries(jsonData)) {
                        let rowIndex = days.indexOf(day) + 1;
                        if (rowIndex > 0) {
                            for (const [slot, purpose] of Object.entries(slots)) {
                                let colIndex = parseInt(slot);
                                const cell = document.querySelector(`#timetable tbody tr:nth-child(${rowIndex}) td:nth-child(${colIndex + 1})`);
                                if (!cell) continue;
                                cell.textContent = purpose || '';
                                cell.dataset.day = day;
                                cell.dataset.slot = colIndex;
                                // Mark approved/booked cells so they are non-interactive in booking mode
                                if (purpose && String(purpose).trim() !== '') {
                                    cell.classList.add('approved-slot');
                                    cell.title = 'Already booked for a Semester';
                                } else {
                                    cell.classList.remove('approved-slot');
                                    cell.removeAttribute('title');
                                }
                            }
                        }
                    }
                } catch (error) {
                    console.error("Error parsing JSON:", error);
                    console.log("Raw data:", data);
                }
            })
            .catch(error => console.error("Error fetching timetable:", error));

        checkSlots();
    }

//         function toggleNav() {
//             const sidenav = document.getElementById("mySidenav");
//             const toggleBtn = document.querySelector(".toggle-btn");

//             if (sidenav.style.width === "250px") {
       
//                 sidenav.style.width = "0px"; // Open sidebar
//         toggleBtn.style.left = "0px"; // Position button to the right of the open sidebar
//         document.getElementById("main").style.marginLeft = "0px";
//         toggleBtn.classList.remove("open");
//       } else {
//         sidenav.style.width = "250px"; // Close sidebar
//         toggleBtn.style.left = "200px"; // Move button to the left
//         document.getElementById("main").style.marginLeft= "250px";

//         toggleBtn.classList.add("open");
//     }
//         } 
   
const organiserInput = document.getElementById('organiser_name');
const suggestionsBox = document.getElementById('suggestions');
const mobileField = document.getElementById('organiser_mobile');
const emailField = document.getElementById('organiser_email');
const employeeIdField = document.getElementById('employee_id');

let currentIndex = -1; // Tracks the currently highlighted suggestion
let previousInputLength = 0; // Track the previous input length

// Function to trigger faculty search on click/focus
function triggerFacultySearch() {
    const query = organiserInput.value.trim();
    if (query.length === 0) {
        // Show placeholder text when input is empty
        suggestionsBox.innerHTML = '<li class="list-group-item text-muted">Start typing to search faculty...</li>';
        suggestionsBox.style.display = 'block';
        return;
    }
    if (query.length > 0) {
        fetch('get_employee.php?department=<?php echo urlencode($department_name); ?>&query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (data.length > 0) {
                    suggestionsBox.style.display = 'block';
                    currentIndex = -1;
                    data.forEach((employee, index) => {
                        const suggestion = document.createElement('li');
                        suggestion.textContent = employee.employee_name;
                        suggestion.className = 'list-group-item';
                        suggestion.style.cursor = 'pointer';
                        suggestion.setAttribute('data-index', index);
                        suggestion.setAttribute('data-id', employee.employee_id);

                        suggestion.addEventListener('click', () => {
                            organiserInput.value = employee.employee_name;
                            suggestionsBox.style.display = 'none';
                            fetchEmployeeDetails(employee.employee_id);
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
}

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
        // Show placeholder text when input is empty
        suggestionsBox.innerHTML = '<li class="list-group-item text-muted">Start typing to search departments...</li>';
        suggestionsBox.style.display = 'block';
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
function highlightCard(selectedCard) {
    document.querySelectorAll('.card').forEach(card => card.classList.remove('active'));
    selectedCard.classList.add('active');
}

document.addEventListener("DOMContentLoaded", function () {
    // Get elements
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const hallSelect = document.getElementById('selected_hall');

    // Add event listeners for date inputs
    if (startDate) startDate.addEventListener('change', function() {
        checkSlots();
        setTimeout(disableBookedSlots, 100);
    });
    if (endDate) endDate.addEventListener('change', function() {
        checkSlots();
        setTimeout(disableBookedSlots, 100);
    });
    if (hallSelect) hallSelect.addEventListener('change', function() {
        checkSlots();
        setTimeout(disableBookedSlots, 100);
    });

    // Use event delegation for dynamically loaded radio buttons and checkboxes
    document.addEventListener('change', function (event) {
        if (event.target.matches('input[name="day_of_week"]')) {
            // Clear previously selected slots when day changes
            document.querySelectorAll('input[name="slots[]"]:checked').forEach(slot => {
                slot.checked = false;
            });
            
            // When day changes, disable booked slots for that day
            setTimeout(disableBookedSlots, 100);
            checkSlots();
            // Highlight selected slots in timetable
            setTimeout(highlightSelectedSlots, 100);
        } else if (event.target.matches('input[name="slots[]"]')) {
            checkSlots();
            // Highlight selected slots in timetable
            setTimeout(highlightSelectedSlots, 100);
        }
    });
});
function checkSlots() {
    const day = document.querySelector('input[name="day_of_week"]:checked');
    const selectedSlots = Array.from(document.querySelectorAll('input[name="slots[]"]:checked')).map(checkbox => checkbox.value);
    selectedSlotsState = selectedSlots.slice();
    try { console.log('[Selection] slots:', selectedSlotsState); } catch(e) {}
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const hallId = document.getElementById('selected_hall').value;

    // Ensure all required fields are selected
    if (!day || selectedSlots.length === 0 || !startDate || !endDate || !hallId) {
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'check_slot_booking.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    const params = new URLSearchParams();
    params.append('day_of_week', day.value);
    params.append('slots', selectedSlots.join(','));
    params.append('start_date', startDate);
    params.append('end_date', endDate);
    params.append('hall_id', hallId);

    xhr.send(params.toString());

    xhr.onload = function () {
     if (xhr.status === 200) {
         try {
             const response = JSON.parse(xhr.responseText);
 
             // Ensure response.error is an array (in case it's not)
             if (Array.isArray(response.error) && response.error.length > 0) {
                 try { console.log('[Validation] Errors:', response.error); } catch(e) {}
                 document.getElementById('slot_error').innerHTML = response.error.join("<br>");
                 document.getElementById('slot_error').style.display = 'block';
                 document.getElementById('organiser_details').style.display = 'none';
                 openConflictModal(response.error);
             } else if (response.error && typeof response.error === 'string') {
                 // If error is a string, display it directly
                 try { console.log('[Validation] Error:', response.error); } catch(e) {}
                 document.getElementById('slot_error').innerHTML = response.error;
                 document.getElementById('slot_error').style.display = 'block';
                 document.getElementById('organiser_details').style.display = 'none';
                 openConflictModal(response.error);
             } else {
                 // Detect any booked/approved conflicts among selected slots
                 const bookedArray = Array.isArray(response.bookedSlots) ? response.bookedSlots : (Array.isArray(response.conflicts) ? response.conflicts : []);
                 const bookedFlag = response.booked === true || response.hasConflict === true || response.anyBooked === true;
                 if ((bookedArray && bookedArray.length > 0) || bookedFlag) {
                     try { console.log('[Validation] Booked conflict detected:', { bookedSlots: bookedArray, booked: response.booked, hasConflict: response.hasConflict, anyBooked: response.anyBooked }); } catch(e) {}
                     document.getElementById('slot_error').innerHTML = 'One or more selected slots are already booked.';
                     document.getElementById('slot_error').style.display = 'block';
                     document.getElementById('organiser_details').style.display = 'none';
                     openConflictModal(bookedArray && bookedArray.length ? bookedArray : 'One or more selected slots are already booked.');
                 } else {
                     // No error, show organiser details
                     document.getElementById('slot_error').style.display = 'none';
                     document.getElementById('organiser_details').style.display = 'block';
                     closeConflictModal();
                 }
             }
         } catch (e) {
             console.error("Failed to parse JSON response:", e);
         }
     }
 };
 }

// Get semester date restrictions from PHP
const semesterStart = '<?= $semesterStart ?>';
const semesterEnd = '<?= $semesterEnd ?>';
const today = new Date().toISOString().split('T')[0];

// Retrieve the DOM elements, NOT their values
const startDateInput = document.getElementById('start_date');
const endDateInput = document.getElementById('end_date');

// Set semester date restrictions with past date prevention
const minDate = new Date(Math.max(new Date(semesterStart), new Date(today))).toISOString().split('T')[0];
startDateInput.setAttribute('min', minDate);
startDateInput.setAttribute('max', semesterEnd);
endDateInput.setAttribute('min', minDate);
endDateInput.setAttribute('max', semesterEnd);

startDateInput.addEventListener("input", function() {
  // Get the selected start date
  const startDate = new Date(startDateInput.value);
  const todayDate = new Date(today);

  // Ensure start date is not before today
  if (startDate < todayDate) {
      startDateInput.value = today;
      return;
  }

  // If the end date is empty or set to a date before the start date, update it to match the start date.
  if (!endDateInput.value || new Date(endDateInput.value) < startDate) {
      endDateInput.value = startDateInput.value;
  }

  // Update the min attribute of the end date to ensure it cannot be set to a day before the start date.
  endDateInput.setAttribute('min', startDateInput.value);
});

endDateInput.addEventListener("input", function() {
  const endDate = new Date(endDateInput.value);
  const todayDate = new Date(today);

  // Ensure end date is not before today
  if (endDate < todayDate) {
      endDateInput.value = today;
      return;
  }

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
<script>
function suggestDepartments(query) {
    const suggestionsList = document.getElementById("department_suggestions");

    if (query.trim().length === 0) {
        // Show placeholder text when input is empty
        suggestionsList.innerHTML = '<li class="list-group-item text-muted">Start typing to search departments...</li>';
        suggestionsList.style.display = "block";
        return;
    }

    fetch('get_departments.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            suggestionsList.innerHTML = '';

            if (data.length > 0) {
                data.forEach(department => {
                    const listItem = document.createElement('li');
                    listItem.textContent = department.department_name;
                    listItem.className = 'list-group-item list-group-item-action';
                    listItem.onclick = function() {
                        document.getElementById('organiser_department').value = department.department_name;
                        suggestionsList.style.display = 'none';
                    };
                    suggestionsList.appendChild(listItem);
                });
                suggestionsList.style.display = 'block';
            } else {
                suggestionsList.style.display = 'none';
            }
        })
        .catch(error => console.error('Error fetching department suggestions:', error));
}
</script>