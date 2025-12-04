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

$reports = [];
if ($patient_id) {
    $sql = "SELECT mr.id, mr.file_name, mr.file_path, mr.uploaded_at, a.id AS appointment_id, d.id AS doctor_id, u.first_name AS doctor_first, u.last_name AS doctor_last
            FROM medical_reports mr
            JOIN appointments a ON mr.appointment_id = a.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ?
            ORDER BY mr.uploaded_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $reports[] = $row;
    $stmt->close();
}

function avatar_url($first, $last) {
    $name = urlencode(trim($first . ' ' . $last));
    return "https://ui-avatars.com/api/?name={$name}&background=2a7de1&color=fff";
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Medical Records - MediCare</title>
    <link rel="stylesheet" href="patient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width:1000px;margin:20px auto;padding:0 12px;">
        <a class="back-btn" href="patient-dashboard.php">&larr; Back to Dashboard</a>
        <h2>Medical Records</h2>
        <p>All medical reports associated with your appointments.</p>
        <?php if (empty($reports)): ?>
            <div class="alert">No medical reports found.</div>
        <?php else: ?>
            <div class="resource-section">
                <ul class="resource-list">
                    <?php foreach ($reports as $r): ?>
                        <li class="resource-item">
                            <div>
                                <strong><?php echo esc($r['file_name']); ?></strong>
                                <div class="resource-meta">By <?php echo esc(($r['doctor_first'] ?? '') . ' ' . ($r['doctor_last'] ?? '')); ?> — <?php echo esc(date('M d, Y', strtotime($r['uploaded_at']))); ?></div>
                            </div>
                            <div>
                                <?php if (!empty($r['file_path'])): ?>
                                    <a class="card-link" href="download_report.php?id=<?php echo (int)$r['id']; ?>" target="_blank">Download</a>
                                <?php else: ?>
                                    <span class="card-link">No file</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
