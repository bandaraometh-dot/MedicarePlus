<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('appointments.html');
}

// Debug logging
$logFile = __DIR__ . '/appointment_debug.log';
file_put_contents($logFile, "--- New Request ---\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

if (session_status() === PHP_SESSION_NONE) session_start();

$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$dob = isset($_POST['dob']) ? trim($_POST['dob']) : NULL;
$department = isset($_POST['department']) ? trim($_POST['department']) : '';
$doctor = isset($_POST['doctor']) ? (int)$_POST['doctor'] : 0; // expecting doctor id
$appointment_date = isset($_POST['appointment_date']) ? trim($_POST['appointment_date']) : NULL;
$appointment_time = isset($_POST['appointment_time']) ? trim($_POST['appointment_time']) : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Basic validation: require date/time and doctor
if (empty($appointment_date) || empty($appointment_time) || $doctor <= 0) {
    redirect('appointments.html?error=missing');
}

// Resolve patient_id if user is logged in
$patient_id = null;
if (!empty($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare('SELECT id FROM patients WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $prow = $res->fetch_assoc();
    $stmt->close();
    if ($prow && isset($prow['id'])) $patient_id = (int)$prow['id'];
}

// Combine date and time into datetime
$datetime = date('Y-m-d H:i:s', strtotime($appointment_date . ' ' . $appointment_time));
$status = 'pending';

try {
    if ($patient_id !== null) {
        $stmt = $conn->prepare('INSERT INTO appointments (patient_id, doctor_id, datetime, status) VALUES (?,?,?,?)');
        $stmt->bind_param('iiss', $patient_id, $doctor, $datetime, $status);
    } else {
        // Guest booking: insert without patient_id
        $stmt = $conn->prepare('INSERT INTO appointments (doctor_id, datetime, status) VALUES (?,?,?)');
        $stmt->bind_param('iss', $doctor, $datetime, $status);
    }

    $ok = $stmt->execute();
    $stmt->close();
} catch (Exception $e) {
    $ok = false;
}

if ($ok) {
    // Notify all admins about the new appointment by inserting messages
    try {
        $appt_id = $conn->insert_id;
        $subject = 'New appointment #' . (int)$appt_id;
        $body_lines = [];
        $body_lines[] = 'Appointment ID: ' . (int)$appt_id;
        $body_lines[] = 'Doctor ID: ' . (int)$doctor;
        $body_lines[] = 'Datetime: ' . $datetime;
        if (!empty($full_name)) $body_lines[] = 'Patient name: ' . $full_name;
        if (!empty($email)) $body_lines[] = 'Patient email: ' . $email;
        if (!empty($phone)) $body_lines[] = 'Patient phone: ' . $phone;
        $body = implode("\n", $body_lines);

        // Fetch admin users
        $adm_res = $conn->query("SELECT id FROM users WHERE role = 'admin'");
        if ($adm_res && $adm_res->num_rows > 0) {
            while ($adm = $adm_res->fetch_assoc()) {
                $admin_id = (int)$adm['id'];
                if (!empty($_SESSION['user_id'])) {
                    $from_user = (int)$_SESSION['user_id'];
                    $mi = $conn->prepare('INSERT INTO messages (`from_user`,`to_user`,`subject`,`body`) VALUES (?,?,?,?)');
                    if ($mi) {
                        $mi->bind_param('iiss', $from_user, $admin_id, $subject, $body);
                        $mi->execute();
                        $mi->close();
                    }
                } else {
                    $mi = $conn->prepare('INSERT INTO messages (`to_user`,`subject`,`body`) VALUES (?,?,?)');
                    if ($mi) {
                        $mi->bind_param('iss', $admin_id, $subject, $body);
                        $mi->execute();
                        $mi->close();
                    }
                }
            }
        }
    } catch (Exception $e) {
        // don't block user flow if admin notification fails
    }

    // If the user is logged in as a patient, set a flash and send them to their dashboard
    if (!empty($_SESSION['user_id'])) {
        set_flash('success', 'Appointment added successfully');
        redirect('patient-dashboard.php');
    }
    redirect('appointments.html?success=1');
} else {
    redirect('appointments.html?error=server');
}
