<?php
include 'assets/conn.php';

if (isset($_GET['id'])) {
    $hall_id = $_GET['id'];

    // Fetch school and department data
    $sql = "SELECT d.department_id, d.department_name, d.incharge_name, d.incharge_email, d.incharge_contact_mobile, 
                   d.incharge_intercom, d.designation, d.incharge_status, s.school_name
            FROM departments d 
            JOIN schools s ON s.school_id = d.school_id
            WHERE d.school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $hall_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch school name for the header
    $departments = $result->fetch_all(MYSQLI_ASSOC);

    $school_name = !empty($departments) ? $departments[0]['school_name'] : "Unknown School";
}
// } else {
//     die("Invalid access. No school ID provided.");
// }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design.css" />
    <title>View Department</title>

    <style>
        .btn-outline-success a {
            color: green;
        }

        .btn-outline-success a:hover {
            color: white;
        }

        h3 {
            font-family: 'Times New Roman', Times, serif;
        }

        /* Success Message Styles */
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
            z-index: 1000;
            font-weight: 500;
            display: flex;
            align-items: center;
            transform: translateX(400px);
            opacity: 0;
            transition: all 0.5s ease;
        }

        .success-message.show {
            transform: translateX(0);
            opacity: 1;
        }

        .success-message.fade-out {
            transform: translateX(400px);
            opacity: 0;
        }

        /* Disable effect for action buttons */
        .icon-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            filter: grayscale(100%);
            pointer-events: none;
        }

        /* Loading overlay inside table during search */
        .table-container { position: relative; }
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
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Search suggestions dropdown */
        .search-suggestions-wrapper { position: relative; }
        #deptSearchSuggestions {
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
        #deptSearchSuggestions .item { padding: 8px 10px; cursor: pointer; font-size: 14px; }
        #deptSearchSuggestions .item:hover { background: #f1f5ff; }

        #deptSearch{
            height:35px;
            position: relative;
            top:5px;
        }

        #deptSearchBtn{
            height: 38px;
             width: 38px;
             position: relative;
             top:9px;
        }
    </style>
</head>

<body>
    <?php include 'assets/header.php'; ?>
    
    <!-- Success Messages -->
    <div id="modifyMessage" class="success-message" style="display: none;">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Department Modified Successfully!
    </div>

    <div id="deleteMessage" class="success-message" style="display: none;">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        Department Deleted Successfully!
    </div>
    <div id="main">
        <div class="row justify-content-center">
            <div class="col-md-10 mt-5">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <center>
                            <h1 style="color:#0e00a3;">Departments Under <?php echo htmlspecialchars($school_name); ?></h1><br>
                        </center>

                        <div class="row">
                            <div class="col-8">
                                <div style="display: flex; align-items: center; gap: 10px; margin: 20px;">
                                    <!-- Modify (Blue) -->
                                    <button id="modifyBtn" onclick="modifyDepartment()" class="icon-button blue-button" disabled>
                                        <i class="fa-solid fa-pen-to-square"></i> Modify
                                    </button>



                                    <!-- Achieve Selected (Red) -->
                                    <button id="deleteBtn" onclick="deleteDepartment()" class="icon-button red-button" disabled>
                                        <i class="fa-solid fa-box-archive"></i> Delete
                                    </button>

                                    <!-- Inline search next to Delete -->
                                    <div class="search-suggestions-wrapper" style="max-width: 300px; margin-left:8px; width:100%;">
                                        <div class="input-group">
                                            <input type="text" id="deptSearch" class="form-control" placeholder="Search Department by Name" aria-label="Search departments" autocomplete="off">
                                            <button type="button" id="deptSearchBtn" class="btn btn-secondary" title="Search">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>
                                        <div id="deptSearchSuggestions"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4">
                                <!-- Toggle Button -->
                                <div style=" display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin: 20px;">
                                    <label for="multiSelectToggle">Multiple Selection:</label>
                                    <button id="multiSelectToggle" onclick="toggleMultiSelect()" style="padding: 5px 10px; border: none; border-radius: 5px; background-color: #ccc; cursor: pointer;">
                                        Off
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-container" style="max-height: 500px; overflow-y: auto; position: relative;">
                            <div id="loadingOverlay" class="loading-overlay" style="display:none;">
                                <div>
                                    <div class="loading-spinner"></div>
                                    <div style="color:#007bff; font-weight:600;">Searching...</div>
                                </div>
                            </div>
                            <table class="table table-bordered" id="bookingTable">
                                <thead>
                                    <tr>
                                        <th>Select</th>
                                        <th>Department</th>
                                        <th> Name & Designation</th>
                                        <th>Email / Contacts </th>
                                        <!-- <th>Action</th> -->
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // $i = 1; // Serial number counter
                                    if (!empty($departments)) {
                                        foreach ($departments as $row) {

                                            echo "<tr>";
                                            echo "<td>" . '<input type="checkbox" style="width: 20px; height: 20px; margin: 50% 30%;" class="hall-checkbox" value="' . $row['department_id'] . '" onclick="handleCheckbox(this)">' . "</td>";
                                            echo "<td>" . htmlspecialchars($row['department_name']) . "</td>";
                                            echo "<td> <b  style='color: #007bff;'>"
                                                . htmlspecialchars($row['incharge_name']) . " </b><br>";
                                                
                                                if ($row['incharge_status'] == "Incharge") {
                                                    echo htmlspecialchars($row['designation']) . " <span style='color:#dc3545'>(i/c) </span>";
                                                } else {
                                                    echo htmlspecialchars($row['designation']);
                                                } 
                                                 "</td>";
                                            echo "<td>"
                                                . htmlspecialchars($row['incharge_email']) . "<br>"
                                                . htmlspecialchars($row['incharge_intercom']) . "<br>"
                                                . htmlspecialchars($row['incharge_contact_mobile']) . "</td>";
                                            //     echo "<td style='text-align:center;'>
                                            //     <button class='btn btn-outline-success' style='padding: 5px 15px; margin-top: 12%;'>
                                            //         <a href='modify_dept.php?dept_id=" . htmlspecialchars($row['department_id']) . "' 
                                            //         style='margin: 0; padding: 0;'>
                                            //             <b>Modify</b>
                                            //         </a>
                                            //     </button>
                                            //     <button class='btn btn-outline-danger' style='padding: 5px 20px;  margin-top: 12%;' onclick='showDeleteModal(" . htmlspecialchars($row['department_id']) . ")'>
                                            //         <b>Delete</b>
                                            //     </button>
                                            // </td>";
                                            echo "</tr>";
                                            // $i++;
                                        }
                                    } else {
                                        echo ' <td colspan="5" style="text-align: center;">No Departments Available</td>';
                                    }

                                    $stmt->close();
                                    $conn->close();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Dropdown toggle functionality
        document.querySelectorAll(".dropdown-btn").forEach(function(btn) {
            btn.addEventListener("click", function() {
                this.classList.toggle("collapsed");
                var dropdownContainer = this.nextElementSibling;
                dropdownContainer.style.display = dropdownContainer.style.display === "block" ? "none" : "block";
            });
        });
    </script>



    <!--handle multiple & single selection at a time  -->
    <script>
        // Search handling with 2s loading overlay and client-side filter for departments, plus suggestions
        (function setupDeptSearch(){
            function performSearch(){
                const raw = (document.getElementById('deptSearch')?.value || '');
                const query = raw.trim().toLowerCase();
                if (query.length === 0) return; // only search if input has text
                const overlay = document.getElementById('loadingOverlay');
                if (!overlay) return;
                overlay.style.display = 'flex';
                setTimeout(function(){
                    try{
                        const rows = document.querySelectorAll('#bookingTable tbody tr');
                        rows.forEach(function(row){
                            if (!row || !row.cells || row.cells.length === 0) return;
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(query) ? '' : 'none';
                        });
                    } finally {
                        overlay.style.display = 'none';
                    }
                }, 2000);
            }

            document.addEventListener('DOMContentLoaded', function(){
                const btn = document.getElementById('deptSearchBtn');
                const input = document.getElementById('deptSearch');
                const suggestions = document.getElementById('deptSearchSuggestions');
                let deptNamesCache = [];

                function extractUniqueDeptNames(){
                    const names = [];
                    const rows = document.querySelectorAll('#bookingTable tbody tr');
                    rows.forEach(function(row){
                        const tds = row.querySelectorAll('td');
                        if (tds && tds.length >= 2) {
                            const name = (tds[1].innerText || '').trim();
                            if (name) names.push(name);
                        }
                    });
                    const unique = Array.from(new Set(names));
                    unique.sort((a,b)=>a.localeCompare(b));
                    return unique;
                }

                function renderSuggestions(query){
                    if (!suggestions) return;
                    if (!query || query.trim() === '') { suggestions.style.display = 'none'; suggestions.innerHTML=''; return; }
                    if (!deptNamesCache.length) deptNamesCache = extractUniqueDeptNames();
                    const q = query.toLowerCase();
                    const matches = deptNamesCache.filter(n => n.toLowerCase().includes(q)).slice(0,8);
                    if (matches.length === 0) { suggestions.style.display = 'none'; suggestions.innerHTML=''; return; }
                    suggestions.innerHTML = matches.map(m => '<div class="item" data-value="'+m.replace(/"/g,'&quot;')+'">'+m.replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</div>').join('');
                    suggestions.style.display = 'block';
                }

                function hideSuggestions(){ if (suggestions) { suggestions.style.display = 'none'; suggestions.innerHTML=''; } }

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
                        const field = document.getElementById('deptSearch');
                        if (field) field.value = val;
                        hideSuggestions();
                        if (btn) btn.click();
                    });
                }
            });
        })();

        let isMultiSelectEnabled = false; // Default to single selection

        // Function to toggle between single and multiple selection
        function toggleMultiSelect() {
            const toggleButton = document.getElementById('multiSelectToggle');
            isMultiSelectEnabled = !isMultiSelectEnabled; // Toggle the state

            // Update button text and style
            if (isMultiSelectEnabled) {
                toggleButton.textContent = 'On';
                toggleButton.style.backgroundColor = '#4CAF50'; // Green for "On"
                toggleButton.style.color = '#fff';
            } else {
                toggleButton.textContent = 'Off';
                toggleButton.style.backgroundColor = '#ccc'; // Gray for "Off"
                toggleButton.style.color = '#000';
            }

            // Reset all checkboxes when toggling
            const checkboxes = document.querySelectorAll('.hall-checkbox');
            checkboxes.forEach((cb) => {
                cb.checked = false; // Uncheck all checkboxes
                cb.disabled = false; // Enable all checkboxes
            });
        }

        // Function to handle checkbox clicks
        function handleCheckbox(checkbox) {
            const checkboxes = document.querySelectorAll('.hall-checkbox');

            if (!isMultiSelectEnabled) {
                if (checkbox.checked) {
                    checkboxes.forEach((cb) => {
                        if (cb !== checkbox) {
                            cb.disabled = true; // Disable other checkboxes
                        }
                    });
                } else {
                    checkboxes.forEach((cb) => {
                        cb.disabled = false; // Re-enable all checkboxes
                    });
                }
            }
        }
    </script>
    <script>
        // Enable/disable action buttons based on selection
        function updateActionButtonsState() {
            const hasSelection = document.querySelectorAll('.hall-checkbox:checked').length > 0;
            const ids = ['modifyBtn', 'deleteBtn'];
            ids.forEach(function(id) {
                const btn = document.getElementById(id);
                if (btn) btn.disabled = !hasSelection;
            });
        }

        document.addEventListener('change', function(e){
            if (e.target && e.target.classList && e.target.classList.contains('hall-checkbox')) {
                updateActionButtonsState();
            }
        });

        document.addEventListener('DOMContentLoaded', function(){
            updateActionButtonsState();
        });
    </script>
    <script>
        function modifyDepartment() {
            const selected = document.querySelectorAll('.hall-checkbox:checked');

            if (selected.length === 0) {
                alert("Please select a Department to modify.");
                return;
            }

            if (selected.length > 1) {
                alert("You can modify only one Department at a time.");
                return;
            }
            // if (!confirm('Are you sure you want to modify the selected school?')) {
            //     return;
            // }
            const hallId = selected[0].value;
            window.location.href = `modify_dept.php?id=${hallId}`;
        }

        // Function to delete Mutiple Departments - Supports Multiple Deletion
        function deleteDepartment() {
            const selected = document.querySelectorAll('.hall-checkbox:checked');

            if (selected.length === 0) {
                alert("Please select at least one Department to delete.");
                return;
            }

            const count = selected.length;
            if (!confirm(`Are you sure you want to delete ${count} selected Department(s)?`)) {
                return;
            }

            // Collect all selected department IDs
            const departmentIds = Array.from(selected).map(checkbox => checkbox.value);

            // Get the school ID dynamically from PHP
            const schoolId = <?= json_encode($hall_id ?? null) ?>;

            if (!schoolId) {
                alert("Error: Missing school ID.");
                return;
            }

            // Show alert before deletion
            alert('Department Deleted Successfully!');

            // Redirect to delete_dept.php with dept_ids and scl_id as GET parameters
            const url = `delete_dept.php?dept_ids=${departmentIds.join(',')}&scl_id=${schoolId}`;
            window.location.href = url;
        }
    </script>

    <!-- Success message animation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modifyMessage = document.getElementById('modifyMessage');
            const deleteMessage = document.getElementById('deleteMessage');
            
            // Check URL parameters for success messages
            const urlParams = new URLSearchParams(window.location.search);
            
            // Handle "Department Modified Successfully" message
            if (urlParams.get('modified') === '1') {
                if (modifyMessage) {
                    modifyMessage.style.display = 'flex';
                    // Show the message with fade-in effect
                    setTimeout(function() {
                        modifyMessage.classList.add('show');
                    }, 100);
                    
                    // Hide the message after 5 seconds with fade-out effect
                    setTimeout(function() {
                        modifyMessage.classList.add('fade-out');
                        
                        // Remove the element from DOM after animation completes
                        setTimeout(function() {
                            modifyMessage.remove();
                        }, 500);
                    }, 5000);
                }
            }
            
            // Handle "Department Deleted Successfully" message
            if (urlParams.get('deleted') === '1') {
                if (deleteMessage) {
                    deleteMessage.style.display = 'flex';
                    // Show the message with fade-in effect
                    setTimeout(function() {
                        deleteMessage.classList.add('show');
                    }, 100);
                    
                    // Hide the message after 5 seconds with fade-out effect
                    setTimeout(function() {
                        deleteMessage.classList.add('fade-out');
                        
                        // Remove the element from DOM after animation completes
                        setTimeout(function() {
                            deleteMessage.remove();
                        }, 500);
                    }, 5000);
                }
            }
        });
    </script>

</body>

</html>