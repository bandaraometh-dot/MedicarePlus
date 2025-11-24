<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('register.html');
}

$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : NULL;
$address = isset($_POST['address']) ? trim($_POST['address']) : '';

// Basic validation
if (empty($username) || empty($email) || empty($password)) {
    redirect('register.html?error=missing');
}

if ($password !== $confirm_password) {
    redirect('register.html?error=password_mismatch');
}

// Check uniqueness of username/email
$stmt = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    redirect('register.html?error=exists');
}
$stmt->close();

// Insert user
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'patient';
$stmt = $conn->prepare('INSERT INTO users (first_name, last_name, username, email, password, phone, dob, address, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('sssssssss', $first_name, $last_name, $username, $email, $password_hash, $phone, $dob, $address, $role);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    redirect('login.html?registered=1');
} else {
    redirect('register.html?error=server');
}
