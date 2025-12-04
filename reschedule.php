<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    redirect('login.html');
}

$user_id = (int) $_SESSION['user_id'];

// GET: show form; POST: process form
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) redirect('patient-dashboard.php?error=invalid_id');

    // Verify appointment belongs to this user and fetch current datetime
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reschedule Appointment</title>
        <link rel="stylesheet" href="patient-dashboard.css">
    </head>
    <body>
        <div class="container" style="padding: 80px 0;">
            <section class="appointments-section">
                <div class="section-header">
                    <h3>Reschedule Appointment</h3>
                    <a class="btn-primary" href="patient-dashboard.php">Back to Dashboard</a>
                </div>
                <form method="post" action="reschedule.php">
                    <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                    <label for="new_datetime">New Date & Time</label>
                    <input type="datetime-local" id="new_datetime" name="new_datetime" value="<?php echo date('Y-m-d\TH:i', strtotime($appt['datetime'])); ?>" required>
                    <div style="margin-top:15px; display:flex; gap:10px;">
                        <button class="btn-primary" type="submit">Save</button>
                        <a class="btn-secondary" href="patient-dashboard.php">Cancel</a>
                    </div>
                </form>
            </section>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// POST: process new date/time
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$new_dt = isset($_POST['new_datetime']) ? trim($_POST['new_datetime']) : '';
if ($id <= 0 || empty($new_dt)) {
    redirect('patient-dashboard.php?error=invalid_input');
}

// Validate datetime format and make sure it's in the future
$timestamp = strtotime($new_dt);
if ($timestamp === false) redirect('patient-dashboard.php?error=invalid_datetime');
if ($timestamp <= time()) redirect('patient-dashboard.php?error=past_datetime');

// Ensure ownership
$sql = "SELECT a.id FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.id = ? AND p.user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    redirect('patient-dashboard.php?error=not_authorized');
}
$stmt->close();

// Update appointment datetime and set status to rescheduled
$new_datetime = date('Y-m-d H:i:s', $timestamp);
$status = 'rescheduled';
$stmt = $conn->prepare('UPDATE appointments SET datetime = ?, status = ? WHERE id = ?');
$stmt->bind_param('ssi', $new_datetime, $status, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) redirect('patient-dashboard.php?success=rescheduled');
redirect('patient-dashboard.php?error=server');

