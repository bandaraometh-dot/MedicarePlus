<?php
$servername = "localhost";
// For XAMPP default MySQL user is `root` with an empty password. Update as needed.
$username = "root";
$password = "";
$dbname = "MedicarePlus";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully";
?>