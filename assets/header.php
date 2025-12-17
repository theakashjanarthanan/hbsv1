<?php
include 'assets/conn.php'; // Include the Database Connection File
session_start(); // Start the session

if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Fetch user details from session
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$user_role = $_SESSION['role'];

$school_name1 = '';
$department_name1 = '';

// Fetch school name for Dean
if ($user_role == 'dean') {
    $sql_school = "SELECT school_name FROM schools WHERE school_id = ?";
    $stmt = $conn->prepare($sql_school);
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result_school = $stmt->get_result();
    if ($row = $result_school->fetch_assoc()) {
        $school_name1 = $row['school_name'];
    }
}

// Fetch department name for HOD
if ($user_role == 'hod' || $user_role == 'prof') {
    $sql_department = "SELECT department_name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql_department);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result_department = $stmt->get_result();
    if ($row = $result_department->fetch_assoc()) {
        $department_name1 = $row['department_name'];
    }
}
// First SQL query: Count pending bookings
$sql_count = "
SELECT COUNT(*) AS pending_count 
FROM bookings b 
WHERE b.status = 'pending' 
AND day_of_week IS NULL
  AND b.end_date >= CURDATE()";
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

// Get the department ID of the logged-in user
$query = "SELECT department_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id); // Assuming $user_id is passed
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_department_id = $user['department_id'] ?? null; // Get department_id of the logged-in user

// Second SQL query: Count allowed bookings for specific department
$sql_count = "
SELECT COUNT(*) AS pending_count1 
FROM bookings b 
WHERE b.status = 'allow' 
  AND b.start_date >= CURDATE()  -- Add the date condition here
  AND EXISTS (SELECT 1 FROM users u WHERE u.user_id = b.user_id AND u.department_id = ?)";

if ($user_role === 'hod' && $user_department_id !== null) {
    // Filter HOD role bookings where the hall is NOT in their department
    $sql_count .= " AND b.hall_id NOT IN (SELECT hall_id FROM hall_details WHERE department_id = ?)";
}
$stmt = $conn->prepare($sql_count);

if ($user_role === 'hod') {
    $stmt->bind_param("ii", $user_department_id, $user_department_id);
} else {
    $stmt->bind_param("i", $user_department_id);
}

$stmt->execute();
$result1 = $stmt->get_result();
$row = $result1->fetch_assoc();
$pending_count1 = $row['pending_count1'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>University Hall Booking System</title>
    <style>
        :root{
            --brand-primary:#0d6efd;
            --brand-primary-dark:#0a58ca;
            --brand-bg:#0d6efd;
            --text-on-primary:#ffffff;
            --surface-1:#283745;
            --surface-2:#34495e;
            --surface-3:#3d566e;
            --hover:#1b6ff7;
            --shadow:0 2px 8px rgba(0,0,0,.12);
            --radius:10px;
        }
        body {
            font-family: "Lato", sans-serif;
            margin: 0;
            padding-top: 0;
            background-color: #f7f9fc;
            color: #1f2937;
        }

        /* Top navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10;
            background: linear-gradient(90deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1), width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Keep navbar full width; do not shift when sidebar opens */
        .navbar .logo-section{
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .navbar img {
            height: 40px;
            width: auto;
        }
        .title-section {
            color: var(--text-on-primary);
            font-size: 20px;
            margin: 0;
            flex: 1;
            text-align: center;
            white-space: nowrap;
            letter-spacing: .5px;
            font-weight: 700;
            position: relative;
            bottom:10px;
        }
        .user-section {
            color: var(--text-on-primary);
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 10px;
            text-align: right;
            min-width: 0;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 2px;
        }

        .user-department {
            font-size: 18px;
            font-weight: 500;
            opacity: 0.9;
            line-height: 1.2;
        }

        .user-greeting {
            font-size: 18px;
            font-weight: 400;
            /* opacity: 0.8; */
            line-height: 1.2;
        }
        .user-badge{
            background: rgba(255,255,255,.1);
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            border: 1px solid rgba(255,255,255,.2);
        }
        .logout-link{
            color: #fff;
            text-decoration: none;
            border: 1px solid rgba(255,255,255,.3);
            padding: 6px 10px;
            border-radius: 4px;
            transition: background-color .2s ease, color .2s ease, border-color .2s ease, transform .15s ease;
            position: relative;
            overflow: hidden;
        }
        .logout-link:hover{
            background: rgba(255, 0, 0, 0.12);
            border-color: rgba(255,255,255,.55);
            transform: translateY(-1px);
        }
        .logout-link:active{
            transform: translateY(0);
        }
        .badge{
            display: inline-block;
            min-width: 22px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            background: #fff;
            color: #111827;
            font-weight: 600;
            border: 1px solid rgba(0,0,0,.1);
        }

        /* Responsive navbar */
        @media (max-width: 963px) {
            .navbar { 
                flex-wrap: wrap; 
                padding: 10px; 
                gap: 8px;
            }
            .title-section { 
                font-size: 18px; 
                order: 3; 
                width: 100%; 
                margin-top: 8px;
            }
            .user-section { 
                gap: 2px;
            }
            .user-department {
                font-size: 13px;
            }
            .user-greeting {
                font-size: 12px;
            }
        }
        @media (max-width: 600px) {
            .navbar img { height: 32px; }
            .title-section { font-size: 16px; }
            .user-section { 
                gap: 1px;
                min-width: 120px;
            }
            .user-department {
                font-size: 12px;
            }
            .user-greeting {
                font-size: 11px;
            }
            .user-badge {
                font-size: 11px;
                padding: 4px 8px;
            }
            .logout-link {
                font-size: 12px;
                padding: 4px 8px;
            }
        }
        @media (max-width: 400px) {
            .title-section h3{ font-size: 14px; }
            .user-section {
                min-width: 100px;
            }
            .user-department {
                font-size: 11px;
            }
            .user-greeting {
                font-size: 10px;
            }
        }

        /* Left sidebar - Applied from header-demo.php */
        .sidenav {
            height: 100%;
            width: 250px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: 0;
            background-color: #283745;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 100px;
        }

        .sidenav a,
        .sidenav button {
            padding: 15px 20px;
            text-decoration: none;
            font-size: 18px;
            color: #ecf0f1;
            display: flex;
            align-items: center;
            border: none;
            background: none;
            outline: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sidenav a:hover,
        .sidenav button:hover {
            background-color: #34495e;
        }

        .dropdown-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dropdown-btn i {
            transition: transform 0.3s ease;
        }

        .dropdown-btn.collapsed i {
            transform: rotate(180deg);
        }

        .sidenav a.active,
        .sidenav button.active {
            background-color: rgb(36, 36, 36);
            color: white;
        }

        .dropdown-btn.active {
            background-color: steelblue;
            color: white;
        }

        .dropdown-container {
            display: none;
            background-color: #34495e;
            /* Submenu background */
            padding: 10px 0;
        }

        .dropdown-container a {
            display: block;
            padding: 10px 30px;
            text-decoration: none;
            color: #ecf0f1;
            transition: background-color 0.3s ease;
        }

        .dropdown-container a:hover {
            background-color: #3d566e;
        }

        .dropdown-container a.active {
            background-color: #007bff;
        }

        .dropdown-container {
            display: none;
            background-color: #004a99;
            padding: 10px;
        }

        .dropdown-container.active {
            display: block;
        }

        .dropdown-container a {
            display: block;
            padding: 8px 15px;
            text-decoration: none;
        }

        .dropdown-container a.active {
            background-color: rgb(102, 102, 102);
        }

        .dropdown-container a:hover {
            background-color: rgb(97, 97, 97);
        }

        .dropdown-btn.collapsed i {
            transform: rotate(180deg);
        }



        .toggle-btn {
            position: fixed;
            top: 80px;
            left: 200px;
            background-color: #283745;
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
        
        /* Subtle custom scrollbar for sidebar */
        .sidenav::-webkit-scrollbar { width: 8px; }
        .sidenav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 8px; }
        /* Red light hover/click effect for Logout */
        .logout-link:hover{
            background: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.6);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15), 0 4px 12px rgba(220, 53, 69, 0.25);
        }
        .logout-link:active{
            background: #dc3545;
            border-color: #dc3545;
            color: #fff;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25) inset;
            transform: translateY(0);
        }
    </style>

</head>

<body>
    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>

<nav class="navbar">
    <div class="navbar-section logo-section">
        <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo">
        <!-- <span class="user-badge" ><?= htmlspecialchars(strtoupper($user_role)); ?></span> -->
    </div>

    <div class="navbar-section title-section">
        <h3>UNIVERSITY HALL BOOKING SYSTEM</h3>
    </div>

    <div class="navbar-section user-section">
        <?php if (isset($username)): ?>
            <div class="user-info">
                <div class="user-department">
                    <?php if ($user_role == 'dean'): ?>
                        <?= htmlspecialchars($school_name1); ?>
                    <?php elseif ($user_role == 'hod' || $user_role == "prof"): ?>
                        <?= htmlspecialchars($department_name1); ?>
                    <?php endif; ?>
                </div>
                <div class="user-greeting">Hi, <?= htmlspecialchars($username); ?></div>
            </div>
            <a class="logout-link" id="logoutBtn" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="logout-link" href="index.php">Login</a>
        <?php endif; ?>
    </div>
</nav>


    <div id="mySidenav" class="sidenav">
        <br>
        <br>
        <?php if ($user_role == 'prof'): ?>
            <a href="timetable.php" class="<?= ($currentPage == 'timetable.php') ? 'active' : '' ?>">
                Dashboard
            </a>
        <?php else: ?>
            <?php if ($user_role == 'admin'): ?>
                <a href="home.php" class="<?= ($currentPage == 'home.php') ? 'active' : '' ?>">
                Dashboard
            </a>
        <?php else: ?>
            <a href="timetable.php" class="<?= ($currentPage == 'timetable.php') ? 'active' : '' ?>">
            Dashboard
            </a>
            <?php endif; ?>

            <button class="dropdown-btn">
                Hall Details
                <i style="margin-left:20px;" class="fa fa-chevron-down"></i>
            </button>
            <div class="dropdown-container">
                <?php if ($user_role == 'admin'): ?>
                    <a href="add_hall.php" class="<?= ($currentPage == 'add_hall.php') ? 'active' : '' ?>">Add Hall</a>
                <?php endif; ?>
                <a href="view_modify_hall.php"
                    class="<?= ($currentPage == 'view_modify_hall.php') ? 'active' : '' ?>">View / Modify Hall</a>
                <a href="view_modify_achived_hall.php"
                    class="<?= ($currentPage == 'view_modify_achived_hall.php') ? 'active' : '' ?>">Archive Halls</a>
            </div>
            <?php if ($user_role == 'admin' || $user_role == 'dean'): ?>
                <button class="dropdown-btn">
                    School / Departments
                    <i style="margin-left:20px;" class="fa fa-chevron-down"></i>
                </button>
                <div class="dropdown-container">
                    <?php if ($user_role == 'admin'): ?>
                        <a href="add_school_dept.php" class="<?= ($currentPage == 'add_school_dept.php') ? 'active' : '' ?>">Add
                            School / Department</a>
                    <?php endif; ?>
                    <a href="view_school.php" class="<?= ($currentPage == 'view_school.php') ? 'active' : '' ?>">View/Modify
                        School</a>
                </div>
            <?php endif; ?>

            <button class="dropdown-btn">
                Employee Details
                <i style="margin-left:20px;" class="fa fa-chevron-down"></i>
            </button>
            <div class="dropdown-container">
                <?php if ($user_role == 'admin'): ?>
                    <a href="add_employees.php" class="<?= ($currentPage == 'add_employees.php') ? 'active' : '' ?>">Add
                        Employee</a>
                <?php endif; ?>

                <a href="view_employees.php" class="<?= ($currentPage == 'view_employees.php') ? 'active' : '' ?>">View
                    Employees</a>
            </div>
        <?php endif; ?>
        <?php if ($user_role != 'admin'): ?>

            <button class="dropdown-btn" <?= ($user_role == 'prof') ? 'data-book-hall="true"' : '' ?>>
                Book The Hall
                <i style="margin-left:20px;" class="fa fa-chevron-down"></i>
            </button>
            <div class="dropdown-container">
                <a href="find_halls.php" class="<?= ($currentPage == 'find_halls.php') ? 'active' : '' ?>">Browse & Book
                    Hall</a>
                <?php if ($user_role == 'hod'): ?>
                    <a href="sem_booking.php" class="<?= ($currentPage == 'sem_booking.php') ? 'active' : '' ?>">Semester
                        Booking</a>
                <?php endif; ?>
                <a href="view_modify_booking.php"
                    class="<?= ($currentPage == 'view_modify_booking.php') ? 'active' : '' ?>">My Bookings</a>
            </div>
            <?php if ($user_role == 'hod'): ?>
                <button class="dropdown-btn">
                    Approve Bookings
                        <?= ($pending_count > 0) ? "<span class='badge bg-light text-dark me-2'>$pending_count</span>" : "" ?>
                        <i class="fa fa-chevron-down"></i>
                    
                </button>
                <div class="dropdown-container">
                    <a href="no_conflict_bookings.php"
                        class="<?= ($currentPage == 'no_conflict_bookings.php') ? 'active' : '' ?>">Bookings</a>
                    <a href="conflict_bookings.php"
                        class="<?= ($currentPage == 'conflict_bookings.php') ? 'active' : '' ?>">Bookings Conflict</a>
                </div>
            <?php endif; ?>
            <?php if ($user_role == 'hod'): ?>
                <button class="dropdown-btn">
                    Manage Bookings
                    <i style="margin-left:20px;" class="fa fa-chevron-down"></i>
                </button>
                <div class="dropdown-container">
                    <a href="forward_bookings.php"
                        class="<?= ($currentPage == 'forward_bookings.php') ? 'active' : '' ?>">Forward Bookings
                        <?= ($pending_count1 > 0) ? "<span class='badge bg-light text-dark'>$pending_count1</span>" : "" ?></a>
                    <a href="view_bookings.php" class="<?= ($currentPage == 'view_bookings.php') ? 'active' : '' ?>">View
                        Bookings</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <button class="toggle-btn" onclick="toggleNav()">☰</button>

<script>
   
        function toggleNav() {
            const sidenav = document.getElementById("mySidenav");
            const toggleBtn = document.querySelector(".toggle-btn");

            if (sidenav.style.width === "250px" || sidenav.style.width === "") {
                sidenav.style.width = "0"; // Close sidebar
                toggleBtn.style.left = "0"; // Move button to the left
                const mainElement = document.getElementById("main");
                if (mainElement) {
                    mainElement.style.marginLeft = "0";
                }
                toggleBtn.classList.add("open");
                sessionStorage.setItem('sidebarOpen', 'false');
            } else {
                sidenav.style.width = "250px";
                toggleBtn.style.left = "200px";
                const mainElement = document.getElementById("main");
                if (mainElement) {
                    mainElement.style.marginLeft = "250px";
                }
                toggleBtn.classList.remove("open");
                sessionStorage.setItem('sidebarOpen', 'true');
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
    // Initialize sidebar state from sessionStorage
    const sidenav = document.getElementById("mySidenav");
    const toggleBtn = document.querySelector(".toggle-btn");
    const sidebarState = sessionStorage.getItem('sidebarOpen');
    
    // Default to open if no state is stored (first visit)
    if (sidebarState === null || sidebarState === 'true') {
        sidenav.style.width = "250px";
        toggleBtn.style.left = "200px";
        toggleBtn.classList.remove("open");
        const mainElement = document.getElementById("main");
        if (mainElement) {
            mainElement.style.marginLeft = "250px";
        }
    } else {
        sidenav.style.width = "0";
        toggleBtn.style.left = "0";
        toggleBtn.classList.add("open");
        const mainElement = document.getElementById("main");
        if (mainElement) {
            mainElement.style.marginLeft = "0";
        }
    }
    const dropdownBtns = document.querySelectorAll('.dropdown-btn');
    const dropdownContainers = document.querySelectorAll('.dropdown-container');
    const dropdownLinks = document.querySelectorAll('.dropdown-container a');
    const dashboardLinks = document.querySelectorAll('.sidenav a[href="home.php"], .sidenav a[href="timetable.php"]');

    // Check if user is prof and open "Book the hall" dropdown by default
    const bookHallBtn = document.querySelector('.dropdown-btn[data-book-hall="true"]');
    let activeMenuIndex = sessionStorage.getItem('activeMenuIndex');
    
    if (bookHallBtn) {
        // For prof users, always open "Book the hall" dropdown by default
        const bookHallIndex = Array.from(dropdownBtns).indexOf(bookHallBtn);
        if (bookHallIndex !== -1) {
            const bookHallDropdown = bookHallBtn.nextElementSibling;
            if (bookHallDropdown && bookHallDropdown.classList.contains('dropdown-container')) {
                bookHallBtn.classList.add('active');
                bookHallBtn.classList.add('collapsed');
                bookHallDropdown.classList.add('active');
                sessionStorage.setItem('activeMenuIndex', bookHallIndex);
                activeMenuIndex = bookHallIndex; // Update for consistency
            }
        }
    } else if (activeMenuIndex !== null) {
        // For non-prof users, restore previous state from sessionStorage
        dropdownBtns[activeMenuIndex].classList.add('active');
        dropdownContainers[activeMenuIndex].classList.add('active');
    }

    // Handle Dashboard link clicks
    dashboardLinks.forEach(link => {
        link.addEventListener('click', function () {
            dropdownContainers.forEach((container) => container.classList.remove('active'));
            dropdownBtns.forEach((btn) => btn.classList.remove('collapsed'));
            dropdownLinks.forEach(link => link.classList.remove('active'));
            sessionStorage.removeItem('activeMenuIndex');
        });
    });

    // Handle dropdown button clicks
    dropdownBtns.forEach((btn, index) => {
        const dropdown = btn.nextElementSibling;

        if (btn.classList.contains('active')) {
            dropdown.classList.add('active');
            btn.classList.add('collapsed');
        }

        btn.addEventListener('click', function () {
            const isActive = dropdown.classList.contains('active');
            if (isActive) {
                dropdown.classList.remove('active');
                btn.classList.remove('collapsed');
                sessionStorage.removeItem('activeMenuIndex');
            } else {
                dropdownContainers.forEach((container) => container.classList.remove('active'));
                dropdownBtns.forEach((btn) => btn.classList.remove('collapsed'));

                dropdown.classList.add('active');
                btn.classList.add('collapsed');
                sessionStorage.setItem('activeMenuIndex', index);
            }
        });
    });

    // Handle dropdown link clicks
    dropdownLinks.forEach(link => {
        link.addEventListener('click', function () {
            dropdownLinks.forEach(link => link.classList.remove('active'));
            this.classList.add('active');
        });
    });
});

    </script>
</body>

</html>
