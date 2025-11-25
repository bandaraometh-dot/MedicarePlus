<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.html');
}

// Role (patient/doctor/admin) is provided via query string from the form action
$role = isset($_GET['role']) ? $_GET['role'] : 'patient';
// The login field accepts either username OR email — allow users to enter either.
$identifier = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

$identifier = esc($identifier);
if (empty($identifier) || empty($password)) {
    redirect('login.html?error=missing');
}

// Try to find user by username OR email for convenience
$stmt = $conn->prepare('SELECT id, password, role, first_name, last_name FROM users WHERE (username = ? OR email = ?) AND role = ? LIMIT 1');
$stmt->bind_param('sss', $identifier, $identifier, $role);
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
// store the identifier (username or email) used to login
$_SESSION['username'] = $identifier;
// normalize role value
$db_role = is_string($db_role) ? strtolower(trim($db_role)) : $db_role;
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
