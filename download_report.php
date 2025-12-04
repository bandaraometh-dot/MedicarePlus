<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) redirect('login.html');

$user_id = (int)$_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    redirect('patient-dashboard.php?error=invalid');
}

// Resolve patient id
$patient_id = null;
$stmt = $conn->prepare('SELECT id FROM patients WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($pid);
if ($stmt->fetch()) $patient_id = (int)$pid;
$stmt->close();

if (!$patient_id) redirect('patient-dashboard.php?error=no_patient');

// Find report and ensure it belongs to an appointment for this patient
$sql = 'SELECT mr.file_path, mr.file_name FROM medical_reports mr JOIN appointments a ON mr.appointment_id = a.id WHERE mr.id = ? AND a.patient_id = ? LIMIT 1';
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id, $patient_id);
$stmt->execute();
$stmt->bind_result($file_path, $file_name);
if (!$stmt->fetch()) {
    $stmt->close();
    redirect('patient-dashboard.php?error=not_found');
}
$stmt->close();

$base = realpath(__DIR__);
$full = realpath($file_path);
// Ensure file exists and is inside project base (prevent traversal)
if (!$full || strpos($full, $base) !== 0 || !is_file($full)) {
    redirect('patient-dashboard.php?error=file_missing');
}

// Serve file
$mime = mime_content_type($full) ?: 'application/octet-stream';
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($file_name ?: $full) . '"');
header('Content-Length: ' . filesize($full));
readfile($full);
exit;

?>
