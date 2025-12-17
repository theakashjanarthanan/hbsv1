<?php
session_start();

// Include OAuth configuration
include 'google_oauth_config.php';

// Database connection
include 'assets/conn.php';

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Check if we have the authorization code
if (!isset($_GET['code'])) {
    die('No authorization code received from Google');
}

// Check state parameter for security
if (!isset($_GET['state']) || $_GET['state'] !== OAUTH_STATE) {
    die('Invalid state parameter');
}

$code = $_GET['code'];

// Exchange authorization code for access token
$tokenData = getGoogleAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    die('Failed to get access token from Google');
}

$access_token = $tokenData['access_token'];

// Get user info from Google
$userInfo = getGoogleUserInfo($access_token);

if (!$userInfo || !isset($userInfo['email'])) {
    die('Failed to get user information from Google');
}

$email = $userInfo['email'];
$name = $userInfo['name'] ?? $userInfo['given_name'] . ' ' . $userInfo['family_name'];
$googleId = $userInfo['id'];

// Check if user exists in database by email only
$sql = "SELECT user_id, username, email, role, department_id, school_id, employee_id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // User exists, log them in
    $user = $result->fetch_assoc();
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['employee_id'] = $user['employee_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['department_id'] = $user['department_id'];
    $_SESSION['school_id'] = $user['school_id'];
    
    // Redirect based on user role
    if ($user['role'] == 'admin') {
        header("Location: home.php");
    } else {
        header("Location: timetable.php");
    }
    exit();
} else {
    // User doesn't exist - create new account with Google OAuth
    // For Google OAuth registration, we'll create a basic user account
    $defaultRole = 'prof'; // Default role for new Google users
    $hashedPassword = password_hash(uniqid(), PASSWORD_DEFAULT); // Random password
    
    $insertSql = "INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ssss", $name, $email, $hashedPassword, $defaultRole);
    
    if ($insertStmt->execute()) {
        $newUserId = $conn->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $defaultRole;
        $_SESSION['username'] = $name;
        $_SESSION['department_id'] = null;
        $_SESSION['school_id'] = null;
        $_SESSION['employee_id'] = null;
        
        // Redirect based on user role
        if ($defaultRole == 'admin') {
            header("Location: home.php");
        } else {
            header("Location: timetable.php");
        }
        exit();
    } else {
        // Failed to create account
        $_SESSION['oauth_error'] = 'Failed to create account with Google OAuth. Please try registering manually.';
        header("Location: register.php");
        exit();
    }
}

$conn->close();
?>
