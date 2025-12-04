<?php

require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$user_id = (int) $_SESSION['user_id'];
$current = basename($_SERVER['PHP_SELF']);

// Fetch statistics
$stats = [];
$stmt = $conn->prepare('SELECT COUNT(*) FROM users');
$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();
$stats['users'] = (int)$total_users;

$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'doctor'");
$stmt->execute();
$stmt->bind_result($total_doctors);
$stmt->fetch();
$stmt->close();
$stats['doctors'] = (int)$total_doctors;

$stmt = $conn->prepare('SELECT COUNT(*) FROM patients');
$stmt->execute();
$stmt->bind_result($total_patients);
$stmt->fetch();
$stmt->close();
$stats['patients'] = (int)$total_patients;

$stmt = $conn->prepare('SELECT COUNT(*) FROM services');
$stmt->execute();
$stmt->bind_result($total_services);
$stmt->fetch();
$stmt->close();
$stats['services'] = (int)$total_services;

$stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE datetime >= NOW()");
$stmt->execute();
$stmt->bind_result($upcoming_appts);
$stmt->fetch();
$stmt->close();
$stats['upcoming_appointments'] = (int)$upcoming_appts;

$stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'pending'");
$stmt->execute();
$stmt->bind_result($pending_appts);
$stmt->fetch();
$stmt->close();
$stats['pending_appointments'] = (int)$pending_appts;

// logs table may not exist on older installs — handle gracefully
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM logs");
    $stmt->execute();
    $stmt->bind_result($total_logs);
    $stmt->fetch();
    $stmt->close();
    $stats['logs'] = (int)$total_logs;
} catch (mysqli_sql_exception $e) {
    // If logs table missing or other DB error, default to 0
    $stats['logs'] = 0;
}

    // Flash message (one-time)
    $flash = get_flash();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard - MediCare Plus</title>
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
        <div class="container">
            <?php if (!empty($flash)): ?>
                <div style="padding:12px;border-radius:8px;margin-bottom:12px;font-weight:600;background:#d4edda;color:#155724;border:1px solid #c3e6cb;">
                    <?php echo esc($flash['message']); ?>
                </div>
            <?php endif; ?>
            <h2>Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Users</h3>
                    <p class="stat-value"><?php echo $stats['users']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Doctors</h3>
                    <p class="stat-value"><?php echo $stats['doctors']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Patients</h3>
                    <p class="stat-value"><?php echo $stats['patients']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Services</h3>
                    <p class="stat-value"><?php echo $stats['services']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Upcoming Appointments</h3>
                    <p class="stat-value"><?php echo $stats['upcoming_appointments']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Pending Appointments</h3>
                    <p class="stat-value"><?php echo $stats['pending_appointments']; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Log Entries</h3>
                    <p class="stat-value"><?php echo $stats['logs']; ?></p>
                </div>
            </div>

            <!-- quick-links removed per request -->
        </div>
    </main>
</body>
</html>
