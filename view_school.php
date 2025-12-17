<?php
// Include database connection
if (!file_exists('assets/conn.php')) {
    die("Database connection file is missing.");
}
include 'assets/conn.php';
include 'assets/header.php';

$user_id = $_SESSION['user_id'];
$query_user = $conn->prepare("SELECT role, school_id, department_id FROM users WHERE user_id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$user = $query_user->get_result()->fetch_assoc();

$role = $user['role'];
$school_id = $user['school_id'];
// Fetch all school data
if (isset($conn)) {
    $sql = "SELECT * FROM schools";

    if ($role == 'dean' ) {
        $sql .= " where school_id = '$school_id'";
    }


    $result = mysqli_query($conn, $sql);
} else {
    die("Database connection is not established.");
}
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/design.css" />
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
        #schoolSearchSuggestions {
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
        #schoolSearchSuggestions .item { padding: 8px 10px; cursor: pointer; font-size: 14px; }
        #schoolSearchSuggestions .item:hover { background: #f1f5ff; }
        /* icon button  */
        .icon-button {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            background-color: transparent;
            border: 2px solid;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .icon-button i {
            font-size: 16px;
        }

        /* Red Button */
        .red-button {
            color: #dc3545;
            border-color: #dc3545;
        }

        .red-button:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Yellow Button */
        .yellow-button {
            color: orange;
            border-color: orange;
        }

        .yellow-button:hover {
            background-color: orange;
            color: white;
        }

        /* Blue Button */
        .blue-button {
            color: #007bff;
            border-color: #007bff;
        }

        .blue-button:hover {
            background-color: #007bff;
            color: white;
        }

        /* Green Button */
        .green-button {
            color: green;
            border-color: green;
        }

        .green-button:hover {
            background-color: green;
            color: white;
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

        #schoolSearch{
            height:35px;
            position: relative;
            top:5px;
        }

        #schoolSearchBtn{
            height: 38px;
             width: 38px;
             position: relative;
             top:9px;
        }
    </style>
    <title>Admin Home</title>
</head>

<body>
    <!-- Success Messages -->
    <div id="modifyMessage" class="success-message" style="display: none;">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        School Modified Successfully!
    </div>

    <div id="deleteMessage" class="success-message" style="display: none;">
        <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
        School Deleted Successfully!
    </div>

    <div id="main">
        <div class="container1 mt-3">
            <div class="table-wrapper">
                <div class="card">
                    <div class="card-body">
                        <center>
                            <h1 style="color:#170098;">School Details</h1>
                        </center>
                        <div class="row">
                            <div class="col-8">
                                <div style="display: flex; align-items: center; gap: 10px; margin: 20px;">
                                    <!-- Modify (Blue) -->
                                    <button id="modifyBtn" onclick="modifySelected()" class="icon-button blue-button" disabled>
                                        <i class="fa-solid fa-pen-to-square"></i> Modify
                                    </button>

                                    <!-- Departments (Green) --> 
                                    <!-- Migrated inside the Table -->
                                    <!-- <button onclick="departmentPopup()" class="icon-button green-button">
                                        <i class="fa-solid fa-building"></i>Departments
                                    </button> -->

                                    <!-- Achieve Selected (Red) -->
                                    <button id="deleteBtn" onclick="deleteSchool()" class="icon-button red-button" disabled>
                                        <i class="fa-solid fa-box-archive"></i> Delete
                                    </button>

                                    <!-- Inline search next to Delete -->
                                    <div class="search-suggestions-wrapper" style="max-width: 300px; margin-left:8px; width:100%;">
                                        <div class="input-group">
                                            <input type="text" id="schoolSearch" class="form-control" placeholder="Search School by Name" aria-label="Search schools" autocomplete="off">
                                            <button type="button" id="schoolSearchBtn" class="btn btn-secondary" title="Search">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                            </button>
                                        </div>
                                        <div id="schoolSearchSuggestions"></div>
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
                                    <!-- Clear All Filters -->
                                    <button type="button" id="clearFiltersButton" class="icon-button" onclick="clearAllSchoolFilters()" disabled>
                                        <i class="fa-solid fa-broom"></i> Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-container" style="max-height: 500px; overflow-y: auto; position: relative;">
                            <div id="loadingOverlay" class="loading-overlay" style="display:none;">
                                <div>
                                    <div class="loading-spinner"></div>
                                    <div style="color:#007bff; font-weight:600;">Searching...</div>
                                </div>
                            </div>
                            <table class="table table-bordered" id="bookingTable">
                                <!-- Fixed thead -->
                                <thead style="position: sticky; top: 0; background-color: white; z-index: 1;">
                                    <tr>
                                        <th>Select</th>
                                        <th>School Name</th>
                                        <th>Dean name</th>
                                        <th>Dean Contacts</th>
                                        <th>Departments</th>
                                        <!-- <th>Status</th> -->
                                        <!-- <th style="width:20%;">Actions</th> -->
                                    </tr>
                                </thead>

                                <!-- Scrollable tbody -->

                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0):
                                        // $i = 1;
                                        while ($school = mysqli_fetch_assoc($result)): ?>
                                            <tr>

                                            <td> <input type="checkbox" style="width: 20px; height: 20px; margin: 50% 30%;" class="hall-checkbox" value="<?php echo $school['school_id']; ?>" onclick="handleCheckbox(this)"></td>
                                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                                <td><b  style="color: #007bff;"><?php echo htmlspecialchars($school['incharge_name']); ?></b><br>
                                            
                                                <?php if($school['incharge_status'] == "Incharge"){
                                                     echo htmlspecialchars($school['designation']);?> <span style="color:#dc3545">(i/c)</span>
                                                     <?php
                                                      }
                                                      else {
                                                        echo htmlspecialchars($school['designation']);
                                                      } ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($school['incharge_email']); ?><br>
                                                    <!-- ?php echo htmlspecialchars($school['incharge_contact_number']); ?> <br> -->
                                                    <?php echo htmlspecialchars($school['incharge_intercom']); ?></td>
                                                <td style="text-align: center;">
                                                    <a href="view_dept.php?id=<?php echo $school['school_id']; ?>" class="btn btn-outline-success btn-sm" style="padding: 5px 15px; font-size: medium;">
                                                        <i class="fa-solid fa-building"></i> Show Departments
                                                    </a>
                                                </td>
                                                <!-- <td><?php echo htmlspecialchars($school['incharge_status']); ?></td> -->
                                                <!-- <td style="width:19%; padding-left: 30px;">
                                                        <a href="view_dept.php?school_id=<?php echo $school['school_id']; ?>" class="btn btn-outline-secondary btn-sm mb-2" style="padding: 5px 15px; font-size:medium;">Departments</a>
                                                        <a href="modify_school.php?school_id=<?php echo $school['school_id']; ?>" class="btn btn-outline-primary btn-sm" style="padding: 5px 15px; font-size:medium;">Update</a>
                                                        <a href="delete_scl.php?school_id=<?php echo $school['school_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this school?');" style="padding: 5px 15px; font-size:medium ;">Delete</a>
                                                    </td> -->
                                            </tr>
                                        <?php
                                        endwhile;
                                        ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No Schools available</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
<script>
    // Search handling with 2s loading overlay and client-side filter for schools, plus suggestions
    (function setupSchoolSearch(){
        function performSearch(){
            const raw = (document.getElementById('schoolSearch')?.value || '');
            const query = raw.trim().toLowerCase();
            if (query.length === 0) return; // only search if input has text
            const overlay = document.getElementById('loadingOverlay');
            if (!overlay) return;
            overlay.style.display = 'flex';
            setTimeout(function(){
                try{
                    const tbody = document.querySelector('#bookingTable tbody');
                    if (tbody) {
                        const existing = tbody.querySelector('#noResultsRow');
                        if (existing) existing.remove();
                    }
                    const rows = document.querySelectorAll('#bookingTable tbody tr');
                    rows.forEach(function(row){
                        if (!row || !row.cells || row.cells.length === 0) return;
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(query) ? '' : 'none';
                    });
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
                } finally {
                    overlay.style.display = 'none';
                }
            }, 2000);
        }

        document.addEventListener('DOMContentLoaded', function(){
            const btn = document.getElementById('schoolSearchBtn');
            const input = document.getElementById('schoolSearch');
            const suggestions = document.getElementById('schoolSearchSuggestions');
            let schoolNamesCache = [];

            function extractUniqueSchoolNames(){
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
                if (!schoolNamesCache.length) schoolNamesCache = extractUniqueSchoolNames();
                const q = query.toLowerCase();
                const matches = schoolNamesCache.filter(n => n.toLowerCase().includes(q)).slice(0,8);
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
                    const field = document.getElementById('schoolSearch');
                    if (field) field.value = val;
                    hideSuggestions();
                    if (btn) btn.click();
                });
            }
            // Enable clear button after search
            if (btn) btn.addEventListener('click', function(){ const b = document.getElementById('clearFiltersButton'); if (b) b.disabled = false; });
            if (input) input.addEventListener('keydown', function(e){ if (e.key === 'Enter'){ const b = document.getElementById('clearFiltersButton'); if (b) b.disabled = false; }});
        });
    })();

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

    // Clear All School Filters function
    function clearAllSchoolFilters() {
        const input = document.getElementById('schoolSearch');
        if (input) input.value = '';
        const tbody = document.querySelector('#bookingTable tbody');
        const noRow = tbody ? tbody.querySelector('#noResultsRow') : null;
        if (noRow) noRow.remove();
        const rows = document.querySelectorAll('#bookingTable tbody tr');
        rows.forEach(function(row){ if (row) row.style.display = ''; });
        const clearBtn = document.getElementById('clearFiltersButton');
        if (clearBtn) clearBtn.disabled = true;
    }

    function modifySelected() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select a school to modify.");
            return;
        }

        if (selected.length > 1) {
            alert("You can modify only one school at a time.");
            return;
        }
        // if (!confirm('Are you sure you want to modify the selected school?')) {
        //     return;
        // }
        const hallId = selected[0].value;
        window.location.href = `modify_school.php?id=${hallId}`;
    }

    function departmentPopup() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select a school.");
            return;
        }

        if (selected.length > 1) {
            alert("Select only one school at a time.");
            return;
        }
        // if (!confirm('Are you sure you want to modify the selected school?')) {
        //     return;
        // }
        const hallId = selected[0].value;
        window.location.href = `view_dept.php?id=${hallId}`;
    }

    // Function to Schools - Supports Multiple Deletion
    function deleteSchool() {
        const selected = document.querySelectorAll('.hall-checkbox:checked');

        if (selected.length === 0) {
            alert("Please select a school to delete.");
            return;
        }

        const count = selected.length;

        if (!confirm(`Are you sure you want to delete the selected ${count} school(s)?`)) {
            return;
        }

        // Show alert before deletion
        alert('School Deleted Successfully!');

        // Create a form to POST the selected IDs
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_scl.php';

        selected.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'school_ids[]';
            input.value = cb.value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }
</script>
<!--handle multiple & single selection at a time  -->
<script>
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

    // Success message animation
    document.addEventListener('DOMContentLoaded', function() {
        const modifyMessage = document.getElementById('modifyMessage');
        const deleteMessage = document.getElementById('deleteMessage');
        
        // Check URL parameters for success messages
        const urlParams = new URLSearchParams(window.location.search);
        
        // Handle "Modified Successfully" message
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
        
        // Handle "Deleted Successfully" message
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



</html>