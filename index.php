<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Pondicherry University</title>

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">

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
            background: url('image/Pondicherry_University_Cover_Photo.png') no-repeat center center;
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

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            margin: 120px auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .login-container h3 {
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control {
            height: 45px;
            border-radius: 10px;
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

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
        }

        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-password a {
            color: #007bff;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .alert {
            font-size: 14px;
            margin-top: 10px;
        }

        @media (max-width: 576px) {
            .navbar-title h4 {
                font-size: 16px;
            }
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
    </div>
</nav>

<!-- Login Card -->
<div class="main-content">
    <div class="login-container">
        <h3>Login</h3>
        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter the E-Mail ID" required>
            </div>

            <div class="mb-3 password-toggle">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter the Password" required>
                <i class="bi bi-eye-slash" id="togglePassword"></i>
            </div>

            <!-- PHP Error Message -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary w-100 mt-3">Login</button>

            <div class="forgot-password">
                <p><a href="recover_password.php">Forgot Password?</a></p>
            </div>
        </form>
    </div>
</div>

<!-- Password toggle script -->
<script>
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');

    toggle.addEventListener('click', function () {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
</script>

</body>
</html>
