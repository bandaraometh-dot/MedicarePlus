<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$current = basename($_SERVER['PHP_SELF']);

// Fetch patients
$patients = [];
$sql = "SELECT p.id AS patient_id, u.id AS user_id, u.first_name, u.last_name, u.email, p.dob, p.gender FROM patients p LEFT JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 100";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) $patients[] = $row;
}

function avatar_url($first, $last) {
    $name = urlencode(trim($first . ' ' . $last));
    return "https://ui-avatars.com/api/?name={$name}&background=2a7de1&color=fff";
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Patients - Admin</title>
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
                <img src="<?php echo avatar_url($_SESSION['name'] ?? 'Admin',''); ?>" alt="admin">
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
                <h1>Manage Patients</h1>
                <p>List of registered patients</p>
            </div>
            <div class="header-right">
                <a class="card-link" href="admin-patients-create.php">+ Add Patient</a>
            </div>
        </div>

        <section class="stats-section">
            <div class="stats-grid">
                <?php if (empty($patients)): ?>
                    <div class="stat-card">No patients found.</div>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                        <div class="stat-card">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <div class="stat-icon"><img src="<?php echo avatar_url($p['first_name'],$p['last_name']); ?>" style="width:36px;height:36px;border-radius:6px;"/></div>
                                <div class="stat-info">
                                    <h3><?php echo esc($p['first_name'].' '.$p['last_name']); ?></h3>
                                    <p><?php echo esc($p['email'] ?? ''); ?> — <?php echo esc($p['gender'] ?? ''); ?></p>
                                </div>
                                <div style="margin-left:auto; text-align:right;">
                                    <a class="card-link" href="admin-patients-edit.php?id=<?php echo (int)$p['patient_id']; ?>">Edit</a>
                                    <a class="card-link" href="admin-patients-delete.php?id=<?php echo (int)$p['patient_id']; ?>">Delete</a>
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
