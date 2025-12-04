<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$current = basename($_SERVER['PHP_SELF']);

// Simple logs viewer (latest 200 entries)
$logs = [];
$logs_error = null;
try {
    $stmt = $conn->prepare('SELECT l.id, l.user_id, u.first_name, u.last_name, l.level, l.message, l.ip_address, l.created_at FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 200');
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $logs[] = $row;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Likely the logs table does not exist — show a friendly admin message instead of fatal error
    $logs_error = $e->getMessage();
    $logs = [];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Logs - Admin</title>
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
                <h1>System Logs</h1>
                <p>Recent system and security events</p>
            </div>
        </div>

        <section class="charts-section">
            <div class="chart-card">
                <h3>Recent Logs</h3>
                <?php if (empty($logs)): ?>
                    <div class="chart-placeholder">No log entries.</div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($logs as $l): ?>
                            <div class="activity-item">
                                <i class="info"></i>
                                <div style="flex:1;">
                                    <p><strong><?php echo esc(strtoupper($l['level'])); ?></strong> — <?php echo esc($l['message']); ?></p>
                                    <span><?php echo esc($l['first_name'].' '.$l['last_name']); ?> @ <?php echo esc($l['ip_address']); ?> — <?php echo esc($l['created_at']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</body>
</html>
