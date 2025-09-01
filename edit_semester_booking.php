<?php
include('assets/conn.php');  // Include database connection
include 'assets/header.php';

// Fetch latest semester start and end date
$query = "SELECT * FROM semesters ORDER BY semester_id DESC LIMIT 1";
$result = mysqli_query($conn, $query);
$latestSemester = mysqli_fetch_assoc($result);

$semesterStart = date('Y-m-d', strtotime($latestSemester['start_date']));
$semesterEnd = date('Y-m-d', strtotime($latestSemester['end_date']));

if (isset($_GET['id'])) {
    $booking_id = $_GET['id'];

    // Prepare the SQL statement with a JOIN to fetch hall name from hall_details table
    $stmt = $conn->prepare("
        SELECT b.*, v.hall_name, v.capacity, v.department_id, v.school_id
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
        }

        .suggestion-item:hover {
            background-color: #f8f9fa;
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
    </style>
</head>

<body>
    <?php include 'assets/header.php' ?>

    <div id="main">
        <div class="row justify-content-center">
            <div class="col-md-8 mt-5">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4" style="color:#170098;">Update Semester Booking</h2>
                        
                        <!-- Semester Range Banner -->
                        <div class="semester-banner mb-4">
                            <div class="alert alert-info text-center" role="alert">
                                <i class="fas fa-calendar-alt me-2"></i>
                                <strong>Semester Period:</strong> 
                                <?= date('M d, Y', strtotime($semesterStart)) ?> - <?= date('M d, Y', strtotime($semesterEnd)) ?>
                                <br>
                                <small class="text-muted">Only dates within this period can be selected for semester booking updates</small>
                            </div>
                        </div>
                        
                        <form action="update_semester_booking.php" method="post" onsubmit="return validateForm()">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['booking_id']); ?>">
                            <input type="hidden" name="hall_id" value="<?php echo htmlspecialchars($booking['hall_id'] ?? ''); ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($booking['status']); ?>">
                            <input type="hidden" name="booking_date" value="<?php echo date('Y-m-d'); ?>">

                            <div class="form-group mb-3">
                                <label for="hall_name" class="form-label">Hall Name</label>
                                <input type="text" class="form-control" id="hall_name" name="hall_name" 
                                       value="<?php echo htmlspecialchars($booking['hall_name'] ?? ''); ?>" readonly>
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
                            <!-- 
                            <div class="form-group mb-3">
                                <label for="students_count" class="form-label">Number of Students</label>
                                <input type="number" class="form-control" id="students_count" name="students_count" 
                                       value="<?php echo htmlspecialchars($booking['students_count'] ?? ''); ?>" required>
                            </div> -->

                            <div class="form-group mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo htmlspecialchars($booking['start_date'] ?? ''); ?>" 
                                       min="<?= htmlspecialchars($semesterStart) ?>"
                                       max="<?= htmlspecialchars($semesterEnd) ?>"
                                       required
                                       onchange="handleDateChange(); checkSlotAvailability()">
                                <div id="start_date_error" class="error-message"></div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo htmlspecialchars($booking['end_date'] ?? ''); ?>" 
                                       min="<?= htmlspecialchars($semesterStart) ?>"
                                       max="<?= htmlspecialchars($semesterEnd) ?>"
                                       required
                                       onchange="handleDateChange(); checkSlotAvailability()">
                                <div id="end_date_error" class="error-message"></div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Day of Week</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="day_of_week" id="monday" value="Monday" 
                                           <?php echo ($booking['day_of_week'] == 'Monday') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="monday">Monday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="day_of_week" id="tuesday" value="Tuesday" 
                                           <?php echo ($booking['day_of_week'] == 'Tuesday') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tuesday">Tuesday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="day_of_week" id="wednesday" value="Wednesday" 
                                           <?php echo ($booking['day_of_week'] == 'Wednesday') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="wednesday">Wednesday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="day_of_week" id="thursday" value="Thursday" 
                                           <?php echo ($booking['day_of_week'] == 'Thursday') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="thursday">Thursday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="day_of_week" id="friday" value="Friday" 
                                           <?php echo ($booking['day_of_week'] == 'Friday') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="friday">Friday</label>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label">Time Slots</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Forenoon</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot1" value="1" 
                                                   <?php echo (in_array('1', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot1">9:30 AM - 10:30 AM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot2" value="2" 
                                                   <?php echo (in_array('2', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot2">10:30 AM - 11:30 AM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot3" value="3" 
                                                   <?php echo (in_array('3', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot3">11:30 AM - 12:30 PM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot4" value="4" 
                                                   <?php echo (in_array('4', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot4">12:30 PM - 1:30 PM</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Afternoon</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot5" value="5" 
                                                   <?php echo (in_array('5', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot5">1:30 PM - 2:30 PM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot6" value="6" 
                                                   <?php echo (in_array('6', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot6">2:30 PM - 3:30 PM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot7" value="7" 
                                                   <?php echo (in_array('7', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot7">3:30 PM - 4:30 PM</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="slots[]" id="slot8" value="8" 
                                                   <?php echo (in_array('8', explode(',', $booking['slot_or_session'] ?? ''))) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="slot8">4:30 PM - 5:30 PM</label>
                                        </div>
                                    </div>
                                                                 </div>
                             </div>

                             <!-- Slot Availability Status -->
                             <div id="slot-availability" style="display: none;"></div>
                             
                             <!-- Check Availability Button -->
                             <div class="form-group mb-3">
                                 <button type="button" class="btn btn-info" onclick="checkSlotAvailability()">
                                     <i class="bi bi-search"></i> Check Slot Availability
                                 </button>
                             </div>

                             <div class="form-group mb-3 position-relative">
                                <label for="organiser_department" class="form-label">Faculty's Department</label>
                                <input type="text" class="form-control" id="organiser_department" name="organiser_department"
                                       value="<?php echo htmlspecialchars($department_name); ?>" required oninput="suggestDepartments(this.value)">
                                <ul id="department_suggestions" class="suggestions" style="display: none;"></ul>
                            </div>

                            <div class="form-group mb-3 position-relative">
                                <label for="organiser_name" class="form-label">Faculty's Name</label>
                                <input type="text" class="form-control" id="organiser_name" name="organiser_name" 
                                       value="<?php echo htmlspecialchars($booking['organiser_name'] ?? ''); ?>" 
                                       placeholder="Type to search..." required oninput="suggestFaculty(this.value)">
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
                                <button type="submit" class="btn btn-primary">Update Semester Booking</button>
                                <a href="view_modify_booking.php?semester=1" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to check if a date is within semester range
        function isDateWithinSemester(dateStr) {
            const semesterStart = '<?= $semesterStart ?>';
            const semesterEnd = '<?= $semesterEnd ?>';
            
            const date = new Date(dateStr);
            const start = new Date(semesterStart);
            const end = new Date(semesterEnd);
            
            return date >= start && date <= end;
        }
        
        // Function to handle date changes and validate semester range
        function handleDateChange() {
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const startDateStr = startInput.value;
            const endDateStr = endInput.value;

            const startDate = new Date(startDateStr);
            const endDate = new Date(endDateStr);
            const semesterStart = new Date('<?= $semesterStart ?>');
            const semesterEnd = new Date('<?= $semesterEnd ?>');

            // Reset error messages
            document.getElementById('start_date_error').textContent = '';
            document.getElementById('end_date_error').textContent = '';

            // Validation: start date within semester range
            if (startDateStr && (startDate < semesterStart || startDate > semesterEnd)) {
                document.getElementById('start_date_error').textContent = 'Start date must be within the semester period.';
                startInput.value = '<?= $semesterStart ?>';
                return;
            }

            // Validation: end date within semester range
            if (endDateStr && (endDate < semesterStart || endDate > semesterEnd)) {
                document.getElementById('end_date_error').textContent = 'End date must be within the semester period.';
                endInput.value = '<?= $semesterEnd ?>';
                return;
            }

            // Validation: end date is not before start date
            if (startDateStr && endDateStr && startDate > endDate) {
                document.getElementById('end_date_error').textContent = 'End date cannot be before start date.';
                endInput.value = startInput.value;
                return;
            }

            // Update min/max constraints
            endInput.min = startDateStr;
            startInput.max = endDateStr;
        }
        
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
            const studentsCount = document.getElementById('students_count').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const dayOfWeek = document.querySelector('input[name="day_of_week"]:checked');
            const slots = document.querySelectorAll('input[name="slots[]"]:checked');
            const organiserName = document.getElementById('organiser_name').value;
            const organiserDepartment = document.getElementById('organiser_department').value;
            
            if (!purpose && !purposeName && !studentsCount && !startDate && !endDate && 
                !dayOfWeek && slots.length === 0 && !organiserName && !organiserDepartment) {
                alert('Please update at least one field.');
                return false;
            }
            
            return true;
        }

        // Slot availability checking
        function checkSlotAvailability() {
            const dayOfWeek = document.querySelector('input[name="day_of_week"]:checked');
            const slots = document.querySelectorAll('input[name="slots[]"]:checked');
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const hallId = document.querySelector('input[name="hall_id"]').value;
            const bookingId = document.querySelector('input[name="booking_id"]').value;
            const availabilityDiv = document.getElementById('slot-availability');

            // Hide previous messages
            availabilityDiv.style.display = 'none';

            // Only check if we have all the required fields for conflict checking
            if (!dayOfWeek || slots.length === 0 || !startDate || !endDate) {
                // Don't show error, just don't check availability
                return;
            }

            const selectedSlots = Array.from(slots).map(slot => slot.value);

            const formData = new FormData();
            formData.append('day_of_week', dayOfWeek.value);
            formData.append('slots', selectedSlots.join(','));
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            formData.append('hall_id', hallId);
            formData.append('booking_id', bookingId);

            fetch('check_semester_slot_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    availabilityDiv.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                    availabilityDiv.style.display = 'block';
                } else {
                    availabilityDiv.innerHTML = '<div class="alert alert-success">All selected slots are available!</div>';
                    availabilityDiv.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Add event listeners for slot availability checking
        document.addEventListener('DOMContentLoaded', function() {
            // Check availability when day of week changes
            document.querySelectorAll('input[name="day_of_week"]').forEach(radio => {
                radio.addEventListener('change', checkSlotAvailability);
            });

            // Check availability when slots change
            document.querySelectorAll('input[name="slots[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', checkSlotAvailability);
            });

            // Check availability when dates change
            document.getElementById('start_date').addEventListener('change', checkSlotAvailability);
            document.getElementById('end_date').addEventListener('change', checkSlotAvailability);
            
            // Add input event listeners for real-time validation
            document.getElementById('start_date').addEventListener('input', function() {
                const startDate = new Date(this.value);
                const endInput = document.getElementById('end_date');
                
                // Update end date min constraint
                if (this.value) {
                    endInput.min = this.value;
                }
                
                // Validate semester range
                handleDateChange();
            });
            
            document.getElementById('end_date').addEventListener('input', function() {
                const endDate = new Date(this.value);
                const startInput = document.getElementById('start_date');
                
                // Validate semester range
                handleDateChange();
            });
            
            // Initial date validation
            handleDateChange();
        });

        // Initialize date pickers with semester constraints
        flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            minDate: "<?= $semesterStart ?>",
            maxDate: "<?= $semesterEnd ?>"
        });

        flatpickr("#end_date", {
            dateFormat: "Y-m-d",
            minDate: "<?= $semesterStart ?>",
            maxDate: "<?= $semesterEnd ?>"
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
