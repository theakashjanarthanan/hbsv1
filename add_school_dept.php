<?php
include 'assets/conn.php'; // Database connection file

if (isset($_POST['submitroom']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which type of data to insert (school or department)
    if (isset($_POST['type'])) {
        $type = $_POST['type'];

        if ($type === 'school') {
            // Insert School data
            $school_name = $_POST['school'];
            $incharge_name = $_POST['incharge-name'];
            $designation  = "Dean";
            $incharge_contact = $_POST['incharge-contact'];
            $incharge_email = $_POST['incharge-email'];
            $incharge_intercom = $_POST['incharge-intercom'];
            $incharge_status = $_POST['incharge-status'];

            $sql = "INSERT INTO schools (school_name, incharge_name, incharge_contact_number, incharge_email, incharge_intercom, incharge_status) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $school_name, $incharge_name, $incharge_contact, $incharge_email, $incharge_intercom, $incharge_status);

            // Display the Alert Box and Navigate after pressing OK in the Alert Box
            if ($stmt->execute()) {
                echo "<script>
                    alert('School added successfully!');
                    window.location.href = 'view_school.php';
                </script>";
                exit();
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }

            $stmt->close();
        } elseif ($type === 'department') {
            // Insert Department data
            $school_id = $_POST['school_name'];
            $department_name = $_POST['department-name'];
            $hod_name = $_POST['hod-name'];
            $hod_contact = $_POST['hod-contact'];
            $designation = "Head Of Department";
            $hod_email = $_POST['hod-email'];
            $hod_intercom = $_POST['hod-intercom'];
            $incharge_status = $_POST['incharge-status'];

            $sql = "INSERT INTO departments (school_id, department_name, incharge_name, incharge_contact_mobile, designation, incharge_email, incharge_intercom, incharge_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssssss", $school_id, $department_name, $hod_name, $hod_contact, $designation, $hod_email, $hod_intercom, $incharge_status);

            // Display the Alert Box and Navigate after pressing OK in the Alert Box
            if ($stmt->execute()) {
                echo "<script>
                    alert('Department added successfully!');
                    window.location.href = 'view_school.php';
                </script>";
                exit();
            } else {
                echo "<script>alert('Error: " . $stmt->error . "');</script>";
            }
            
            $stmt->close();
        }
    }
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
    <link rel="stylesheet" href="assets/design.css" />

    <title>Add Hall</title>
    <style>
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }
    </style>
</head>

<body>

    <?php include 'assets/header.php' ?>
<div id="main">
        <div class="row justify-content-center">
            <div class="col-md-10 mt-5">
                <div class="card shadow-lg">
                    <div class="card-body">

            <!-- <h2 style="text-align: center;">Add School or Department</h2> -->

            <!-- Radio buttons to choose between School or Department -->
            <form id="myForm" style="margin:20px 150px; background-color:white; padding: 50px; border-radius:15px;" method="POST">
                <h1 style="text-align: center; color:#170098;">Add School or Department</h1>
                <h4 class="form-section-title" id="formTitle" style="margin-top:20px">Add School or Department</h4>
                <label for="type" style=" margin-top:20px, margin:20px 0 10px 0; font-size: 17px; font-weight: 600;">Select School or Department:</label><br>
                <div class="btn-group" role="group" aria-label="Room Type" style="margin-top:5px">
                    <input type="radio" class="btn-check" name="type" id="School" value="school" onclick="toggleForm()" checked>
                    <label class="btn btn-outline-primary" for="School">School</label>

                    <input type="radio" class="btn-check" name="type" id="Department" value="department" onclick="toggleForm()">
                    <label class="btn btn-outline-primary" for="Department">Department</label>

                </div>

                <!-- <label>
                    <input type="radio" name="type" value="department" onclick="toggleForm()">
                    Department
                </label> -->

                <!-- Form for School -->
                <div id="school-form" class=" form-section active mt-3">
                    <!-- <h3>School Details</h3> -->
                    <div class="form-label">
                        <label class="form-label" for="school">School Name:</label>
                        <input type="text" class="form-control" id="school" name="school" placeholder="Enter School Name" data-required="true">
                        <span id="school-error" style="color: red;"></span>
                    </div>
                    
                    <div class="form-label">
                        <label class="form-label" for="dean-name">Dean Name:</label>
                        <input type="text" class="form-control" id="dean-name" name="incharge-name" placeholder="Enter Dean Name" data-required="true">
                        <span id="dean-name-error" style="color: red;"></span>
                    </div>

                    <!-- <div class="form-label">
                        <label class="form-label" for="designation">Designation:</label>
                        <input type="text" class="form-control" id="designation" name="designation" value="Dean" readonly>
                        <span id="designation-error" style="color: red;"></span>
                    </div> -->

                    <div class="form-label">
                        <label class="form-label" for="dean-contact">Dean Contact Number:</label>
                        <input type="text" class="form-control" id="dean-contact" name="incharge-contact" placeholder="Enter Dean Contact Number" data-required="true">
                        <span id="dean-contact-error" style="color: red;"></span>
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="dean-email">Dean Email:</label>
                        <input type="email" class="form-control" id="dean-email" name="incharge-email" placeholder="Enter Dean Email" data-required="true">
                        <span id="dean-email-error" style="color: red;"></span>
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="dean-intercom">Dean Intercom:</label>
                        <input type="text" class="form-control" id="dean-intercom" name="incharge-intercom" placeholder="Enter Dean Intercom" data-required="true">
                        <span id="dean-intercom-error" style="color: red;"></span>
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="dean-status">Dean Status:</label>
                        <select id="dean-status" name="incharge-status">
                            <option value="">--- Select Dean Status ---</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Incharge">Incharge</option>
                        </select>
                        <span id="dean-status-error" style="color: red;"></span>
                    </div>

                </div>

                <!-- Form for Department -->
                <div id="department-form" class="form-section mt-3">
                    <!-- <h3>Department Details</h3> -->
                    <div id="departmentFields">
                        <label class="form-label" style="margin-top: 8px;">School Name:</label>
                        <select style="padding:12px;" name="school_name" id="school_name">
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

                        <!-- <label class="form-label" style="margin-top: 8px;">Department :</label>
                            <select style="padding: 7px;" name="department_name" id="department_name">
                                <option value="">Select Department</option>
                            </select> -->
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="department-name">Department Name:</label>
                        <input type="text" class="form-control" id="department-name" name="department-name" placeholder="Enter Department Name">
                        <span id="department-name-error" style="color: red;"></span>
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="hod-name">Incharge Name:</label>
                        <input type="text" class="form-control" id="hod-name" name="hod-name" placeholder="Enter HOD name">
                        <span id="hod-name-error" style="color: red;"></span>
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="hod-contact">HOD's Contact Number:</label>
                        <input type="text" class="form-control" id="hod-contact" name="hod-contact" placeholder="Enter HOD Contact Number">
                        <span id="hod-contact-error" style="color: red;"></span>
                    </div>
                    
                    <!-- <div class="form-label">
                        <label class="form-label" for="designation">Designation:</label>
                        <input type="text" class="form-control" id="designation" name="designation" value=" Head Of Department" readonly>
                        <span id="designation-error" style="color: red;"></span>
                    </div> -->

                    <div class="form-label">
                        <label class="form-label" for="hod-email">HOD's Email:</label>
                        <input type="email" class="form-control" id="hod-email" name="hod-email" placeholder="Enter HOD Email">
                        <span id="hod-email-error" style="color: red;"></span>
                    </div>


                    <div class="form-label">
                        <label class="form-label" for="hod-intercom">HOD's Intercom:</label>
                        <input type="text" class="form-control" id="hod-intercom" name="hod-intercom" placeholder="Enter HOD Intercome">
                        <span id="hod-intercom-error" style="color: red;"></span>
                    </div>

                    <div class="form-label">
                        <label class="form-label" for="hod-status">HOD Status:</label>
                        <select id="hod-status" name="incharge-status">
                            <option value="" >--- Select HOD Status ---</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Incharge">Incharge</option>
                        </select>
                        <span id="dean-status-error" style="color: red;"></span>
                    </div>

                </div>

                <br>
                
                <!-- <button type="submit" style="margin-left: 35%;" class="btn btn-success btn-lg">Submit</button> -->
                <!-- Center Alligned Action Buttons -->
                <div style="display: flex; justify-content: center; gap: 20px;">
                    <input type="submit" style="padding: 12px 40px; background-color: #007bff;" class="btn btn-success btn-lg" name="submitroom" value="Submit">
                    <input type="submit" style="padding: 12px 40px;" class="btn btn-success btn-lg" onclick="clearForm()" name="clear" value="Clear">
                </div>
            </form>
            <p id="form-error" style="color: red;"></p>
        </div>
    </div>
    <!-- <footer>
        <p>&copy; 2024 University Hall Booking System | All Rights Reserved</p>
    </footer> -->


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        function toggleForm() {
            const selectedType = document.querySelector('input[name="type"]:checked').value;

            const schoolFields = document.querySelectorAll('#school-form input, #school-form select');
            const deptFields = document.querySelectorAll('#department-form input, #department-form select');

            // Hide both first
            document.getElementById('school-form').classList.remove('active');
            document.getElementById('department-form').classList.remove('active');

            // Remove required attributes from all
            schoolFields.forEach(el => el.removeAttribute('required'));
            deptFields.forEach(el => el.removeAttribute('required'));

            const formTitle = document.getElementById('formTitle');

            if (selectedType === 'school') {
                document.getElementById('school-form').classList.add('active');
                formTitle.textContent = 'Add School';

                // Add required to school inputs
                schoolFields.forEach(el => {
                    if (el.hasAttribute('data-required')) {
                        el.setAttribute('required', 'required');
                    }
                });

            } else if (selectedType === 'department') {
                document.getElementById('department-form').classList.add('active');
                formTitle.textContent = 'Add Department';

                // Add required to department inputs
                deptFields.forEach(el => {
                    if (el.hasAttribute('data-required')) {
                        el.setAttribute('required', 'required');
                    }
                });
            }
        }
    </script>


    <script>
        // Get all the dropdown buttons
        var dropdownBtns = document.querySelectorAll(".dropdown-btn");

        // Loop through the buttons to add event listeners
        dropdownBtns.forEach(function(btn) {
            btn.addEventListener("click", function() {
                // Toggle between showing and hiding the active dropdown container
                this.classList.toggle("collapsed");
                var dropdownContainer = this.nextElementSibling;
                if (dropdownContainer.style.display === "block") {
                    dropdownContainer.style.display = "none";
                } else {
                    dropdownContainer.style.display = "block";
                }
            });
        });
    </script>

    <!-- Clear form data  -->
    <script>
        function clearForm() {
            document.getElementById("myForm").reset(); // Reset all form fields
        }
    </script>

    <!-- form validation  -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Function to show error message
            function showError(inputId, message) {
                let errorField = document.getElementById(inputId + "-error");
                if (!errorField) {
                    errorField = document.createElement("span");
                    errorField.id = inputId + "-error";
                    errorField.style.color = "red";
                    errorField.style.display = "block";
                    document.getElementById(inputId).after(errorField);
                }
                errorField.innerText = message;
            }

            // Function to clear error message
            function clearError(inputId) {
                let errorField = document.getElementById(inputId + "-error");
                if (errorField) {
                    errorField.innerText = "";
                }
            }

            // Validate Required Fields
            function validateRequired(inputId, fieldName) {
                let fieldValue = document.getElementById(inputId).value.trim();
                if (fieldValue === "") {
                    showError(inputId, `${fieldName} is required`);
                    return false;
                }
                clearError(inputId);
                return true;
            }

            // Validate Email
            function validateEmail(inputId) {
                let emailValue = document.getElementById(inputId).value.trim();
                let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailValue)) {
                    showError(inputId, "Invalid email format (e.g., example@mail.com)");
                    return false;
                }
                clearError(inputId);
                return true;
            }

            // Validate Contact Number
            function validateContact(inputId) {
                let contactValue = document.getElementById(inputId).value.trim();
                let contactRegex = /^[0-9]{10}$/; // Must be exactly 10 digits
                if (!contactRegex.test(contactValue)) {
                    showError(inputId, "Invalid contact number (must be 10 digits)");
                    return false;
                }
                clearError(inputId);
                return true;
            }

            // Attach blur event listeners for validation
            function addValidation(inputId, validationFunction, fieldName = "") {
                document.getElementById(inputId).addEventListener("blur", function() {
                    if (fieldName) {
                        validationFunction(inputId, fieldName);
                    } else {
                        validationFunction(inputId);
                    }
                });
            }

            // Function to check if the school exists (with callback)
            function checkSchoolExists(inputId, callback) {
                let schoolName = document.getElementById(inputId).value.trim();

                if (schoolName === "") {
                    showError(inputId, "School Name is required");
                    callback(false);
                    return;
                }

                let xhr = new XMLHttpRequest();
                xhr.open("POST", "check_school.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        if (xhr.responseText === "exists") {
                            showError(inputId, "School already exists!");
                            callback(false);
                        } else {
                            clearError(inputId);
                            callback(true);
                        }
                    }
                };
                xhr.send("school=" + encodeURIComponent(schoolName));
            }

            // Attach event listener for school name input field
            document.getElementById("school").addEventListener("blur", function() {
                checkSchoolExists("school", function() {});
            });

            // Attach validation to form fields
            addValidation("dean-name", validateRequired, "Dean Name");
            addValidation("dean-email", validateEmail);
            addValidation("dean-contact", validateContact);
            addValidation("dean-intercom", validateContact);
            addValidation("dean-status", validateRequired, "Dean Status");

            addValidation("school_name", validateRequired, "School Name");
            addValidation("department-name", validateRequired, "Department Name");
            addValidation("hod-name", validateRequired, "HOD Name");
            addValidation("hod-email", validateEmail);
            addValidation("hod-contact", validateContact);
            addValidation("hod-intercom", validateContact);

            // Final form validation before submission
            function validateForm(event) {
                event.preventDefault(); // Prevent form submission

                let isValid = true;

                if (!validateRequired("school", "School Name")) isValid = false;
                if (!validateRequired("dean-name", "Dean Name")) isValid = false;
                if (!validateEmail("dean-email")) isValid = false;
                if (!validateContact("dean-contact")) isValid = false;
                if (!validateContact("dean-intercom")) isValid = false;
                if (!validateRequired("dean-status", "Dean Status")) isValid = false;

                if (!validateRequired("school_name", "School Name")) isValid = false;
                if (!validateRequired("department-name", "Department Name")) isValid = false;
                if (!validateRequired("hod-name", "HOD Name")) isValid = false;
                if (!validateEmail("hod-email")) isValid = false;
                if (!validateContact("hod-contact")) isValid = false;
                if (!validateContact("hod-intercom")) isValid = false;

                if (!isValid) {
                    document.getElementById("form-error").innerText = "Fill all the fields correctly!";
                    return;
                }

                // Check school existence before final submission
                checkSchoolExists("school", function(schoolValid) {
                    if (schoolValid) {
                        document.getElementById("form-error").innerText = ""; // Clear form error
                        document.getElementById("school-form").submit(); // Submit form
                    } else {
                        document.getElementById("form-error").innerText = "Fix errors before submitting!";
                    }
                });
            }

            // Attach form submission event listener
            document.getElementById("school-form").addEventListener("submit", validateForm);
        });
    </script>
</body>

</html>