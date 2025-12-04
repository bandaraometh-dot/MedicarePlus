<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    redirect('login.html');
}

$user_id = (int) $_SESSION['user_id'];

// Get patient id
$patient_id = null;
$stmt = $conn->prepare('SELECT id FROM patients WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($pid);
if ($stmt->fetch()) $patient_id = (int) $pid;
$stmt->close();

$has_prescriptions = false;
$prescriptions = [];
try {
    $r = $conn->query("SHOW TABLES LIKE 'prescriptions'");
    if ($r && $r->num_rows > 0) {
        $has_prescriptions = true;
        if ($patient_id) {
            $sql = "SELECT id, medicine_name, dosage, instructions, issued_at, doctor_id FROM prescriptions WHERE patient_id = ? ORDER BY issued_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $patient_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $prescriptions[] = $row;
            $stmt->close();
        }
    }
} catch (Exception $e) {
    // ignore
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Prescriptions - MediCare</title>
    <link rel="stylesheet" href="patient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width:1000px;margin:20px auto;padding:0 12px;">
        <a class="back-btn" href="patient-dashboard.php">&larr; Back to Dashboard</a>
        <h2>Prescriptions</h2>
        <?php if (!$has_prescriptions): ?>
            <div class="alert">The prescriptions module is not installed on the server.</div>
        <?php else: ?>
            <?php if (empty($prescriptions)): ?>
                <div class="alert">No prescriptions found.</div>
            <?php else: ?>
                <div class="resource-section">
                    <ul class="resource-list">
                        <?php foreach ($prescriptions as $p): ?>
                            <li class="resource-item">
                                <div>
                                    <strong><?php echo esc($p['medicine_name']); ?></strong>
                                    <div class="resource-meta"><?php echo esc($p['dosage']); ?> — <?php echo esc(date('M d, Y', strtotime($p['issued_at']))); ?></div>
                                    <?php if (!empty($p['instructions'])): ?><div style="font-size:13px;color:#444;margin-top:6px;white-space:pre-wrap;"><?php echo esc($p['instructions']); ?></div><?php endif; ?>
                                </div>
                                <div><span class="card-link">View</span></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
