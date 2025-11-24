<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.html');
}

$role = isset($_GET['role']) ? $_GET['role'] : 'patient';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    redirect('login.html?error=missing');
}

$stmt = $conn->prepare('SELECT id, password, role, first_name, last_name FROM users WHERE username = ? AND role = ? LIMIT 1');
$stmt->bind_param('ss', $username, $role);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    redirect('login.html?error=invalid');
}

$stmt->bind_result($id, $password_hash, $db_role, $first_name, $last_name);
$stmt->fetch();

if (!password_verify($password, $password_hash)) {
    $stmt->close();
    redirect('login.html?error=invalid');
}

// Authentication successful
$_SESSION['user_id'] = $id;
$_SESSION['username'] = $username;
$_SESSION['role'] = $db_role;
$_SESSION['name'] = $first_name . ' ' . $last_name;

$stmt->close();

// Redirect to dashboard by role
if ($db_role === 'admin') {
    redirect('admin-dashboard.html');
} elseif ($db_role === 'doctor') {
    redirect('doctor-dashboard.html');
} else {
    redirect('patient-dashboard.html');
}
