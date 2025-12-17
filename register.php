<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$connname = "hbs_run";

$conn = new mysqli($servername, $username, $password, $connname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_msg = '';
$success_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $department = $_POST['department'] ?? null; // Department is optional for some roles
    $password = $_POST['password'];

    // Validate input fields
    if (empty($username) || empty($email) || empty($role) || empty($password)) {
        $error_msg = "All fields are required!";
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL based on role
        if ($role == 'admin' || $role == 'registrar') {
            $sql = "INSERT INTO Users (username, email, password, role) VALUES (?, ?, ?, ?)";
        } else {
            // For roles with departments
            if (empty($department)) {
                $error_msg = "Department is required for Professors, Deans, and HODs!";
            } else {
                $sql = "INSERT INTO Users (username, email, password, role, department_id) VALUES (?, ?, ?, ?, ?)";
            }
        }

        if (empty($error_msg)) {
            $stmt = $conn->prepare($sql);
            
            // Check if prepare() was successful
            if (!$stmt) {
                $error_msg = "Prepare failed: " . $conn->error;
            } else {
                // Bind parameters and execute query
                if ($role == 'admin' || $role == 'registrar') {
                    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
                } else {
                    $stmt->bind_param("ssssi", $username, $email, $hashedPassword, $role, $department);
                }

                if ($stmt->execute()) {
                    $success_msg = "Registration successful! Redirecting to login...";
                    // Redirect to login page after successful registration
                    header("refresh:2;url=index.php");
                } else {
                    $error_msg = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Pondicherry University</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    
    <!-- Google OAuth Configuration -->
    <?php include 'google_oauth_config.php'; ?>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
            position: relative;
        }

        .background-blur {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100vw;
            background: url('image/NEW.avif') no-repeat center center;
            background-size: cover;
            filter: blur(2px);
            z-index: -1;
        }

        .navbar {
            background-color: rgba(0, 123, 255, 0.9);
            height: 80px;
        }

        .main-content {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 80px; /* Navbar height compensation */
            box-sizing: border-box;
        }

        .content-wrapper {
            display: flex;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            gap: 40px;
            align-items: center;
        }

        .info-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 45px;
            color: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            align-items: center;
            text-align: center;
            margin-bottom:65px;
        }

        .info-container .university-logo {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 30px;
            border-radius: 50%;
            background: rgb(255, 255, 255);
            padding: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .info-container h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .info-container p {
            font-size: 1.125rem;
            line-height: 1.75;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .info-container .features {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-container .features li {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            font-size: 1rem;
        }

        .info-container .features li i {
            margin-right: 0.75rem;
            color:rgb(43, 255, 0);
            font-size: 1.25rem;
            font-weight: bold;
        }

        .navbar-logo img {
            height: 45px;
        }

        .navbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .navbar-title h4 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            margin: 50px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            max-height: 80vh;
            overflow-y: auto; /* internal scroll */
            flex-shrink: 0;
            -webkit-overflow-scrolling: touch; /* smooth on iOS */
            background-clip: padding-box; /* keep rounded corners visually clean */
            margin-bottom:120px;
        }

        /* Subtle, modern scrollbar inside the register container */
        .register-container::-webkit-scrollbar { width: 10px; }
        .register-container::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.04);
            border-radius: 10px;
        }
        .register-container::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.8);
        }
        .register-container { scrollbar-width: thin; scrollbar-color: rgba(0,0,0,0.35) rgba(0,0,0,0.06); }

        .register-container h3 {
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control, .form-select {
            height: 45px;
            border-radius: 10px;
            padding-right: 42px;
        }

        .btn-primary {
            height: 45px;
            border-radius: 10px;
            font-weight: bold;
            background-color: #007bff;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-google {
            height: 45px;
            border-radius: 10px;
            font-weight: 500;
            border: 1px solid #dadce0;
            color: #3c4043;
            background-color: #ffffff;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-google:hover { background-color: #f8f9fa; color: #3c4043; border-color: #dadce0; box-shadow: 0 4px 8px rgba(0,0,0,.15); transform: translateY(-1px); }
        .btn-google:focus { box-shadow: 0 0 0 0.2rem rgba(66, 133, 244, 0.25); outline: none; }
        .btn-google:active { transform: translateY(0); box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        .google-logo { width: 18px; height: 18px; margin-right: 12px; }

        .password-toggle { position: relative; }
        .password-toggle .toggle-visibility {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid rgba(0,0,0,.08);
            background: rgba(255,255,255,.9);
            color: #666;
            font-size: 18px;
            cursor: pointer;
            outline: none;
        }
        .password-toggle .toggle-visibility:hover { background: #f3f4f6; color: #333; }
        .password-toggle .toggle-visibility:focus { box-shadow: 0 0 0 3px rgba(13,110,253,.15); }

        .login-link {
            text-align: center;
            margin-top: 15px;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            font-size: 14px;
            margin-top: 10px;
        }

        .alert-success {
            background-color: #d1edff;
            border-color: #007bff;
            color: #004085;
        }

        /* Register spinner modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: #ffffff;
            color: #111827;
            padding: 18px 22px;
            border-radius: 8px;
            min-width: 260px;
            max-width: 90vw;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 8px 24px rgba(0,0,0,.15);
        }
        .spinner {
            width: 22px;
            height: 22px;
            border: 3px solid #e5e7eb;
            border-top-color: #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            body { overflow: auto; }
            .main-content { height: auto; min-height: 100vh; align-items: flex-start; padding-top: 80px; }
            .content-wrapper { flex-direction: column; gap: 30px; padding: 0 15px; }
            .info-container { order: 2; padding: 20px; }
            .register-container { order: 1; margin: 30px 0 80px; max-width: 100%; width: 100%; }
        }
        @media (max-width: 576px) {
            .navbar-title h4 { font-size: 16px; }
            .main-content { padding-top: 60px; }
            .content-wrapper { padding: 0 10px; gap: 20px; }
            .info-container { padding: 16px; }
            .info-container .university-logo { width: 80px; height: 80px; margin-bottom: 20px; padding: 4px; }
            .info-container h1 { font-size: 1.75rem; }
            .info-container p { font-size: 0.9rem; }
            .register-container { order: 1; padding: 30px 20px; }
        }
    </style>
</head>
<body>

<!-- Blurred Background Layer -->
<div class="background-blur"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid d-flex align-items-center justify-content-between px-4">
        <div class="navbar-logo">
            <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo">
        </div>
        <div class="navbar-title">
            <h4 class="text-white m-0">UNIVERSITY HALL BOOKING SYSTEM</h4>
        </div>
        <a href="contributors.php" class="btn btn-light btn-sm" style="display: inline-block; padding: 8px 10px; font-family: 'Arial', sans-serif; font-size: 16px; font-weight: bold; color: #333; background-color: #f8f9fa; border: 2px solid #ddd; border-radius: 8px; text-align: center; text-decoration: none; transition: all 0.3s ease-in-out; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
            Developers Team
        </a>
    </div>
</nav>

<!-- Register + Info -->
<div class="main-content">
    <div class="content-wrapper">
        <div class="info-container">
            <img src="image/UNI_IMG (1).png" alt="Pondicherry University Logo" class="university-logo">
            <h1>Pondicherry University</h1>
            <h3>Hall Booking System</h3>
            <p>Streamline your event planning with our comprehensive hall booking platform. Manage reservations, check availability, and coordinate events seamlessly.</p>
            <ul class="features">
                <li><i class="bi bi-calendar-check"></i> Easy Hall Reservation</li>
                <li><i class="bi bi-clock-history"></i> Real-time Availability</li>
                <li><i class="bi bi-shield-check"></i> Secure Authentication</li>
                <li><i class="bi bi-bell"></i> Automated Notifications</li>
            </ul>
        </div>

        <div class="register-container">
            <h3>Register</h3>
            <form action="register.php" method="POST" id="registerForm">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="" disabled selected>Select Role</option>
                        <option value="prof">Professor</option>
                        <option value="dean">Dean</option>
                        <option value="hod">HOD</option>
                        <option value="admin">Admin</option>
                        <option value="registrar">Registrar</option>
                    </select>
                </div>

                <!-- Department (only visible for certain roles) -->
                <div class="mb-3" id="department-section" style="display: none;">
                    <label for="department" class="form-label">Department</label>
                    <select class="form-select" id="department" name="department">
                        <option value="">Select Department</option>
                        <?php
                        // Fetch departments from the database
                        $sql = "SELECT department_id, department_name FROM Departments";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . $row['department_id'] . '">' . $row['department_name'] . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3 password-toggle">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="toggle-visibility bi bi-eye-slash" style="margin-top:15px;" id="togglePassword" aria-label="Toggle password visibility"></button>
                </div>

                <!-- PHP Error/Success Messages -->
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <!-- OAuth Error Message -->
                <?php if (isset($_SESSION['oauth_error'])): ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo $_SESSION['oauth_error']; unset($_SESSION['oauth_error']); ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-100 mt-3" id="registerBtn">Register</button>

                <!-- Divider -->
                <div class="text-center my-3">
                    <span class="text-muted">or</span>
                </div>

                <!-- Google OAuth Button -->
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google w-100 mb-3">
                    <svg class="google-logo" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>

                <div class="login-link">
                    <p>Already have an account? <a href="index.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="site-footer" role="contentinfo" aria-label="Site Footer">
    <span>&copy; 2025 Pondicherry University</span>
    <span class="visually-hidden">All rights reserved.</span>
</footer>

<!-- Register spinner modal -->
<div class="modal-overlay" id="registerModal" role="dialog" aria-live="polite" aria-label="Registering">
    <div class="modal-box">
        <div class="spinner" aria-hidden="true"></div>
        <div>Registering…</div>
    </div>
</div>

<!-- Scripts -->
<script>
    // Show/Hide department section based on the role
    document.getElementById('role').addEventListener('change', function() {
        var role = this.value;
        var departmentSection = document.getElementById('department-section');
        if (role === 'prof' || role === 'dean' || role === 'hod') {
            departmentSection.style.display = 'block';
        } else {
            departmentSection.style.display = 'none';
        }
    });

    // Password toggle functionality (modern button)
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    if (toggle && password) {
        toggle.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    }

    // Intercept register submit to show spinner for 2 seconds
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const registerModal = document.getElementById('registerModal');
    if (registerForm && registerBtn && registerModal) {
        let submitting = false;
        registerForm.addEventListener('submit', function (e) {
            if (submitting) return; // prevent duplicate
            e.preventDefault();
            submitting = true;
            registerBtn.disabled = true;
            registerModal.classList.add('active');
            setTimeout(() => {
                registerForm.submit();
            }, 2000);
        });
    }
</script>

<?php include 'assets/footer.php'; ?>
</body>
</html>