<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$current = basename($_SERVER['PHP_SELF']);

// Fetch upcoming appointments with patient and doctor names
$appointments = [];
$sql = "SELECT a.id, a.datetime, a.status, p.id as patient_id, up.first_name as p_first, up.last_name as p_last, d.id as doctor_id, ud.first_name as d_first, ud.last_name as d_last
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.id
        LEFT JOIN users up ON p.user_id = up.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users ud ON d.user_id = ud.id
        ORDER BY a.datetime DESC LIMIT 200";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $appointments[] = $row;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Appointments - Admin</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="logo.png" width="40" height="40" alt="logo">
                <h2>MediCare Admin</h2>
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name'] ?? 'Admin'); ?>&background=2a7de1&color=fff" alt="admin">
                <div>
                    <h4><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></h4>
                    <span>Administrator</span>
                </div>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current === 'admin-dashboard.php') ? 'active' : ''; ?>"><a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="<?php echo ($current === 'admin-doctors.php') ? 'active' : ''; ?>"><a href="admin-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
            <li class="<?php echo ($current === 'admin-patients.php') ? 'active' : ''; ?>"><a href="admin-patients.php"><i class="fas fa-user-injured"></i> Patients</a></li>
            <li class="<?php echo ($current === 'admin-services.php') ? 'active' : ''; ?>"><a href="admin-services.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
            <li class="<?php echo ($current === 'admin-appointments.php') ? 'active' : ''; ?>"><a href="admin-appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
            <li class="<?php echo ($current === 'logs.php') ? 'active' : ''; ?>"><a href="logs.php"><i class="fas fa-clipboard-list"></i> Logs</a></li>
        </ul>
        <div class="sidebar-footer">
            <form method="post" action="logout.php"><button class="btn-logout">Logout</button></form>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <div class="header-left">
                <h1>Manage Appointments</h1>
                <p>View and manage appointment requests</p>
            </div>
        </div>

        <section class="stats-section">
            <div class="stats-grid">
                <?php if (empty($appointments)): ?>
                    <div class="stat-card">No appointments found.</div>
                <?php else: ?>
                    <?php foreach ($appointments as $a): ?>
                        <div class="stat-card">
                            <div style="display:flex; gap:12px; align-items:center; width:100%;">
                                <div class="stat-info">
                                    <h3><?php echo esc(($a['d_first'] ?? 'Dr') . ' ' . ($a['d_last'] ?? '')); ?></h3>
                                    <p>Patient: <?php echo esc(($a['p_first'] ?? '') . ' ' . ($a['p_last'] ?? '')); ?></p>
                                    <p><?php echo esc(date('M d, Y H:i', strtotime($a['datetime']))); ?> — <strong><?php echo esc(ucfirst($a['status'])); ?></strong></p>
                                </div>
                                <div style="margin-left:auto; text-align:right;">
                                    <a class="card-link" href="admin-appointments-edit.php?id=<?php echo (int)$a['id']; ?>">Edit</a>
                                    <a class="card-link" href="admin-appointments-delete.php?id=<?php echo (int)$a['id']; ?>">Delete</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
