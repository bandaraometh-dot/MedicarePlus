<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('appointments.html');
}

$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : NULL;
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$doctor = isset($_POST['doctor']) ? trim($_POST['doctor']) : '';
$appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : NULL;
$appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;

// Basic validation
if (empty($full_name) || empty($email) || empty($appointment_date) || empty($appointment_time)) {
    redirect('appointments.html?error=missing');
}

$stmt = $conn->prepare('INSERT INTO appointments (user_id, full_name, email, phone, dob, department, doctor, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$status = 'pending';
$stmt->bind_param('issssssssss', $user_id, $full_name, $email, $phone, $dob, $department, $doctor, $appointment_date, $appointment_time, $reason, $status);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    redirect('appointments.html?success=1');
} else {
    redirect('appointments.html?error=server');
}
