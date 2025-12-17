<?php
// session_start();
include 'assets/conn.php';
include 'assets/header.php';

// Fetch latest semester start and end date
$query = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latestSemester = mysqli_fetch_assoc($result);

$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));
$today = date('Y-m-d');

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$query_user = $conn->prepare("SELECT role, school_id, department_id FROM users WHERE user_id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$user = $query_user->get_result()->fetch_assoc();

$role = $user['role'];
$school_id = $user['school_id'];
$department_id = $user['department_id'];

// Base Query
$query = "SELECT 
    hd.*, 
    ht.type_name, 
    s.school_name, 
    d.department_name, 
    sec.section_name,
    COALESCE(d.incharge_name, s.incharge_name ) AS incharge_name,
    COALESCE(d.designation, s.designation ) AS designation,
    COALESCE(d.incharge_intercom, s.incharge_intercom) AS incharge_intercom,
    COALESCE(d.incharge_email, s.incharge_email) AS incharge_email
FROM hall_details hd
JOIN hall_type ht ON hd.type_id = ht.type_id
LEFT JOIN schools s ON hd.school_id = s.school_id
LEFT JOIN departments d ON hd.department_id = d.department_id
LEFT JOIN section sec ON hd.section_id = sec.section_id";

// Apply role-based filtering
$conditions = [];
if ($role == 'dean' ) {
    $conditions[] = "hd.school_id = '$school_id'";
} elseif ($role == 'hod') {
    $conditions[] = "hd.department_id = '$department_id'";
}
// print_r($conditions);
// exit;
// Apply additional filters
$filters = ['hallType' => 'ht.type_name', 'hallName' => 'hd.hall_name', 'capacity' => 'hd.capacity', 'floor' => 'hd.floor', 'zone' => 'hd.zone'];
foreach ($filters as $param => $column) {
    if (!empty($_GET[$param])) {
        $conditions[] = "$column LIKE '%" . $conn->real_escape_string($_GET[$param]) . "%'";
    }
}

// Append conditions to the query
if (!empty($conditions)) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}
$query .= " ORDER BY hd.hall_id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="assets/design.css?v=2.0" />
    <style>
        .modal-header {
            background-color: #007bff;
            color: white;
        }

        .table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }

        thead th {
            text-align: center;
            background-color: #f4f4f4;
            padding: 10px;
        }

        .container1 {
            width: calc(100%);
            max-width: none;
            justify-content: space-between;
            align-items: center;
            padding: 0 2%;
        }

        .card {
            border: none;
        }

        .filter-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .filter-content {
            background: white;
            width: 550px;
            padding: 30px 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 18px;
            cursor: pointer;
        }

        input[type=text],
        input[type=email],
        input[type=number],
        select,
        textarea {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            margin-top: 4px;
            margin-bottom: 4px;
            resize: vertical;
        }


        .popup-overlay {
            display: none;
            /* Initially hidden */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1;
        }

        .popup-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 440px;
        }

        .close-button {
            cursor: pointer;
            float: right;
            font-size: 20px;
        }

        /* esabeled the clear filter button  */
        .filter-icon {
            filter: grayscale(100%);
            transition: filter 0.3s ease;
        }

        .filter-icon.active {
            filter: brightness(0.5) sepia(1) hue-rotate(300deg) saturate(5);

        }

        .transfer-ownership-button{
            background: #0075ff;
            color: white;
            margin: 10px 5px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        .transfer-ownership-cancel-button{
            background: #dc3545;
            color: white;
            margin: 10px 5px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        /* Success message styles */
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 9999;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease-in-out;
        }
        /* Loading overlay inside card body during search */
        .card-body { position: relative; }
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .loading-spinner {
            width: 2.5rem;
            height: 2.5rem;
            border: 0.35rem solid #e0e0e0;
            border-top-color: #007bff;
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
            margin: 0 auto 10px auto;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Search suggestions dropdown */
        .search-suggestions-wrapper { position: relative; }
        #hallSearchSuggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #ddd;
            border-top: none;
            z-index: 20;
            display: none;
            max-height: 240px;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        #hallSearchSuggestions .item {
            padding: 8px 10px;
            cursor: pointer;
            font-size: 14px;
        }
        #hallSearchSuggestions .item:hover { background: #f1f5ff; }

        .success-message.show {
            opacity: 1;
            transform: translateX(0);
        }

        .success-message.fade-out {
            opacity: 0;
            transform: translateX(100%);
        }
        /* Disable effect for action buttons */
        .icon-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            filter: grayscale(100%);
            pointer-events: none;
        }

        #hallSearchBtn{
             height: 38px;
             width: 38px;
             position: relative;
             top:3px;
        }

    </style>
    <title>Admin Home</title>
</head>

<body>
    <!-- Success Message -->
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div id="successMessage" class="success-message">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Hall Added Successfully!
    </div>
    <?php endif; ?>

    <!-- Modified Success Message -->
    <?php if (isset($_GET['modified']) && $_GET['modified'] == '1'): ?>
    <div id="modifiedMessage" class="success-message">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Hall Modified Successfully!
    </div>
    <?php endif; ?>

    <!-- Availability Updated Success Message -->
    <?php if (isset($_GET['availability_updated']) && $_GET['availability_updated'] == '1'): ?>
    <div id="availabilityMessage" class="success-message">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Hall Availability Status Updated Successfully!
    </div>
    <?php endif; ?>

    <!-- Ownership Transfer Success Message -->
    <?php if (isset($_GET['ownership_transferred']) && $_GET['ownership_transferred'] == '1'): ?>
    <div id="ownershipMessage" class="success-message">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Hall Ownership Transfered Successfully!
    </div>
    <?php endif; ?>

    <!-- Hall Archived Success Message -->
    <?php if (isset($_GET['hall_archived']) && $_GET['hall_archived'] == '1'): ?>
    <div id="archivedMessage" class="success-message">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Hall Archieved Successfully!
    </div>
    <?php endif; ?>

    <div id="main">
        <div class="container1 mt-3">
            <div class="table-wrapper">
                <div class="card">
                    <div class="card-body">
                        <!-- Loading overlay for search -->
                        <center>
                            <h1 style="color:#170098;">Hall Details</h1>

                        </center>

                        <!-- Toggle Button -->


                        <div class="row">
                            <div class="col-8">
                                <div style="display: flex;  align-items: center; gap: 10px; margin: 20px;">

                                    <!-- availability (Green) -->
                                    <button id="availabilityBtn" onclick="openAvailabilityPopup()" class="icon-button green-button" disabled>
                                        <i class="fa-solid fa-check-circle"></i> Availability
                                    </button>

                                    <!-- ownership Transfer (Yellow) -->
                                    <button id="ownershipBtn" onclick="openOwnershipPopup()" class="icon-button yellow-button" disabled>
                                        <i class="fa-solid fa-handshake"></i> Ownership Transfer
                                    </button>
                                    <?php if ($user_role == 'admin'): ?>
                                        <!-- Modify (Blue) -->
                                        <button id="modifyBtn" onclick="modifySelected()" class="icon-button blue-button" disabled>
                                            <i class="fa-solid fa-pen-to-square"></i> Modify
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($user_role != 'admin'): ?>
                                        <button id="modifyFeaturesBtn" onclick="openFeatureForm()" class="icon-button blue-button" disabled>
                                            <i class="fa-solid fa-pen-to-square"></i> Modify Features
                                        </button>
                                    <?php endif; ?>

                                    <!-- Achieve Selected (Red) -->
                                    <button id="archiveBtn" onclick="updateMultipleStatus()" class="icon-button red-button" disabled>
                                        <i class="fa-solid fa-box-archive"></i> Archive
                                    </button>
                                    <!-- Inline search next to Archive -->
                                    <div class="search-suggestions-wrapper" style="max-width: 300px; margin-left:8px; width:100%;">
                                        <div class="input-group">
                                            <input type="text" id="hallSearch" class="form-control" placeholder="Search Hall by Hall Name" aria-label="Search halls" autocomplete="off">
                                            <button type="button" id="hallSearchBtn" class="btn btn-secondary" title="Search">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>
                                        <div id="hallSearchSuggestions"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <!-- <div style="margin-left: 20px;">
                                    <label for="multiSelectToggle">Multiple Selection:</label>
                                    
                                </div> -->
                                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin: 20px;">
                                    <label for="multiSelectToggle">Multiple Selection:</label>

                                    <button id="multiSelectToggle" onclick="toggleMultiSelect()" style="padding: 5px 10px; border: none; border-radius: 5px; background-color: #ccc; cursor: pointer;">
                                        Off
                                    </button>
                                    <!-- Clear All Filters (Yellow) -->
                                    <button type="button" id="clearFiltersButton" class="icon-button " onclick="clearAllFilter()" disabled>
                                        <i class="fa-solid fa-broom"></i> Clear Filters
                                    </button>

                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto; position: relative;">
                            <div id="loadingOverlay" class="loading-overlay" style="display:none;">
                                <div>
                                    <div class="loading-spinner"></div>
                                    <div style="color:#007bff; font-weight:600;">Searching...</div>
                                </div>
                            </div>
                            <table class="table table-hover align-middle table-bordered" id="bookingTable">
                                <thead class="table-light sticky-top" style="z-index: 0;">
                                    <tr>
                                        <th>Select</th>
                                        <th style="min-width: 150px;">Date</th>
                                        <th style="min-width: 200px;">
                                            Hall Details 
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="openFilterPopup()" title="Filter Hall">
                                                <i class="bi bi-funnel"></i>
                                            </button>
                                        </th>
                                        <th style="min-width: 250px;">
                                            Belongs to 
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="openFilterPopup1()" title="Filter Owner">
                                                <i class="bi bi-funnel"></i>
                                            </button>
                                        </th>
                                        <th style="min-width: 200px;">
                                            Features 
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="openFilterPopup2()" title="Filter Features">
                                                <i class="bi bi-funnel"></i>
                                            </button>
                                        </th>
                                        <th style="min-width: 200px;">Incharge Details</th>
                                        <th style="min-width: 200px;">
                                            Status 
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="openFilterPopup4()" title="Filter Status">
                                                <i class="bi bi-funnel"></i>
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <?php
                                                $features = array_filter([
                                                    $row['wifi'], $row['ac'], $row['projector'], $row['smart_board'],
                                                    $row['computer'], $row['audio_system'], $row['podium'],
                                                    $row['white_board'], $row['blackboard'], $row['lift'], $row['ramp']
                                                ], fn($feature) => $feature != 'No');
                                                $features_string = implode(', ', $features);
                                            ?>
                                            <?php if ($row['status'] == "Active"): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="form-check-input hall-checkbox" value="<?php echo $row['hall_id']; ?>" onclick="handleCheckbox(this)">
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['from_date']); ?></td>
                                                    <td>
                                                        <strong class="text-primary"><?php echo htmlspecialchars($row['type_name']); ?></strong><br>
                                                        <?php echo htmlspecialchars($row['hall_name']); ?><br>
                                                        Capacity: <?php echo htmlspecialchars($row['capacity']); ?><br>
                                                        Floor: <?php echo htmlspecialchars($row['floor']); ?><br>
                                                        Zone: <?php echo htmlspecialchars($row['zone']); ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($row['section_id'])): ?>
                                                            <strong class="text-primary"><?php echo htmlspecialchars($row['school_name']); ?></strong><br>
                                                            <?php echo htmlspecialchars($row['department_name']); ?>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($row['section_name']); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($features_string); ?></td>
                                                    <td>
                                                        <strong class="text-primary"><?= htmlspecialchars($row['incharge_name']); ?></strong><br>
                                                        <?= htmlspecialchars($row['designation']); ?><br>
                                                        <?= htmlspecialchars($row['incharge_email']); ?><br>
                                                        <small class="text-muted">Ext: <?= htmlspecialchars($row['incharge_intercom']); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="text-center">
                                                            <span class="badge bg-success"><?= ucwords(htmlspecialchars($row['availability'])); ?></span>
                                                            <?php if (!empty($row['start_date']) && !empty($row['to_date'])): ?>
                                                                <div class="mt-1">
                                                                    <small class="text-muted">From: <strong><?= $row['start_date']; ?></strong></small><br>
                                                                    <small class="text-muted">To: <strong><?= $row['to_date']; ?></strong></small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No results found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!--availability Popup form -->
                        <div id="availabilityPopup" class="popup-form" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
                            <div class="popup-content" style="width: 475px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-btn" style="margin-right:12px; font-size: 30px;" onclick="closeFilterPopup('availabilityPopup')">&times;</span>
                                <h3 style="text-align: center; margin-bottom: 15px; color: #0555ca;">Set Availability</h3>
                                                           
                                <h4 class="form-section-title mt-1"></h4>

                                <form method="post" id="availabilityForm" action="modify_availability.php">
                                    <input type="hidden" id="hall_id" name="hall_id" value="">

                                    <!-- Radio buttons inside the form -->
                                    <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content:center; margin-bottom: 20px;">
                                        <input type="radio" style="width: 15px;" id="yes-option" name="available" value="Yes" checked>
                                        <label for="yes-option">Yes</label>

                                        <input type="radio" style="width: 15px; margin-left:4%;" id="no-option" name="available" value="No">
                                        <label for="no-option">No</label>
                                    </div>

                                    <div class="reason-section" style=" text-align: center; margin-top: 20px;">
                                        <label for="reason-dropdown">Reason:</label> <br>
                                        <select id="reason-dropdown" class="reason-input" name="reason-input" style="padding: 7px; width: 85%;">
                                            <option value="">--- Select Reason ---</option>
                                            <!-- <option value="Under Construction">Under Construction</option> -->
                                            <option value="Temporarily Unavailable">Temporarily Unavailable</option>
                                            <option value="Closed for Renovation">Closed for Renovation</option>
                                            <option value="Under Maintenance">Under Maintenance</option>
                                            <option value="Removed">Removed</option>
                                        </select>
                                    </div>

                                    <br>

                                    <!-- Semester Range Banner -->
                                    <div class="alert alert-info text-center mb-3" role="alert" style="padding: 10px; font-size: 14px;">
                                        <i class="bi bi-calendar-event me-2"></i>
                                        <strong>Semester Period:</strong> 
                                        <?= date('M d, Y', strtotime($semesterStart)) ?> - <?= date('M d, Y', strtotime($semesterEnd)) ?>
                                        <br>
                                        <small class="text-muted">Only dates within this period can be selected</small>
                                    </div>

                                    <div id="dateFields" style="display: block; margin-top: 20px; text-align: center;">
                                        <label for="Start">From:</label>
                                        <input type="date" 
                                               id="availabilityStart" 
                                               name="availabilityStart"
                                               min="<?= max($semesterStart, $today) ?>"
                                               max="<?= $semesterEnd ?>"
                                               onkeydown="return false">

                                        <label style="margin-left: 15px;" for="End">To:</label>
                                        <input type="date" 
                                               id="availabilityEnd" 
                                               name="availabilityEnd"
                                               min="<?= max($semesterStart, $today) ?>"
                                               max="<?= $semesterEnd ?>"
                                               onkeydown="return false">
                                    </div>
                                    <br>
                                    <button type="submit" name="availability_submit" style="width: 30%; display: block; margin: 20px auto;" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>

                        <!--Start Filter For All Section  -->
                        <!-- Start hall Details filter  -->
                        <div id="filterPopup" class="filter-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
                            <div class="filter-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; width:550px; padding: 30px 30px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-btn" style="margin-right:12px; font-size: 30px;" onclick="closeFilterPopup('filterPopup')">&times;</span>
                                <center>
                                    <h2 style="color: #007bff; margin-bottom:15px;">Filter Hall Details</h2>
                                </center>
                                <h4 class="form-section-title mt-3"></h4>

                                <label style="margin-left: 10px; margin-bottom:5px;  font-weight: 650;">Hall Type:</label><br>
                                <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; margin-left: 20px;">
                                    <label for="hallType1" style="width: 45%;">
                                        <input type="radio" id="hallType1" name="hallType" value="Seminar Hall"> Seminar Hall
                                    </label>
                                    <label for="hallType2" style="width: 45%;">
                                        <input type="radio" id="hallType2" name="hallType" value="Auditorium"> Auditorium
                                    </label>
                                    <label for="hallType3" style="width: 45%;">
                                        <input type="radio" id="hallType3" name="hallType" value="Lecture Hall"> Lecture Hall
                                    </label>
                                    <label for="hallType4" style="width: 45%;">
                                        <input type="radio" id="hallType4" name="hallType" value="Conference Hall"> Conference Hall
                                    </label>
                                </div>

                                <label for="hallName" style=" font-weight: 650; margin-left: 10px; margin-bottom:5px;">Hall Name:</label>
                                <input type="text" style=" margin-left:20px; margin-bottom:5px; width:95%;" id="hallName" name="hallname" placeholder="Enter Hall Name"><br>

                                <label style="margin-left: 10px; margin-bottom:5px; font-weight: 650;">Capacity:</label><br>
                                <div style="display: flex; gap: 15px; margin-bottom: 10px; margin-left:20px;">
                                    <label for="capacity1">
                                        <input type="radio" id="capacity1" name="capacity" value="50"> Less than 50
                                    </label>
                                    <label for="capacity2" style="margin-left: 30px;">
                                        <input type="radio" id="capacity2" name="capacity" value="100"> 50 to 100
                                    </label>
                                    <label for="capacity3" style="margin-left:30px;">
                                        <input type="radio" id="capacity3" name="capacity" value="101"> More than 100
                                    </label>
                                </div>

                                <label style="margin-left: 10px; margin-bottom:5px; font-weight: 650;">Floor:</label><br>
                                <div style="display: flex; gap: 15px; margin-bottom: 10px; margin-left:20px;">
                                    <label for="floor1">
                                        <input type="radio" id="floor1" name="floor" value="Ground Floor"> Ground Floor
                                    </label>
                                    <label for="floor2" style="margin-left: 25px;">
                                        <input type="radio" id="floor2" name="floor" value="First Floor"> First Floor
                                    </label>
                                    <label for="floor3" style="margin-left: 25px;">
                                        <input type="radio" id="floor3" name="floor" value="Second Floor"> Second Floor
                                    </label>
                                </div>

                                <label style="margin-left: 10px; margin-bottom:5px; font-weight: 650;">Zone:</label><br>
                                <div style="display: flex; gap: 15px; margin-bottom: 10px; margin-left:20px;">
                                    <label for="zone1">
                                        <input type="radio" id="zone1" name="zone" value="North"> North
                                    </label>
                                    <label for="zone2" style="margin-left: 30px;">
                                        <input type="radio" id="zone2" name="zone" value="South"> South
                                    </label>
                                    <label for="zone3" style="margin-left: 30px;">
                                        <input type="radio" id="zone3" name="zone" value="East"> East
                                    </label>
                                    <label for="zone4" style="margin-left: 30px;">
                                        <input type="radio" id="zone4" name="zone" value="West"> West
                                    </label>
                                </div>
                                <button class="btn btn-primary" onclick="applyFilter()" style="margin-top: 20px; margin-left: 40%;">Apply Filter</button>
                                <!-- <button class="btn btn-success" onclick="clearForm()" style="margin-top: 20px;">Clear All </button> -->
                            </div>
                        </div>
                        <!-- Start School/ Department/Section Filter -->
                        <div id="filterPopup1" class="filter-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
                            <div class="filter-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width:550px; background: white; padding:30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-btn" style=" font-size: 30px;" onclick="closeFilterPopup('filterPopup1')">&times;</span>
                                <center>
                                    <h3 style="color:#007bff; margin-top:20px;">Filter School/Department/Section</h3>
                                </center>
                                <h4 class="form-section-title mt-3"></h4>
                                <div style="margin-left: 85px;">
                                    <input type="radio" style="margin-right: 2px;" name="belongs_to" value="Department"
                                        onclick="toggleBelongsTo('Department')" required> Department
                                    <input type="radio" style="margin-left:15px;" name="belongs_to" value="School"
                                        onclick="toggleBelongsTo('School')" required> School
                                    <input type="radio" style="margin-left:15px;" name="belongs_to" value="Administration"
                                        onclick="toggleBelongsTo('Administration')" required> Administration
                                </div>

                                <!-- Dropdowns for Department -->

                                <div id="departmentFields" style="display: none;">

                                    <label class="form-label" style="margin-top: 8px;">School Name:</label>
                                    <select style="padding: 7px;" name="school_name" id="school_name">
                                        <option value="">Select School</option>

                                        <?php
                                        include 'assets/conn.php';
                                        $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['school_id'] . '">' . $row['school_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>

                                    <label class="form-label" style="margin-top: 8px; margin-right: 400px;">Department :</label>
                                    <select style="padding: 7px;" name="department_name" id="department_name">
                                        <option value="">Select Department</option>
                                    </select>
                                </div>

                                <!-- Dropdown for School -->
                                <div id="schoolField" style="display: none;">
                                    <label class="form-label" style="margin-top: 8px; margin-right: 400px;">School Name:</label>
                                    <select style="padding: 7px;" name="school_name_school" id="school_name_school">
                                        <option value="">Select School</option>
                                        <?php
                                        include 'assets/conn.php';
                                        $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($school = $result->fetch_assoc()) {
                                                echo '<option value="' . $school['school_id'] . '">' . $school['school_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>


                                <!-- Dropdown for Section (Administration) -->
                                <div id="sectionField" style="display: none;">
                                    <label class="form-label" style="margin-top: 8px; margin-right: 430px;">Section:</label>
                                    <select style="padding: 7px;" name="section">
                                        <option value="">Select Section</option>
                                        <?php
                                        include 'assets/conn.php';
                                        $sql = "SELECT section_name, section_id FROM section";
                                        $result = $conn->query($sql);

                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['section_id'] . '">' . $row['section_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div><br>
                                <button class="btn btn-primary" onclick="applyFilter1()" style="margin-top: 20px; margin-left: 40%;">Apply Filter</button>
                                <!-- <button class="btn btn-success" onclick="clearForm()" style="margin-top: 20px;">Clear All</button> -->
                            </div>
                        </div>
                        <!-- Start Feature Filter -->
                        <div id="filterPopup2" class="filter-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
                            <div class="filter-content" style="position: absolute; top: 50%; left: 50%; width:570px; transform: translate(-50%, -50%); background: white; padding:30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-btn" style="margin-right:12px; font-size: 30px;" onclick="closeFilterPopup('filterPopup2')">&times;</span>
                                <center>
                                    <h3 style="color:#007bff; margin-top:20px;">Features</h3>
                                </center>
                                <h4 class="form-section-title mt-3"></h4>

                                <!-- <label for="hallType" style="margin-right: 320px;">Features:</label> -->
                                <div class="row mb-3 features-container">
                                    <!-- AC -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="AC" id="ac"> AC
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Projector -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Projector" id="projector"> Projector
                                            </label>
                                        </div>
                                    </div>
                                    <!-- WIFI -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="WiFi"> WIFI
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Podium -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Podium" id="podium"> Podium
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Computer -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Computer" id="computer"> Computer
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Lift -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Lift"> Lift
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Ramp -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Ramp" id="ramp"> Ramp
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Audio System -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="AudioSystem" id="audio_system"> Audio
                                            </label>
                                        </div>
                                    </div>
                                    <!-- White Board -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Whiteboard" id="white_board"> White Board
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Black Board -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Blackboard" id="blackboard"> Black Board
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Smart Board -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="preference[]" value="Smartboard" id="smartboard"> Smart Board
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary" onclick="applyFilter2()" style="margin-top: 20px; margin-left: 40%;">Apply Filter</button>
                                <!-- <button class="btn btn-success" onclick="clearForm()" style="margin-top: 20px;">Clear All</button> -->
                            </div>
                        </div>
                        <!-- Start Availability Filter -->
                        <div id="filterPopup4" class="filter-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
                            <div class="filter-content" style="position: absolute; top: 50%; left: 50%; width: 570px; transform: translate(-50%, -50%); background: white; padding: 30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-btn" style="margin-right:12px; font-size: 30px;" onclick="closeFilterPopup('filterPopup4')">&times;</span>
                                <center>
                                    <h3 style="color:#007bff; margin-top:20px;">Available</h3>
                                </center>
                                <h4 class="form-section-title mt-3"></h4>

                                <!-- <label for="hallType" style="margin-right: 330px;">Availability:</label> -->
                                <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 10px; margin-left: 50px;">
                                    <label for="available1" class="feature-item" style="flex: 1 0 45%;">
                                        <input type="radio" id="available1" name="availability" value="Yes"> Yes
                                    </label>
                                    <label for="available2" style="flex: 1 0 45%;">
                                        <input type="radio" id="available2" name="availability" value="Under Construction"> Under Construction
                                    </label>
                                    <label for="available3" style="flex: 1 0 45%;">
                                        <input type="radio" id="available3" name="availability" value="Temporarily Unavailable"> Temporarily Unavailable
                                    </label>
                                    <label for="available4" style="flex: 1 0 45%;">
                                        <input type="radio" id="available4" name="availability" value="Under Maintenance"> Under Maintenance
                                    </label>
                                    <label for="available5" style="flex: 1 0 45%;">
                                        <input type="radio" id="available5" name="availability" value="Closed for Renovation"> Closed for Renovation
                                    </label>
                                    <label for="available6" style="flex: 1 0 51%; padding-left: 16px;">
                                        <input type="radio" id="available6" name="availability" value="Removed"> Removed
                                    </label>
                                </div>
                                <button class="btn btn-primary" onclick="applyFilter4()" style="margin-top: 20px; margin-left: 32%;">Apply Filter</button>
                                <button class="btn btn-success" onclick="clearForm()" style="margin-top: 20px;">Clear All</button>
                            </div>
                        </div>

                        <!-- Archive Confirmation Popup -->
                        <div id="archivePopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); padding: 20px;  z-index: 1000;">
                            <div class="popup-content" style="width: 475px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-button" style="margin-right:12px; font-size: 30px;" onclick="closeArchivePopup()">&times;</span>
                                <h2 style="color: #007bff;">Confirm Archive</h2>
                                <h4 class="form-section-title mt-1"></h4>
                                <form method="post" id="archivedForm">
                                    <input type="hidden" id="selectedHallId" name="selectedHallId" value="">

                                    <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content:center;">
                                        <input type="radio" style="width: 15px;" id="Archived" name="status" value="Archived" onchange="toggleReasonSection()" checked>
                                        <label for="Archived">Archived</label>
                                    </div>

                                    <!-- Reason dropdown (Hidden initially) -->
                                    <div id="reasonSection" style=" text-align: center; margin-top: 20px;">
                                        <select id="archived-reason" name="reason" class="reason-input" style="padding: 7px; width: 350px;">
                                            <option value="">--- Select Reason ---</option>
                                            <option value="Combined with Neighbour Class">Combined with Neighbour Class</option>
                                            <option value="Convert for Other Purpose">Convert for Other Purpose</option>
                                            <option value="Removed Permanently">Removed Permanently</option>
                                        </select>
                                    </div>

                                    <button type="button" class="btn btn-primary" style="margin-top: 20px; width: 40%;" onclick="archiveHall()"> Archive</button>
                                    <button type="button" class="btn btn-danger" onclick="closeArchivePopup()" style="margin-top: 20px; width: 40%;">Cancel</button>
                                </form>
                            </div>
                        </div>
                        <!-- Ownership Transfer Popup -->
                        <div id="ownershipTransferPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); padding: 20px;  z-index: 1000;">
                            <div class="popup-content" style="width: 475px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">

                                <span class="close-btn" style="margin-right:12px; font-size: 30px;" onclick="closeFilterPopup('ownershipTransferPopup')">&times;</span>

                                <h2 style="color: #007bff;">Transfer Ownership</h2>
                                <h4 class="form-section-title mt-3"></h4>


                                <input type="hidden" id="hallId" name="hall_id"> <!-- Hidden input to store selected hall ID -->
                                <div>
                                    <input type="radio" style="margin-right: 2px; " name="belongs" value="Department" onclick="toggleBelongs('Department')" required checked> Department
                                    <input type="radio" style="margin-left:15px;" name="belongs" value="School" onclick="toggleBelongs('School')" required> School
                                    <input type="radio" style="margin-left:15px;" name="belongs" value="Administration" onclick="toggleBelongs('Administration')" required> Administration
                                </div>

                                <!-- Dropdowns for Department -->
                                <div id="departments" style="display: block;">
                                    <label class="form-label" style="margin-top: 8px; display:block ; text-align: left;">School Name:</label>
                                    <select style="padding: 7px;" name="newSchool" id="newSchool">
                                        <option value="">--- Select School ---</option>
                                        <?php
                                        include 'assets/conn.php';
                                        $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                                        $result = $conn->query($sql);
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['school_id'] . '">' . $row['school_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>

                                    <label class="form-label" style="margin-top: 8px; display:block ; text-align: left;">Department :</label>
                                    <select style="padding: 7px;" name="newDepartment" id="newDepartment">
                                        <option value="">--- Select Department ---</option>
                                    </select>
                                </div>

                                <!-- Dropdown for School -->
                                <div id="schools" style="display: none;">
                                    <label class="form-label" style="margin-top: 8px;">School Name:</label>
                                    <select style="padding: 7px;" name="newSchoolOnly" id="newSchoolOnly">
                                        <option value="">--- Select School ---</option>
                                        <?php
                                        include 'assets/conn.php';
                                        $sql = "SELECT DISTINCT school_name, school_id FROM schools";
                                        $result = $conn->query($sql);
                                        if ($result->num_rows > 0) {
                                            while ($school = $result->fetch_assoc()) {
                                                echo '<option value="' . $school['school_id'] . '">' . $school['school_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Dropdown for Section (Administration) -->
                                <div id="sections" style="display: none;">
                                    <label class="form-label" style="margin-top: 8px;">Section:</label>
                                    <select style="padding: 7px;" name="newSection" id="newSection">
                                        <option value="">--- Select Section ---</option>
                                        <?php
                                        include 'assets/conn.php';
                                        $sql = "SELECT section_name, section_id FROM section";
                                        $result = $conn->query($sql);
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                echo '<option value="' . $row['section_id'] . '">' . $row['section_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div><br>

                                <!-- Action Buttons -->
                                 <div style="margin-top:5px">
                                    <button onclick="transferOwnership()" class="transfer-ownership-button" style="width: 30%; margin-right:10px">Transfer</button>
                                    <button onclick="closePopup()" class="transfer-ownership-cancel-button" style="width: 30%; ">Cancel</button>
                                </div>
                            </div>
                        </div>

                        <!-- update features  -->
                        <div id="updateFeatureForm" class="filter-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000;">
                            <div class="filter-content" style="position: absolute; top: 50%; left: 50%; width:570px; transform: translate(-50%, -50%); background: white; padding:30px 20px; border-radius: 10px; box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2);">
                                <span class="close-btn" style="margin-right:12px; font-size: 30px;" onclick="closeFeatureForm()">&times;</span>
                                <center>
                                    <h2 style="color:#007bff; margin-top:20px;">Modify Features</h2>
                                </center>
                                <h4 class="form-section-title mt-1"></h4>
                                <input type="hidden" id="hallId" name="hallId" value="">
                                <div class="row mb-3 features-container">
                                    <!-- AC -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="AC" id="ac"> AC
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Projector -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Projector" id="projector"> Projector
                                            </label>
                                        </div>
                                    </div>
                                    <!-- WIFI -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="WiFi"> WIFI
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Podium -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Podium" id="podium"> Podium
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Computer -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Computer" id="computer"> Computer
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Lift -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Lift"> Lift
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Ramp -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Ramp" id="ramp"> Ramp
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Audio System -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="AudioSystem" id="audio_system"> Audio
                                            </label>
                                        </div>
                                    </div>
                                    <!-- White Board -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Whiteboard" id="white_board"> White Board
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Black Board -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Blackboard" id="blackboard"> Black Board
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Smart Board -->
                                    <div class="col-md-4 feature-item">
                                        <div class="form-check">
                                            <label class="feature">
                                                <input type="checkbox" class="prefer" name="feature[]" value="Smartboard" id="smartboard"> Smart Board
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <button class="btn btn-primary" onclick="updateFeatures()" style="margin-top: 20px; margin-left: 32%;">Update Features</button>
                                <button class="btn btn-success" onclick="clearForm()" style="margin-top: 20px;">Clear All</button>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Clear form data  -->
    <script>
        function clearForm() {
            let filterPopup = document.getElementById("filterPopup"); // Get the filter popup container

            if (filterPopup) {
                // Reset all text inputs and dropdowns
                filterPopup.querySelectorAll('input[type="text"], select').forEach(input => {
                    input.value = "";
                });

                // Uncheck all radio buttons and checkboxes
                filterPopup.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(input => {
                    input.checked = false;
                });
            }
        }
    </script>

    <!-- belongs To   -->
    <script>
        // Search handling with 2s loading overlay and client-side filter
        (function setupSearch() {
            function performSearch() {
                const raw = (document.getElementById('hallSearch')?.value || '');
                const query = raw.trim().toLowerCase();
                if (query.length === 0) {
                    // If empty, do nothing per requirement (no auto-reset)
                    return;
                }
                const overlay = document.getElementById('loadingOverlay');
                if (!overlay) return;

                overlay.style.display = 'flex';

                setTimeout(function() {
                    try {
                        const tbody = document.querySelector('#bookingTable tbody');
                        if (tbody) {
                            const existing = tbody.querySelector('#noResultsRow');
                            if (existing) existing.remove();
                        }
                        const rows = document.querySelectorAll('#bookingTable tbody tr');
                        rows.forEach(function(row) {
                            // Skip non-active rows guard
                            if (!row || !row.cells || row.cells.length === 0) return;

                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(query) ? '' : 'none';
                        });
                        // Show placeholder when nothing matches
                        const visibleCount = Array.from(rows).filter(function(r){ return r && r.style.display !== 'none'; }).length;
                        if (visibleCount === 0 && tbody) {
                            const thCount = document.querySelectorAll('#bookingTable thead th').length || 1;
                            const tr = document.createElement('tr');
                            tr.id = 'noResultsRow';
                            const td = document.createElement('td');
                            td.colSpan = thCount;
                            td.className = 'text-center text-muted';
                            td.textContent = 'No Results Found';
                            tr.appendChild(td);
                            tbody.appendChild(tr);
                        }
                        // Enable the existing Clear Filters button after search
                        var clearBtn = document.getElementById('clearFiltersButton');
                        if (clearBtn) clearBtn.disabled = false;
                    } finally {
                        overlay.style.display = 'none';
                    }
                }, 2000);
            }

            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('hallSearchBtn');
                const input = document.getElementById('hallSearch');
                const suggestions = document.getElementById('hallSearchSuggestions');
                let hallNamesCache = [];

                function extractUniqueHallNames() {
                    const names = [];
                    const rows = document.querySelectorAll('#bookingTable tbody tr');
                    rows.forEach(function(row){
                        // Hall name is in Hall Details column: contains a strong type then line break then hall name
                        const tds = row.querySelectorAll('td');
                        if (tds && tds.length >= 3) {
                            const hallDetailsTd = tds[2];
                            // Capture text nodes after the first <br> following the type
                            const lines = hallDetailsTd.innerText.split('\n').map(s => s.trim()).filter(Boolean);
                            if (lines.length >= 2) {
                                const name = lines[1];
                                if (name) names.push(name);
                            }
                        }
                    });
                    // Unique & sort
                    const unique = Array.from(new Set(names));
                    unique.sort((a,b)=>a.localeCompare(b));
                    return unique;
                }

                function renderSuggestions(query) {
                    if (!suggestions) return;
                    if (!query || query.trim() === '') { suggestions.style.display = 'none'; suggestions.innerHTML=''; return; }
                    if (!hallNamesCache.length) hallNamesCache = extractUniqueHallNames();
                    const q = query.toLowerCase();
                    const matches = hallNamesCache.filter(n => n.toLowerCase().includes(q)).slice(0,8);
                    if (matches.length === 0) { suggestions.style.display = 'none'; suggestions.innerHTML=''; return; }
                    suggestions.innerHTML = matches.map(m => '<div class="item" data-value="'+m.replace(/"/g,'&quot;')+'">'+m.replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>').join('');
                    suggestions.style.display = 'block';
                }

                function hideSuggestions() {
                    if (suggestions) { suggestions.style.display = 'none'; suggestions.innerHTML=''; }
                }

                if (btn) btn.addEventListener('click', performSearch);
                if (input) {
                    input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); performSearch(); hideSuggestions(); }});
                    input.addEventListener('input', function(){ renderSuggestions(input.value); });
                    input.addEventListener('focus', function(){ renderSuggestions(input.value); });
                    document.addEventListener('click', function(ev){ if (!ev.target.closest('.search-suggestions-wrapper')) hideSuggestions(); });
                }

                if (suggestions) {
                    suggestions.addEventListener('click', function(e){
                        const target = e.target.closest('.item');
                        if (!target) return;
                        const val = target.getAttribute('data-value') || target.textContent;
                        const field = document.getElementById('hallSearch');
                        if (field) field.value = val;
                        hideSuggestions();
                        if (btn) btn.click();
                    });
                }
            });
        })();
        function toggleBelongsTo(value) {
            if (value === 'Department') {
                document.getElementById('departmentFields').style.display = 'block';
                document.getElementById('schoolField').style.display = 'none';
                document.getElementById('sectionField').style.display = 'none';
            } else if (value === 'School') {
                document.getElementById('schoolField').style.display = 'block';
                document.getElementById('departmentFields').style.display = 'none';
                document.getElementById('sectionField').style.display = 'none';
            } else if (value === 'Administration') {
                document.getElementById('sectionField').style.display = 'block';
                document.getElementById('departmentFields').style.display = 'none';
                document.getElementById('schoolField').style.display = 'none';
            }
        }
    </script>

    <!-- Archive and modify halls  -->
    <script>
        // Enable/disable action buttons based on hall selection
        function updateActionButtonsState() {
            const hasSelection = document.querySelectorAll('.hall-checkbox:checked').length > 0;
            const ids = ['availabilityBtn', 'ownershipBtn', 'modifyBtn', 'modifyFeaturesBtn', 'archiveBtn'];
            ids.forEach(function(id) {
                const btn = document.getElementById(id);
                if (btn) {
                    btn.disabled = !hasSelection;
                }
            });
        }

        // Delegate change event to catch dynamically loaded checkboxes as well
        document.addEventListener('change', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('hall-checkbox')) {
                updateActionButtonsState();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            updateActionButtonsState();
        });

        // Function to achieve selected halls

        function updateMultipleStatus() {
            const selectedHalls = Array.from(document.querySelectorAll('.hall-checkbox:checked'))
                .map(checkbox => checkbox.value);



            if (selectedHalls.length === 0) {
                alert('Please select at least one hall to archive.');
                return;
            }

            // Store selected hall IDs as a comma-separated string
            document.getElementById("selectedHallId").value = selectedHalls.join(',');

            // Open the popup
            document.getElementById("archivePopup").style.display = "flex";
        }

        function closeArchivePopup() {
            document.getElementById("archivePopup").style.display = "none";
        }

        // Toggle reason section when selecting "Archived"



        document.addEventListener("DOMContentLoaded", function() {
            const statusRadios = document.querySelectorAll('input[name="status"]');
            const reasonDropdown = document.getElementById("archived-reason");

            // Function to enable/disable dropdown based on selected status
            function toggleReasonDropdown() {
                const selectedStatus = document.querySelector('input[name="status"]:checked');
                if (selectedStatus && selectedStatus.value === "Archived") {
                    reasonDropdown.removeAttribute("disabled"); // Enable dropdown
                } else {
                    reasonDropdown.setAttribute("disabled", "true"); // Disable dropdown
                    reasonDropdown.value = ""; // Clear selected value when disabled
                }
            }

            // Attach event listener to all status radio buttons
            statusRadios.forEach(radio => {
                radio.addEventListener("change", toggleReasonDropdown);
            });

            // Initial state on page load
            toggleReasonDropdown();
        });

        function archiveHall() {
            const hallId = document.getElementById("selectedHallId").value;
            const status = document.querySelector('input[name="status"]:checked');
            const reasonDropdown = document.getElementById("archived-reason");

            if (!hallId) {
                alert("No hall selected.");
                return;
            }

            if (!status) {
                alert("Please select a status.");
                return;
            }

            let reason = "";
            if (status.value === "Archived") {
                reason = reasonDropdown ? reasonDropdown.value.trim() : "";
                if (reason === "") {
                    alert("Please select a reason for archiving.");
                    return;
                }
            }

            // Prepare data for AJAX
            const formData = {
                hall_id: hallId,
                status: status.value,
                reason: reason
            };

            fetch('archived_hall.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Hall Archieved Successfully!');
                        window.location.href = 'view_modify_hall.php?hall_archived=1';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
        }




        // Function to modify a selected hall
        function modifySelected() {
            const selected = document.querySelectorAll('.hall-checkbox:checked');

            if (selected.length === 0) {
                alert("Please select a hall to modify.");
                return;
            }

            if (selected.length > 1) {
                alert("You can modify only one hall at a time.");
                return;
            }
            // if (!confirm('Are you sure you want to modify the selected halls?')) {
            //     return;
            // }
            const hallId = selected[0].value;
            window.location.href = `modify_hall.php?id=${hallId}`;
        }
    </script>

    <!-- fetch the departments  -->
    <script>
        $(document).ready(function() {
            $('#school_name').on('change', function() {
                var school_id = $(this).val();

                $('#department_name').html('<option value="">Select Department</option>');

                if (school_id) {
                    $.ajax({
                        type: 'POST',
                        url: 'fetch_department.php',
                        data: {
                            school_id: school_id
                        },
                        success: function(response) {
                            $('#department_name').html(response);
                        },
                        error: function(xhr, status, error) {
                            console.log("An error occurred: " + error);
                        }
                    });
                }
            });
        });
    </script>


    <!-- filter the data and clear all filter -->
    <script>
        // Track filter states
        let filters = {
            hallDetails: false,
            belongsTo: false,
            features: false,
            status: false
        };

        // Function to update filter icons and clear button
        function updateFilterUI() {
            const hallDetailsIcon = document.getElementById('hallDetailsFilterIcon');
            const belongsToIcon = document.getElementById('belongsToFilterIcon');
            const featuresIcon = document.getElementById('featuresFilterIcon');
            const statusIcon = document.getElementById('statusFilterIcon');
            const clearFiltersButton = document.getElementById('clearFiltersButton');

            // Update filter icons
            if (hallDetailsIcon) hallDetailsIcon.style.filter = filters.hallDetails ? 'brightness(0.5) sepia(1) hue-rotate(300deg) saturate(5)' : 'none';
            if (belongsToIcon) belongsToIcon.style.filter = filters.belongsTo ? 'brightness(0.5) sepia(1) hue-rotate(300deg) saturate(5)' : 'none';
            if (featuresIcon) featuresIcon.style.filter = filters.features ? 'brightness(0.5) sepia(1) hue-rotate(300deg) saturate(5)' : 'none';
            if (statusIcon) statusIcon.style.filter = filters.status ? 'brightness(0.5) sepia(1) hue-rotate(300deg) saturate(5)' : 'none';

            // Enable/disable clear filters button
            const isAnyFilterApplied = Object.values(filters).some(value => value === true);
            if (clearFiltersButton) clearFiltersButton.disabled = !isAnyFilterApplied;
        }

        // Function to open filter popups
        function openFilterPopup() {
            document.getElementById('filterPopup').style.display = 'block';
        }

        function openFilterPopup1() {
            document.getElementById('filterPopup1').style.display = 'block';
        }

        function openFilterPopup2() {
            document.getElementById('filterPopup2').style.display = 'block';
        }

        function openFilterPopup4() {
            document.getElementById('filterPopup4').style.display = 'block';
        }

        function closeFilterPopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
        }

        // Function to apply Hall Details filter
        function applyFilter() {
            var hallType = $('input[name="hallType"]:checked').val();
            var hallName = $('#hallName').val();
            var capacity = $('input[name="capacity"]:checked').val();
            var floor = $('input[name="floor"]:checked').val();
            var zone = $('input[name="zone"]:checked').val();

            $.ajax({
                url: 'filter_halls.php',
                type: 'POST',
                data: {
                    hallType: hallType,
                    hallName: hallName,
                    capacity: capacity,
                    floor: floor,
                    zone: zone
                },
                success: function(response) {
                    $('#bookingTable tbody').html(response); // Update table body with filtered results
                    closeFilterPopup('filterPopup'); // Close filter popup
                    filters.hallDetails = true; // Update filter state
                    updateFilterUI(); // Update UI
                },
                error: function() {
                    alert('Error retrieving filtered data.');
                }
            });
        }

        // Function to apply Belongs To filter
        function applyFilter1() {
            var belongsTo = $('input[name="belongs_to"]:checked').val();
            var schoolName = $('#school_name').val();
            var onlySchoolName = $('#school_name_school').val();
            var departmentName = $('#department_name').val();
            var section = $('#section').val();

            $.ajax({
                url: 'filter_belongs_to.php',
                type: 'POST',
                data: {
                    belongs_to: belongsTo,
                    schoolName: schoolName,
                    departmentName: departmentName,
                    onlySchoolName: onlySchoolName,
                    section: section
                },
                success: function(response) {
                    $('#bookingTable tbody').html(response); // Update table body with filtered results
                    closeFilterPopup('filterPopup1'); // Close filter popup
                    filters.belongsTo = true; // Update filter state
                    updateFilterUI(); // Update UI
                },
                error: function() {
                    alert('Error retrieving filtered data.');
                }
            });
        }

        // Function to apply Features filter
        function applyFilter2() {
            var features = [];
            $('input[name="feature[]"]:checked').each(function() {
                features.push($(this).val());
            });

            $.ajax({
                url: 'filter_features.php',
                type: 'POST',
                data: {
                    features: features
                },
                success: function(response) {
                    $('#bookingTable tbody').html(response); // Update table body with filtered results
                    closeFilterPopup('filterPopup2'); // Close filter popup
                    filters.features = true; // Update filter state
                    updateFilterUI(); // Update UI
                },
                error: function() {
                    alert('Error retrieving filtered data.');
                }
            });
        }
        // Function to apply Availability filter
        function applyFilter4() {
            var availability = $('input[name="availability"]:checked').val();

            if (!availability) {
                alert("Please select an availability option.");
                return;
            }

            console.log("Selected Availability:", availability); // Debugging

            $.ajax({
                url: 'filter_availability.php',
                type: 'POST',
                data: {
                    availability: availability
                },
                success: function(response) {
                    $('#bookingTable tbody').html(response);
                    closeFilterPopup('filterPopup4');
                    filters.status = true;
                    updateFilterUI();
                    // console.log("AJAX Response:", response);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: " + status + " - " + error);
                    alert('Error retrieving filtered data.');
                }
            });
        }
        // clear all filters
        function clearAllFilter() {
            filters = {
                hallDetails: false,
                belongsTo: false,
                features: false,
                status: false
            };

            // Clear form inputs including search
            $('input[type="radio"]').prop('checked', false);
            $('input[type="checkbox"]').prop('checked', false);
            $('input[type="text"]').val('');
            $('select').val('');

            // Explicitly clear search input
            const searchInput = document.getElementById('hallSearch');
            if (searchInput) searchInput.value = '';

            location.reload();
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateFilterUI();
        });
    </script>

    <!-- availability radio button  -->
    <script>
        document.querySelectorAll('input[name="available"]').forEach(function(radio) {
            document.addEventListener("DOMContentLoaded", function() {
                const yesOption = document.getElementById("yes-option");
                const noOption = document.getElementById("no-option");
                const reasonDropdown = document.getElementById("reason-dropdown");
                const availabilityStart = document.getElementById("availabilityStart");
                const availabilityEnd = document.getElementById("availabilityEnd");

                function toggleFields() {
                    const reasonSection = document.querySelector('.reason-section');
                    const semesterBanner = document.querySelector('.alert.alert-info');
                    const dateFields = document.getElementById('dateFields');
                    const submitButton = document.querySelector('button[name="availability_submit"]');
                    
                    if (yesOption.checked) {
                        // Hide form fields when "Yes" is selected
                        reasonSection.style.display = 'none';
                        semesterBanner.style.display = 'none';
                        dateFields.style.display = 'none';
                        submitButton.style.display = 'block'; // Keep submit button visible
                    } else {
                        // Show form fields when "No" is selected
                        reasonSection.style.display = 'block';
                        semesterBanner.style.display = 'block';
                        dateFields.style.display = 'block';
                        submitButton.style.display = 'block';
                        reasonDropdown.disabled = false;
                        availabilityStart.disabled = false;
                        availabilityEnd.disabled = false;
                    }
                }

                // Initial check on page load
                toggleFields();

                // Add event listeners to toggle based on selection
                yesOption.addEventListener("change", toggleFields);
                noOption.addEventListener("change", toggleFields);
            });
        });

        function openAvailabilityPopup() {
            const selectedHall = document.querySelectorAll('.hall-checkbox:checked');

            if (selectedHall.length == 0) {
                alert("Please select a hall to modify the Availability.");
                return;
            }

            if (selectedHall.length > 1) {
                alert("You can modify the Availability for only one hall at a time.");
                return;
            }
            // console.log(selectedHall)

            const hallId = selectedHall[0].value;
            document.getElementById('hall_id').value = hallId;

            // Set up date restrictions when popup opens
            setupDateRestrictions();

            document.getElementById('availabilityPopup').style.display = 'block';
        }

        // Function to setup date restrictions
        function setupDateRestrictions() {
            const availabilityStart = document.getElementById('availabilityStart');
            const availabilityEnd = document.getElementById('availabilityEnd');
            
            const semesterStart = '<?= $semesterStart ?>';
            const semesterEnd = '<?= $semesterEnd ?>';
            const today = new Date().toISOString().split('T')[0];
            
            // Compute effective minimum date (later of today or semester start)
            const effectiveMin = (semesterStart < today) ? today : semesterStart;
            
            // Set min and max boundaries
            availabilityStart.min = effectiveMin;
            availabilityStart.max = semesterEnd;
            availabilityEnd.min = effectiveMin;
            availabilityEnd.max = semesterEnd;
            
            // Add validation on date change
            availabilityStart.addEventListener('change', function() {
                if (this.value < effectiveMin) this.value = effectiveMin;
                if (this.value > semesterEnd) this.value = semesterEnd;
                
                // Update end date min to start date
                if (availabilityEnd.value < this.value) availabilityEnd.value = this.value;
                availabilityEnd.min = this.value || effectiveMin;
            });
            
            availabilityEnd.addEventListener('change', function() {
                if (this.value > semesterEnd) this.value = semesterEnd;
                if (this.value < effectiveMin) this.value = effectiveMin;
                
                // Update start date max to end date
                if (availabilityStart.value > this.value) availabilityStart.value = this.value;
                availabilityStart.max = this.value || semesterEnd;
            });
        }
    </script>

    <!-- Ownership Transfer  -->
    <script>
        function openOwnershipPopup() {
            const selectedHall = document.querySelectorAll('.hall-checkbox:checked');

            if (selectedHall.length == 0) {
                alert("Please select a hall before transferring ownership.");
                return;
            }
            if (selectedHall.length > 1) {
                alert("You can transfer ownership for one hall at a time.");
                return;
            }

            const hallId = selectedHall[0].value; // Get the selected hall's ID

            document.getElementById('hallId').value = hallId; // Assign the ID to hidden input

            // Open the popup
            document.getElementById('ownershipTransferPopup').style.display = 'block';
        }

        // Close popup
        function closePopup() {
            document.getElementById('ownershipTransferPopup').style.display = 'none';
        }

        // Show/hide fields based on radio selection
        function toggleBelongs(value) {
            document.getElementById('departments').style.display = value === 'Department' ? 'block' : 'none';
            document.getElementById('schools').style.display = value === 'School' ? 'block' : 'none';
            document.getElementById('sections').style.display = value === 'Administration' ? 'block' : 'none';
        }

        // Fetch departments when school is selected
        document.getElementById('newSchool').addEventListener('change', function() {
            let schoolId = this.value;

            if (schoolId) {
                fetch('fetch_dept.php?school_id=' + schoolId)
                    .then(response => response.json())
                    .then(data => {
                        let departmentSelect = document.getElementById('newDepartment');
                        departmentSelect.innerHTML = '<option value="">All Department</option>';
                        data.forEach(dept => {
                            let option = document.createElement('option');
                            option.value = dept.department_id;
                            option.textContent = dept.department_name;
                            departmentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching departments:', error));
            } else {
                document.getElementById('newDepartment').innerHTML = '<option value="">All Department</option>';
            }
        });

        // Transfer ownership and update hall_details
        function transferOwnership() {
            let hallId = document.getElementById('hallId').value;
// echo "hallId";
            let belongsTo = document.querySelector('input[name="belongs"]:checked');

            if (!belongsTo) {
                alert("Please select an ownership type.");
                return;
            }

            let data = {
                hall_id: hallId,
                belongs_to: belongsTo.value
            };

            if (belongsTo.value === "Department") {
                data.belongsTo;
                data.school_id = document.getElementById('newSchool').value;
                data.department_id = document.getElementById('newDepartment').value;
            } else if (belongsTo.value === "School") {
                data.belongsTo;
                data.school_id = document.getElementById('newSchoolOnly').value;
            } else if (belongsTo.value === "Administration") {
                data.section_id = document.getElementById('newSection').value;
            }

            console.log("Data before sending:", data); // Debugging line

            $.ajax({
                url: 'update_ownership.php',
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log("Response from server:", response); // Debugging line
                    alert('Hall Ownership Transfered Successfully!');
                    closePopup();
                    window.location.href = 'view_modify_hall.php?ownership_transferred=1';
                },
                error: function() {
                    alert("An error occurred while transferring ownership.");
                }
            });
        }
    </script>


    <!--handle multiple & single selection at a time  -->
    <script>
        let isMultiSelectEnabled = false;

        function toggleMultiSelect() {
            const toggleButton = document.getElementById('multiSelectToggle');
            isMultiSelectEnabled = !isMultiSelectEnabled;

            if (isMultiSelectEnabled) {
                toggleButton.textContent = 'On';
                toggleButton.style.backgroundColor = '#4CAF50';
                toggleButton.style.color = '#fff';
            } else {
                toggleButton.textContent = 'Off';
                toggleButton.style.backgroundColor = '#ccc';
                toggleButton.style.color = '#000';
            }

            const checkboxes = document.querySelectorAll('.hall-checkbox');
            checkboxes.forEach((cb) => {
                cb.checked = false;
                cb.disabled = false;
            });

            updateActionButtonsState();
        }

        // Function to handle checkbox clicks
        function handleCheckbox(checkbox) {
            const checkboxes = document.querySelectorAll('.hall-checkbox');

            if (!isMultiSelectEnabled) {
                if (checkbox.checked) {
                    checkboxes.forEach((cb) => {
                        if (cb !== checkbox) {
                            cb.disabled = true;
                        }
                    });
                } else {
                    checkboxes.forEach((cb) => {
                        cb.disabled = false;
                    });
                }
            }
            updateActionButtonsState();
        }
    </script>

    <!-- Update the features  -->
    <script>
        function openFeatureForm() {
            const selectedHalls = document.querySelectorAll('.hall-checkbox:checked');

            if (selectedHalls.length === 0) {
                alert("Please select a hall before modifying features.");
                return;
            }

            if (selectedHalls.length > 1) {
                alert("You can modify features for one hall at a time.");
                return;
            }

            const hallId = selectedHalls[0].value;

            document.getElementById('hallId').value = hallId;


            $('#updateFeatureForm').fadeIn(200);

            // Fetch features 
            $.ajax({
                url: 'get_features.php',
                type: 'POST',
                data: {
                    hall_id: hallId
                },
                dataType: 'json',
                success: function(response) {
                    // console.log("AJAX Response:", response);

                    if (response.error) {
                        alert(response.error);
                        return;
                    }

                    $(".prefer").prop("checked", false);

                    response.forEach(function(feature) {
                        $("input[value='" + feature + "']").prop("checked", true);
                    });
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText);
                    alert("Error fetching features: " + xhr.responseText);
                }
            });

        }

        // Close the popup 
        function closeFeatureForm() {
            $('#updateFeatureForm').fadeOut(200);
        }

        // Update selected features
        function updateFeatures() {
            let allFeatures = {};

            // Loop through all checkboxes, checked or not
            $("input[name='feature[]']").each(function () {
                const featureName = $(this).val();
                const isChecked = $(this).is(":checked");

                allFeatures[featureName] = isChecked ? featureName : "No";
            });

            const hallId = document.getElementById('hallId').value;

            $.ajax({
                type: "POST",
                url: "update_features.php",
                data: {
                    hall_id: hallId,
                    features: allFeatures // Send full feature state
                },
                dataType: "json",
                success: function(response) {
                    console.log("AJAX Response:", response);
                    if (response.status === "success") {
                        alert("Features updated successfully!");
                        closeFeatureForm();
                        location.reload();
                    } else {
                        alert("Error updating features: " + response.message);
                    }
                },
                error: function(xhr) {
                    console.error("AJAX Error:", xhr.responseText);
                    alert("Error updating features. Please try again.");
                }
            });
        }

        // Clear all checkboxes
        function clearForm() {
            $(".prefer").prop("checked", false);
        }
    </script>

    <!-- Success message animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const modifiedMessage = document.getElementById('modifiedMessage');
            const availabilityMessage = document.getElementById('availabilityMessage');
            const ownershipMessage = document.getElementById('ownershipMessage');
            const archivedMessage = document.getElementById('archivedMessage');
            
            // Handle "Hall Added Successfully" message
            if (successMessage) {
                // Show the message with fade-in effect
                setTimeout(function() {
                    successMessage.classList.add('show');
                }, 100);
                
                // Hide the message after 5 seconds with fade-out effect
                setTimeout(function() {
                    successMessage.classList.add('fade-out');
                    
                    // Remove the element from DOM after animation completes
                    setTimeout(function() {
                        successMessage.remove();
                    }, 500);
                }, 5000);
            }
            
            // Handle "Hall Modified Successfully" message
            if (modifiedMessage) {
                // Show the message with fade-in effect
                setTimeout(function() {
                    modifiedMessage.classList.add('show');
                }, 100);
                
                // Hide the message after 5 seconds with fade-out effect
                setTimeout(function() {
                    modifiedMessage.classList.add('fade-out');
                    
                    // Remove the element from DOM after animation completes
                    setTimeout(function() {
                        modifiedMessage.remove();
                    }, 500);
                }, 5000);
            }
            
            // Handle "Hall Availability Status Updated Successfully" message
            if (availabilityMessage) {
                // Show the message with fade-in effect
                setTimeout(function() {
                    availabilityMessage.classList.add('show');
                }, 100);
                
                // Hide the message after 5 seconds with fade-out effect
                setTimeout(function() {
                    availabilityMessage.classList.add('fade-out');
                    
                    // Remove the element from DOM after animation completes
                    setTimeout(function() {
                        availabilityMessage.remove();
                    }, 500);
                }, 5000);
            }
            
            // Handle "Hall Ownership Transfered Successfully" message
            if (ownershipMessage) {
                // Show the message with fade-in effect
                setTimeout(function() {
                    ownershipMessage.classList.add('show');
                }, 100);
                
                // Hide the message after 5 seconds with fade-out effect
                setTimeout(function() {
                    ownershipMessage.classList.add('fade-out');
                    
                    // Remove the element from DOM after animation completes
                    setTimeout(function() {
                        ownershipMessage.remove();
                    }, 500);
                }, 5000);
            }
            
            // Handle "Hall Archieved Successfully" message
            if (archivedMessage) {
                // Show the message with fade-in effect
                setTimeout(function() {
                    archivedMessage.classList.add('show');
                }, 100);
                
                // Hide the message after 5 seconds with fade-out effect
                setTimeout(function() {
                    archivedMessage.classList.add('fade-out');
                    
                    // Remove the element from DOM after animation completes
                    setTimeout(function() {
                        archivedMessage.remove();
                    }, 500);
                }, 5000);
            }
        });
    </script>

</body>

</html>
