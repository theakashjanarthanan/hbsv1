<?php
include('assets/conn.php');  // Include database connection
include 'assets/header.php'; // Include header file

// Fetch latest semester start and end date
$query = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latestSemester = mysqli_fetch_assoc($result);

$today = date('Y-m-d'); // Get today's date
$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));

// Adjust semester start date to today if it's in the past
if ($semesterStart < $today) {
    $semesterStart = $today;
}

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];

    // Prepare the SQL statement with a JOIN to fetch hall name from hall_details table
    $stmt = $conn->prepare("
        SELECT b.booking_id, b.booking_id_gen, b.user_id, b.hall_id, b.start_date, b.end_date, 
               b.purpose, b.event_type, b.purpose_name, b.students_count, b.organiser_id, 
               b.organiser_name, b.organiser_department, b.organiser_mobile, b.organiser_email, 
               b.slot_or_session, b.booking_date, b.status, b.day_of_week, b.event_image,
               v.hall_name, v.capacity, v.department_id, v.school_id
        FROM bookings AS b 
        JOIN hall_details AS v ON b.hall_id = v.hall_id 
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("i", $booking_id);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the booking details
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();

        // Debug: Check if event_type is being fetched (remove this after testing)
        // echo "Debug - Event Type: " . ($booking['event_type'] ?? 'NULL') . "<br>";

        // Add the logic to determine session_choice
        if (in_array($booking['slot_or_session'], [1, 2, 3, 4])) {
            $booking['session_choice'] = 'fn'; // Forenoon
        } elseif (in_array($booking['slot_or_session'], [5, 6, 7, 8])) {
            $booking['session_choice'] = 'an'; // Afternoon
        } elseif (in_array($booking['slot_or_session'], range(1, 8))) {
            $booking['session_choice'] = 'both'; // Both sessions
        }
    } else {
        echo "No booking found for the provided ID.";
        $booking = null; // Set booking to null to avoid undefined variable warning
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Booking ID is not set.";
    $booking = null; // Set booking to null to avoid undefined variable warning
}

// Fetch department name for the booking
$department_name = '';
if ($booking && isset($booking['department_id'])) {
    $dept_query = "SELECT department_name FROM departments WHERE department_id = ?";
    $dept_stmt = $conn->prepare($dept_query);
    $dept_stmt->bind_param("i", $booking['department_id']);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    if ($dept_result->num_rows > 0) {
        $department_name = $dept_result->fetch_assoc()['department_name'];
    }
    $dept_stmt->close();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/design.css" />
    <title>Edit Semester Booking</title>
    <style>
        .container {
            margin-left: 250px;
            width: calc(100% - 250px);
            max-width: none;
        }

        .container-fluid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2%;
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
        }

        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            list-style: none; /* Remove bullet points */
        }

        .suggestion-item:hover {
            background-color: #f8f9fa;
        }

        .suggestions {
            list-style: none; /* Remove bullet points from the entire list */
            padding-left: 0; /* Remove default padding */
        }

        .position-relative {
            position: relative;
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
        
        /* Error message styling */
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            position: relative;
            border-radius: 8px;
        }

        .close-modal {
            color: #aaa;
            margin-right: 20px;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div id="main">
        <div class="row justify-content-center">
            <div class="col-md-8 mt-5">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4" style="color:#170098;">Update Semester Booking</h2>
                        
                        <!-- Debug Information (remove this after testing) -->
                        <?php if (isset($booking) && $booking): ?>
                            <!-- <div class="alert alert-info" style="font-size: 0.9em;">
                                <strong>Debug Info:</strong><br>
                                Event Type from DB: <code><?php echo htmlspecialchars($booking['event_type'] ?? 'NULL/EMPTY'); ?></code><br>
                                Purpose: <code><?php echo htmlspecialchars($booking['purpose'] ?? 'NULL/EMPTY'); ?></code><br>
                                Purpose Name: <code><?php echo htmlspecialchars($booking['purpose_name'] ?? 'NULL/EMPTY'); ?></code>
                            </div> -->
                        <?php endif; ?>
                        
                        <form action="update_semester_booking.php" method="post" onsubmit="return validateForm()">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                            <input type="hidden" name="hall_id" value="<?php echo htmlspecialchars($booking['hall_id'] ?? ''); ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($booking['status']); ?>">
                            <input type="hidden" name="booking_date" value="<?php echo date('Y-m-d'); ?>">
                            <!-- Hidden semester dates - not displayed but passed to backend -->
                            <input type="hidden" id="start_date" name="start_date" value="<?= htmlspecialchars($semesterStart) ?>">
                            <input type="hidden" id="end_date" name="end_date" value="<?= htmlspecialchars($semesterEnd) ?>">

                            <div class="form-group mb-3">
                                <label for="hall_name" class="form-label">Hall Name</label>
                                <input type="text" class="form-control" id="hall_name" name="hall_name" 
                                       value="<?php echo htmlspecialchars($booking['hall_name'] ?? ''); ?>" readonly disabled> 
                            </div>

                            <div class="form-group mb-3">
                                <label for="purpose" class="form-label">Purpose</label>
                                <select class="form-control" id="purpose" name="purpose" required>
                                    <option value="">Select Purpose</option>
                                    <option value="class" <?php echo ($booking['purpose'] == 'class') ? 'selected' : ''; ?>>Class</option>
                                    <option value="seminar" <?php echo ($booking['purpose'] == 'seminar') ? 'selected' : ''; ?>>Seminar</option>
                                    <option value="workshop" <?php echo ($booking['purpose'] == 'workshop') ? 'selected' : ''; ?>>Workshop</option>
                                    <option value="meeting" <?php echo ($booking['purpose'] == 'meeting') ? 'selected' : ''; ?>>Meeting</option>
                                    <option value="conference" <?php echo ($booking['purpose'] == 'conference') ? 'selected' : ''; ?>>Conference</option>
                                    <option value="other" <?php echo ($booking['purpose'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="purpose_name" class="form-label">Purpose Name</label>
                                <input type="text" class="form-control" id="purpose_name" name="purpose_name" 
                                       value="<?php echo htmlspecialchars($booking['purpose_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="event_type" class="form-label">Event Type</label>
                                <input type="text" class="form-control" id="event_type" name="event_type" 
                                       value="<?php echo htmlspecialchars($booking['event_type'] ?? ''); ?>" 
                                       placeholder="Enter event type">
                            </div>

                        
                            <!-- 
                            <div class="form-group mb-3">
                                <label for="students_count" class="form-label">Number of Students</label>
                                <input type="number" class="form-control" id="students_count" name="students_count" 
                                       value="<?php echo htmlspecialchars($booking['students_count'] ?? ''); ?>" required>
                            </div> -->
                                  

                             <div class="form-group mb-3 position-relative">
                                <label for="organiser_department" class="form-label">Faculty's Department</label>
                                <input type="text" class="form-control" id="organiser_department" name="organiser_department"
                                       value="<?php echo htmlspecialchars($department_name); ?>" required 
                                       oninput="suggestDepartments(this.value)"
                                       onclick="showDepartmentSuggestions()">
                                <ul id="department_suggestions" class="suggestions" style="display: none;"></ul>
                            </div>

                            <div class="form-group mb-3 position-relative">
                                <label for="organiser_name" class="form-label">Faculty's Name</label>
                                <input type="text" class="form-control" id="organiser_name" name="organiser_name" 
                                       value="<?php echo htmlspecialchars($booking['organiser_name'] ?? ''); ?>" 
                                       placeholder="Type to search..." required 
                                       oninput="suggestFaculty(this.value)"
                                       onclick="showFacultySuggestions()">
                                <ul id="suggestions" class="suggestions" style="display: none;"></ul>
                            </div>

                            <div class="form-group mb-3">
                                <label for="organiser_mobile" class="form-label">Faculty's Contact Number</label>
                                <input type="text" class="form-control" id="organiser_mobile" name="organiser_mobile" 
                                        value="<?php echo htmlspecialchars($booking['organiser_mobile'] ?? ''); ?>" readonly required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="organiser_email" class="form-label">Faculty's Email ID</label>
                                <input type="text" class="form-control" id="organiser_email" name="organiser_email" 
                                       value="<?php echo htmlspecialchars($booking['organiser_email'] ?? ''); ?>" readonly required>
                            </div>

                            <input type="hidden" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($booking['organiser_id'] ?? ''); ?>">

                            <div class="text-center">
                                <button type="submit" class="btn btn-success" id="update-btn">
                                    Update Semester Booking
                                </button>
                                <button type="button" class="btn btn-danger" onclick="deleteSemesterBooking()">
                                    <i class="fas fa-trash"></i> Delete Semester Booking
                                </button>
                                <a href="view_modify_booking.php?semester=1" class="btn btn-secondary">Cancel</a>
                                <div id="button-status" class="mt-2" style="display: none;"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Semester Booking Modal -->
    <div id="deleteSemesterModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('deleteSemesterModal')">&times;</span>
            <center>
                <h3 style="color: #dc3545; margin-bottom:15px;">Delete Semester Booking</h3>
            </center>
            <div style="text-align: center; margin: 20px 0;">
                <i class="fa-solid fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                <p style="font-size: 16px; margin-bottom: 20px;">
                    <strong>Are you sure you want to delete this semester booking?</strong>
                </p>
                <p style="color: #666; font-size: 14px; margin-bottom: 25px;">
                    This action cannot be undone. All related booking records will be permanently deleted.
                </p>
            </div>

            <form onsubmit="return handleDeleteSemesterBooking(event);">
                <!-- Booking ID (hidden) -->
                <input type="hidden" name="booking_id" id="delete_booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">

                <div style="display: flex; justify-content: center; gap: 15px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteSemesterModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-trash"></i> Delete Booking
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        // Date fields are now hidden, so date validation functions are no longer needed

        
        // Auto-fetch faculty details functionality
        function suggestFaculty(query) {
            if (query.length < 2) {
                document.getElementById('suggestions').style.display = 'none';
                return;
            }

            fetch(`get_employee.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const suggestions = document.getElementById('suggestions');
                    suggestions.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(employee => {
                            const li = document.createElement('li');
                            li.className = 'suggestion-item';
                            li.textContent = employee.employee_name;
                            li.onclick = () => selectFaculty(employee);
                            suggestions.appendChild(li);
                        });
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Function to show faculty suggestions when input is clicked
        function showFacultySuggestions() {
            const input = document.getElementById('organiser_name');
            const suggestions = document.getElementById('suggestions');
            
            // If input has value, show suggestions based on current value
            if (input.value.length >= 2) {
                suggestFaculty(input.value);
            } else {
                // If input is empty, show all faculty (or a message to start typing)
                if (input.value.length === 0) {
                    suggestions.innerHTML = '<li class="suggestion-item" style="color: #666; font-style: italic;">Start typing to search faculty...</li>';
                    suggestions.style.display = 'block';
                }
            }
        }

        function selectFaculty(employee) {
            document.getElementById('organiser_name').value = employee.employee_name;
            document.getElementById('organiser_mobile').value = employee.employee_mobile;
            document.getElementById('organiser_email').value = employee.employee_email;
            document.getElementById('employee_id').value = employee.employee_id;
            document.getElementById('suggestions').style.display = 'none';
        }

        function suggestDepartments(query) {
            if (query.length < 2) {
                document.getElementById('department_suggestions').style.display = 'none';
                return;
            }

            fetch(`get_departments.php?query=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const suggestions = document.getElementById('department_suggestions');
                    suggestions.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(dept => {
                            const li = document.createElement('li');
                            li.className = 'suggestion-item';
                            li.textContent = dept.department_name;
                            li.onclick = () => {
                                document.getElementById('organiser_department').value = dept.department_name;
                                suggestions.style.display = 'none';
                            };
                            suggestions.appendChild(li);
                        });
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Function to show department suggestions when input is clicked
        function showDepartmentSuggestions() {
            const input = document.getElementById('organiser_department');
            const suggestions = document.getElementById('department_suggestions');
            
            // If input has value, show suggestions based on current value
            if (input.value.length >= 2) {
                suggestDepartments(input.value);
            } else {
                // If input is empty, show all departments (or a message to start typing)
                if (input.value.length === 0) {
                    suggestions.innerHTML = '<li class="suggestion-item" style="color: #666; font-style: italic;">Start typing to search departments...</li>';
                    suggestions.style.display = 'block';
                }
            }
        }

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.position-relative')) {
                document.getElementById('suggestions').style.display = 'none';
                document.getElementById('department_suggestions').style.display = 'none';
            }
        });

        function validateForm() {
            // Only validate essential fields
            const hallId = document.querySelector('input[name="hall_id"]').value;
            const bookingId = document.querySelector('input[name="booking_id"]').value;
            
            if (!hallId || !bookingId) {
                alert('Essential booking information is missing.');
                return false;
            }
            
            // Check if at least one field is being updated
            const purpose = document.getElementById('purpose').value;
            const purposeName = document.getElementById('purpose_name').value;
            const eventType = document.getElementById('event_type').value;
            const organiserName = document.getElementById('organiser_name').value;
            const organiserDepartment = document.getElementById('organiser_department').value;
            
            if (!purpose && !purposeName && !eventType && !organiserName && !organiserDepartment) {
                alert('Please update at least one field.');
                return false;
            }
            
            return true;
        }

        // Delete Semester Booking Functions
        function deleteSemesterBooking() {
            document.getElementById('deleteSemesterModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function handleDeleteSemesterBooking(event) {
            event.preventDefault();

            const bookingId = document.getElementById('delete_booking_id').value;

            const formData = new FormData();
            formData.append("booking_id", bookingId);

            fetch("delete_semester_booking.php", {
                method: "POST",
                body: formData
            })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "success") {
                        alert("Semester booking deleted successfully.");
                        closeModal('deleteSemesterModal');
                        // Redirect back to the booking list
                        window.location.href = "view_modify_booking.php?semester=1";
                            } else {
                        alert("Error: " + data);
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                    alert("An unexpected error occurred.");
                });

            return false;
        }
    </script>
</body>
</html>

<?php
$conn->close(); // Close database connection
?>
