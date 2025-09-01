<?php
include 'assets/conn.php'; // Include database connection
include 'assets/header.php'; // Include Header File

$userRole = $_SESSION['role']; // Role of the user and it Can be Admin, HOD, or Dean
$userId = $_SESSION['user_id']; // Logged-in user ID
if ($userRole == 'admin') {

    // Queries to fetch the counts of each hall type
    $seminarHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 1");
    $auditoriums = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 2");
    $lectureHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 3");
    $conferenceHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE type_id = 4");

    // Total hall count
    $totalHalls = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details");

    $users = mysqli_query($conn, "SELECT COUNT(*) AS count FROM users");
    $schools = mysqli_query($conn, "SELECT COUNT(*) AS count FROM schools");
    $departments = mysqli_query($conn, "SELECT COUNT(*) AS count FROM departments");
    $sections = mysqli_query($conn, "SELECT COUNT(*) AS count FROM section");

    $seminarHallCount = mysqli_fetch_assoc($seminarHalls)['count'];
    $auditoriumCount = mysqli_fetch_assoc($auditoriums)['count'];
    $lectureHallCount = mysqli_fetch_assoc($lectureHalls)['count'];
    $conferenceHallCount = mysqli_fetch_assoc($conferenceHalls)['count'];
    $totalHallCount = mysqli_fetch_assoc($totalHalls)['count'];

    $userCount = mysqli_fetch_assoc($users)['count'];
    $schoolCount = mysqli_fetch_assoc($schools)['count'];
    $departmentCount = mysqli_fetch_assoc($departments)['count'];
    $sectionCount = mysqli_fetch_assoc($sections)['count'];

} elseif ($userRole == 'hod') {
    // Fetch the department ID for the HOD
    $hodDepartmentQuery = mysqli_query($conn, "SELECT department_id FROM users WHERE user_id = '$userId'");
    $hodDepartment = mysqli_fetch_assoc($hodDepartmentQuery)['department_id'];

    // Fetch halls under HOD's department (this can be seminar halls, lecture halls, etc.)
    $departmentHallsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE department_id = '$hodDepartment'");
    $departmentHallCount = mysqli_fetch_assoc($departmentHallsQuery)['count'];

    // Fetch bookings made by the HOD (either within or outside the department)
    $hodBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE user_id = '$userId'");
    $hodBookingsCount = mysqli_fetch_assoc($hodBookingsQuery)['count'];

    // Count total seminar halls in HOD's department
    $seminarHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE department_id = '$hodDepartment' AND type_id = 1");
    $seminarHallCount = mysqli_fetch_assoc($seminarHallQuery)['count'];

    // Count total lecture halls in HOD's department
    $lectureHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE department_id = '$hodDepartment' AND type_id = 3");
    $lectureHallCount = mysqli_fetch_assoc($lectureHallQuery)['count'];

    // Fetch bookings for halls under HOD's department
    $departmentHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment')");
    $departmentHallBookingsCount = mysqli_fetch_assoc($departmentHallBookingsQuery)['count'];

    // Fetch Pending, Approved, and Rejected Bookings for HOD's Department
    $pendingBookingsQuery = mysqli_query($conn, "SELECT * FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment') AND status = 'pending'");
    $pendingBookingsCount = mysqli_num_rows($pendingBookingsQuery);

    $approvedBookingsQuery = mysqli_query($conn, "SELECT * FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment') AND status = 'approved'");
    $approvedBookingsCount = mysqli_num_rows($approvedBookingsQuery);

    $rejectedBookingsQuery = mysqli_query($conn, "SELECT * FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = '$hodDepartment') AND status = 'rejected'");
    $rejectedBookingsCount = mysqli_num_rows($rejectedBookingsQuery);

    // Fetch bookings made for halls under HOD's control (this assumes the HOD is assigned specific halls)
    // If the HOD controls specific halls, fetch bookings only for those halls
    $controlHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId')");
    $controlHallBookingsCount = mysqli_fetch_assoc($controlHallBookingsQuery)['count'];

    // Fetch approved bookings for halls under HOD's control
    $controlHallApprovedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'approved'");
    $controlHallApprovedCount = mysqli_fetch_assoc($controlHallApprovedQuery)['count'];

    // Fetch rejected bookings for halls under HOD's control
    $controlHallRejectedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'rejected'");
    $controlHallRejectedCount = mysqli_fetch_assoc($controlHallRejectedQuery)['count'];

} elseif ($userRole == 'dean') {
    // Fetch the department ID for the HOD
    $deanDepartmentQuery = mysqli_query($conn, "SELECT school_id FROM users WHERE user_id = '$userId'");
    $deanDepartment = mysqli_fetch_assoc($deanDepartmentQuery)['school_id'];

    // Fetch halls under HOD's department (this can be seminar halls, lecture halls, etc.)
    $departmentHallsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE school_id = '$deanDepartment'");
    $departmentHallCount = mysqli_fetch_assoc($departmentHallsQuery)['count'];

    // Fetch bookings made by the HOD (either within or outside the department)
    $hodBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE user_id = '$userId'");
    $hodBookingsCount = mysqli_fetch_assoc($hodBookingsQuery)['count'];

    // Count total seminar halls in HOD's department
    $seminarHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE school_id = '$deanDepartment' AND type_id = 1");
    $seminarHallCount = mysqli_fetch_assoc($seminarHallQuery)['count'];

    // Count total lecture halls in HOD's department
    $lectureHallQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM hall_details WHERE school_id = '$deanDepartment' AND type_id = 3");
    $lectureHallCount = mysqli_fetch_assoc($lectureHallQuery)['count'];

    // Fetch bookings for halls under HOD's department
    $departmentHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE school_id = '$deanDepartment')");
    $departmentHallBookingsCount = mysqli_fetch_assoc($departmentHallBookingsQuery)['count'];

    // Fetch Pending, Approved, and Rejected Bookings for HOD's Department
    $controlHallBookingsQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId')");
    $controlHallBookingsCount = mysqli_fetch_assoc($controlHallBookingsQuery)['count'];

    // Fetch approved bookings for halls under HOD's control
    $controlHallApprovedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'approved'");
    $controlHallApprovedCount = mysqli_fetch_assoc($controlHallApprovedQuery)['count'];

    // Fetch rejected bookings for halls under HOD's control
    $controlHallRejectedQuery = mysqli_query($conn, "SELECT COUNT(*) AS count FROM bookings WHERE hall_id IN (SELECT hall_id FROM hall_details WHERE user_id = '$userId') AND status = 'rejected'");
    $controlHallRejectedCount = mysqli_fetch_assoc($controlHallRejectedQuery)['count'];
}
$sql_count = "
SELECT COUNT(*) AS pending_count 
FROM bookings b 
WHERE b.status = 'pending' 
  AND b.end_date >= CURDATE()
  AnD day_of_week is NULL";

if ($user_role == 'hod') {
    // Filter by department for HOD role
    $sql_count .= " AND b.hall_id IN (SELECT hall_id FROM hall_details WHERE department_id = ?)";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $department_id); // Assuming $department_id is passed
} elseif ($user_role == 'dean') {
    // Filter by school_id for Dean role
    $sql_count .= " AND b.hall_id IN (SELECT hall_id FROM hall_details WHERE department_id IN (SELECT department_id FROM departments WHERE school_id = ?))";
    $stmt = $conn->prepare($sql_count);
    $stmt->bind_param("i", $school_id); // Assuming $school_id is passed
} else {
    // No additional filters for other roles
    $stmt = $conn->prepare($sql_count);
}

$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$pending_count = $row['pending_count'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/design.css" />
    <style>
        :root {
            --primary: #4e54c8;
            --secondary: #007bff;
            --background-light: #f8f9fa;
            --text-dark: #333;
            --card-radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --hover-scale: 1.05;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--background-light);
            margin: 0;
            padding: 0;
        }

        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100vw;
            background: url('image/Pondicherry_University_Logo_Dashboard_Cover.png') no-repeat center center;
            background-size: 800px 550px;
            filter: blur(2px);
            z-index: -1;
        }

        /* Overlay with background-light color */
        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100vw;
            background-color: var(--background-light);
            opacity: 0.2; /* Adjust for stronger or lighter tint */
            z-index: -1;
        }

        /* Main Layout */
        #main {
            padding: 40px 5%;
        }

        /* General Card Styling */
        .card, .section-card, .school-card, .department-card, .hall-card {
            background: #fff;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
        }

        .card:hover,
        .section-card:hover,
        .school-card:hover,
        .department-card:hover,
        .hall-card:hover {
            transform: scale(var(--hover-scale));
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        /* Card Content */
        .card-body {
            padding: 20px;
            margin-bottom:10px
        }

        .card-title {
            font-size: 1.4rem;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .round-card-title{
            font-size: 1.0rem;
        }

        .card-count {
            font-size: 2.2rem;
            font-weight: bold;
            color: var(--secondary);
        }

        /* Container Layout */
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: space-between;
        }

        .left-column,
        .middle-column,
        .right-column {
            flex: 1 1 300px;
        }

        /* Colored Cards */
        .section-card {
            background-color: #e3f2fd;
            border-left: 5px solid #2196f3;
        }

        .school-card {
            background-color: #ede7f6;
            border-left: 5px solid #673ab7;
        }

        .department-card {
            background-color: #fce4ec;
            border-left: 5px solid #e91e63;
        }

        /* Hall Cards in Grid */
        .hall-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            margin-top:50px
        }

        .hall-card {
            height: 150px;
            width: 150px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 1rem;
            color: #000;
            border: 3px solid var(--secondary);
        }

        /* Hall Colors */
        .hall-card:nth-child(1) { background-color: #ffccbc; }
        .hall-card:nth-child(2) { background-color: #d1c4e9; }
        .hall-card:nth-child(3) { background-color: #c8e6c9; }
        .hall-card:nth-child(4) { background-color: #fff9c4; }

        /* Semester Form */
        .semester-form {
            background: #fff;
            padding: 25px;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow);
            text-align: left;
            max-width: 100%;
            margin-top:50px
        }

        .semester-form h3 {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 20px;
            text-align: center;
        }

        .semester-form label {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 5px;
            display: block;
        }

        .semester-form input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            transition: 0.2s ease;
        }

        .semester-form input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 6px rgba(78, 84, 200, 0.4);
        }

        .semester-form button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .semester-form button:hover {
            background: #3c40c6;
        }

        /* Date Picker Full Screen Modal */
        .date-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .date-modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .date-modal-content h3 {
            margin-bottom: 20px;
            color: #333;
        }

        .date-modal-content input[type="date"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .modal-buttons button:first-child {
            background: #ccc;
            color: #333;
        }

        .modal-buttons button:last-child {
            background: #4e54c8;
            color: white;
        }

        /* Responsive Tweaks */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                gap: 20px;
                justify-content: flex-start;  /* change from space-between to flex-start */
                align-items: flex-start;      /* left align stacked items */
            }

            .hall-card {
                width: 150px;
                height: 150px;
                font-size: 0.9rem;
            }

            .semester-form {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>

<!-- Blurred Background Layer -->
<div class="background-blur"></div>
<div class="background-overlay"></div>

    <div id="main">
    <br>
                <br>
                <br>
        <div class="container mt-5">
            <?php if ($userRole == 'admin'): ?>
                
                <!-- Left Column: Sections, Schools, and Departments -->
                <div class="left-column">
                    <div class="section-card">
                        <div class="card-body">
                            <h5 class="card-title">Sections</h5>
                            <p class="card-count"><?= $sectionCount ?></p>
                        </div>
                    </div>

                    <div class="school-card">
                        <div class="card-body">
                            <h5 class="card-title">Schools</h5>
                            <p class="card-count"><?= $schoolCount ?></p>
                        </div>
                    </div>

                    <div class="department-card">
                        <div class="card-body">
                            <h5 class="card-title">Departments</h5>
                            <p class="card-count"><?= $departmentCount ?></p>
                        </div>
                    </div>
                </div>
                <div class="middle-column">
                    <div class="hall-container">
                        <!-- Seminar Halls -->
                        <div class="hall-card">
                            <div class="card-body">
                                <h5 class="round-card-title">Seminar Halls</h5>
                                <p class="card-count"><?= $seminarHallCount ?></p>
                            </div>
                        </div>

                        <!-- Auditoriums -->
                        <div class="hall-card">
                            <div class="card-body">
                                <h5 class="round-card-title">Auditoriums</h5>
                                <p class="card-count"><?= $auditoriumCount ?></p>
                            </div>
                        </div>

                        <!-- Lecture Halls -->
                        <div class="hall-card">
                            <div class="card-body">
                                <h5 class="round-card-title">Lecture Halls</h5>
                                <p class="card-count"><?= $lectureHallCount ?></p>
                            </div>
                        </div>

                        <!-- Conference Halls -->
                        <div class="hall-card">
                            <div class="card-body">
                                <h5 class="round-card-title">Conference Halls</h5>
                                <p class="card-count"><?= $conferenceHallCount ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Database Connection
            

                // Fetch the last semester entry and it will be latest entry
                $query1 = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
                $result1 = mysqli_query($conn, $query1);
                $latestSemester = mysqli_fetch_assoc($result1);
                ?>
                <div class="right-column">
                        <div class="semester-form">
                            <h3>Semester Time Period</h3>
                            <form action="add_semester.php" method="POST">

                                <label for="start_date">Start Date:</label>
                                <input type="text" id="start_date" name="start_date"
                                value="<?= $latestSemester['start_date'] ?? '' ?>" 
                                onclick="openDateModal('start_date')" readonly required>

                                <label for="end_date">End Date:</label>
                                <input type="text" id="end_date" name="end_date"
                                value="<?= $latestSemester['end_date'] ?? '' ?>" 
                                onclick="openDateModal('end_date')" readonly required>
                                
                                <!-- These now use readonly to prevent native calendar pop-up and instead open your custom full-screen modal. -->
                               
                                <!-- Action Button  -->
                                <button type="submit" id="modifyButton">Modify</button>
                            </form>

                            <!-- Fullscreen Date Picker Modal -->
                            <div id="dateModal" class="date-modal">
                                <div class="date-modal-content">
                                    <h3>Select a Date</h3>
                                    <input type="date" id="modalDatePicker">
                                    <div class="modal-buttons">
                                        <button onclick="closeDateModal()">Cancel</button>
                                        <button onclick="confirmDate()">OK</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        <?php endif; ?>
       
    </div>
    </div>

<script>
    // Date Picker Full Screen Modal
    let currentInput = null;

    function openDateModal(inputId) {
        currentInput = document.getElementById(inputId);
        const modal = document.getElementById('dateModal');
        const modalDate = document.getElementById('modalDatePicker');

        modalDate.value = currentInput.value;
        modal.style.display = 'flex';
    }

    function closeDateModal() {
        document.getElementById('dateModal').style.display = 'none';
        currentInput = null;
    }

    function confirmDate() {
        const selectedDate = document.getElementById('modalDatePicker').value;
        if (currentInput) currentInput.value = selectedDate;
        closeDateModal();
    }

    // Optional: Close modal if clicking outside the box
    window.addEventListener('click', function (e) {
        const modal = document.getElementById('dateModal');
        if (e.target === modal) {
            closeDateModal();
        }
    });
</script>

<script>
    // To display "Modifying..." on the Modify button when it's clicked
    const form = document.querySelector('.semester-form form');
    const modifyBtn = document.getElementById('modifyButton');

    form.addEventListener('submit', function () {
        modifyBtn.disabled = true;                  // Optional: prevent double clicks
        modifyBtn.textContent = 'Modifying...';     // Change button text
    });
</script>

<script>
    // Disable or prevent the Modify button from being clicked unless the user changes either the Start Date or End Date
    
    // Store initial values on page load
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const modifyButton = document.getElementById('modifyButton');

    let initialStartDate = startDateInput.value;
    let initialEndDate = endDateInput.value;

    // Disable Modify button initially if no change
    function checkForChanges() {
        if (startDateInput.value !== initialStartDate || endDateInput.value !== initialEndDate) {
            modifyButton.disabled = false;
        } else {
            modifyButton.disabled = true;
        }
    }

    // Initially check on page load
    checkForChanges();

    // Update Modify button state whenever date inputs change (after modal confirm)
    function confirmDate() {
        const selectedDate = document.getElementById('modalDatePicker').value;
        if (currentInput) {
            currentInput.value = selectedDate;
        }
        closeDateModal();
        checkForChanges();  // Check if button should be enabled after date change
    }
</script>

</body>
</html>