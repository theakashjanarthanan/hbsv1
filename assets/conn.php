<?php

// Database connection parameters
$servername = "localhost"; // Hostname of the MySQL server
$username = "root"; // MySQL username (default is 'root' for local servers)
$password = "";  // MySQL password (empty by default in XAMPP/LAMP/WAMP)
$connname = "hbs_run"; // Name of the database to connect, if didn't exists the application won't starts

// // Create a new MySQLi connection instance
$conn = new mysqli($servername, $username, $password, $connname);

// Check connection
if ($conn->connect_error) {
    // If connection fails, display the error and stop the script
    die("Connection failed: " . $conn->connect_error);
}


?>