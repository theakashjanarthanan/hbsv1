<?php session_start() ?>

<link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<!------ Include the above in your HEAD tag ---------->

<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">

    <link rel="stylesheet" href="style.css">

    <link rel="icon" href="Favicon.png">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            z-index: -2;
        }

        .background-overlay {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100vw;
            background: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }

        .navbar {
            background-color: rgba(0, 123, 255, 0.9);
            height: 80px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
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
            color: white;
        }

        .main-content {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 80px; /* compensate for fixed navbar height */
            box-sizing: border-box;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 40px;
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .login-container h3 {
            font-weight: bold;
            margin-bottom: 30px;
            text-align: center;
        }

        .login-container .form-control {
            height: 45px;
            border-radius: 10px;
            font-size: 14px;
        }

        .login-container p {
            margin-top: 15px;
            text-align: center;
        }

        .login-container p a {
            color: #007bff;
            text-decoration: none;
        }

        .login-container p a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-top: 10px;
            font-size: 14px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle i {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .password-toggle i:hover {
            color: #007bff;
        }

        @media (max-width: 576px) {
            .navbar-title h4 {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>

<div class="background-blur"></div>
<div class="background-overlay"></div>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid d-flex align-items-center justify-content-between px-4">
        <div class="navbar-logo">
            <img src="image/logo/PU_Logo_Full.png" alt="Pondicherry University Logo">
        </div>
        <div class="navbar-title">
            <h4>UNIVERSITY HALL BOOKING SYSTEM</h4>
        </div>
    </div>
</nav>

<div class="main-content">
    <main class="login-form">
        <div class="login-container">
            <form action="#" method="POST" name="recover_psw">
                <h3>Recover Password</h3>
                <div class="mb-3">
                    <label for="email_address" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email_address" name="email_address" placeholder="Enter the E-Mail ID" required autofocus>
                </div>
                <div class="text-center">
                    <input class="btn btn-primary w-100" style="margin-top:10px" type="submit" value="Recover Password" name="recover">
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>

<?php 
    if(isset($_POST["recover"])){
        include('assets/conn.php');
        $email = $_POST["email_address"];

        $sql = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        $query = mysqli_num_rows($sql);
  	    $fetch = mysqli_fetch_assoc($sql);

        if(mysqli_num_rows($sql) <= 0){
            ?>
            <script>
                alert("<?php  echo "Sorry, no emails exists "?>");
            </script>
            <?php
        }else{
            // generate token by binaryhexa 
            $token = bin2hex(random_bytes(50));

            session_start ();
            $_SESSION['token'] = $token;
            $_SESSION['email'] = $email;
            require "login/Mail/phpmailer/PHPMailerAutoload.php";
            $mail = new PHPMailer;

            $mail->isSMTP();
            $mail->Host='smtp.gmail.com';
            $mail->Port=587;
            $mail->SMTPAuth=true;
            $mail->SMTPSecure='tls';

            // h-hotel account
            $mail->Username='pudocs.hod@gmail.com';
            $mail->Password='rxtljtgfcpihwhag';

            // send by h-hotel email
            $mail->setFrom('pudocs.hod@gmail.com', 'Password Reset');
            // get email from input
            $mail->addAddress($_POST["email_address"]);
            //$mail->addReplyTo('lamkaizhe16@gmail.com');

            // HTML body
            $mail->isHTML(true);
            $mail->Subject="Recover your password";
            $mail->Body="<b>Dear User</b>
            <h3>We received a request to reset your password.</h3>
            <p>Kindly click the below link to reset your password</p>
           <a href='http://localhost/demo/reset_password.php?token=$token&email=$email'>Reset Password</a>
            <br><br>
            <p>With regrads,</p>
            <b>HBS-Team,Pondicherry University</b>";

            if(!$mail->send()){
                ?>
                    <script>
                        alert("<?php echo " Invalid Email "?>");
                    </script>
                <?php
            }else{
                ?>
                                <script>
                    alert("Password reset email has been sent successfully. Please check your email.");
                    window.location.replace("index.php");
                </script>
                <?php

            }
        }
    }


?>
