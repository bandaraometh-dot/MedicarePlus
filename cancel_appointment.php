<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    redirect('login.html');
}

$user_id = (int) $_SESSION['user_id'];

// Get appointment ID from query
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect('patient-dashboard.php?error=invalid_id');
}

// Verify ownership and ensure appointment is upcoming
$sql = "SELECT a.id, a.datetime FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ? AND p.user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    redirect('patient-dashboard.php?error=not_authorized');
}
$appt = $res->fetch_assoc();
$stmt->close();

// Don't allow cancelling past appointments
if (strtotime($appt['datetime']) < time()) {
    redirect('patient-dashboard.php?error=past_appointment');
}

// Update status to cancelled
$stmt = $conn->prepare('UPDATE appointments SET status = ? WHERE id = ?');
$status = 'cancelled';
$stmt->bind_param('si', $status, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    redirect('patient-dashboard.php?success=cancelled');
}

redirect('patient-dashboard.php?error=server');

