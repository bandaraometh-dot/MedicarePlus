<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

    $flash = get_flash(); 
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'doctor') {
    redirect('login.html');
}

$uid = (int)$_SESSION['user_id'];

// Find doctor record
$stmt = $conn->prepare('SELECT id, specialization, fees FROM doctors WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$doctor = $res->fetch_assoc();
$stmt->close();

if (!$doctor) {
    // Not a doctor account
    redirect('login.html');
}

$doctor_id = (int)$doctor['id'];

// Fetch upcoming appointments for this doctor
$appointments = [];
// Count unread messages for this doctor
$unread_count = 0;
$stmt_un = $conn->prepare('SELECT COUNT(*) FROM messages WHERE to_user = ?');
$stmt_un->bind_param('i', $uid);
$stmt_un->execute();
$stmt_un->bind_result($unread_count);
$stmt_un->fetch();
$stmt_un->close();

$stmt = $conn->prepare("SELECT a.id, a.datetime, a.status, p.id AS patient_id, u.id AS patient_user_id, u.first_name, u.last_name, u.email
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE a.doctor_id = ?
    ORDER BY a.datetime ASC
    LIMIT 30");
$stmt->bind_param('i', $doctor_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $appointments[] = $row;
$stmt->close();

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
    <title>Doctor Dashboard</title>
    <link rel="stylesheet" href="doctor-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.small-note{font-size:13px;color:#666;margin-top:6px}</style>
</head>
<body>
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <img src="logo.png" width="60" height="60" alt="logo">
                <h2>MediCare Plus</h2>
            </div>
            <div class="user-info">
                <img src="<?php echo avatar_url($_SESSION['name'] ?? 'Dr',''); ?>" alt="Doctor">
                <div>
                    <h4><?php echo esc('Dr. '.($_SESSION['name'] ?? 'Doctor')); ?></h4>
                    <span><?php echo esc($doctor['specialization'] ?? ''); ?></span>
                    <div class="doctor-status online"><i class="fas fa-circle"></i> Online</div>
                </div>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="doctor-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="doctor-appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
            <li><a href="doctor-patients.php"><i class="fas fa-user-injured"></i> My Patients</a></li>
            <li><a href="doctor-records.php"><i class="fas fa-file-medical"></i> Medical Records</a></li>
            <li><a href="doctor-availability.php"><i class="fas fa-clock"></i> Availability</a></li>
            <li><a href="messages.php"><i class="fas fa-comments"></i> Messages <?php if ($unread_count>0) echo '<span class="badge" style="margin-left:8px;background:#dc3545;padding:2px 6px;border-radius:12px;font-size:12px;">'.(int)$unread_count.'</span>'; ?></a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <div class="sidebar-footer">
            <div class="quick-stats">
                <div class="stat">
                    <span class="number"><?php echo count(array_filter($appointments, function($a){ return date('Y-m-d', strtotime($a['datetime'])) === date('Y-m-d'); })); ?></span>
                    <span class="label">Today</span>
                </div>
                <div class="stat">
                    <span class="number"><?php echo count($appointments); ?></span>
                    <span class="label">Upcoming</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <header class="content-header">
            <div class="header-left">
                <h1>Doctor Dashboard</h1>
                <p>Welcome back, <?php echo esc($_SESSION['name'] ?? 'Doctor'); ?></p>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <a class="btn-action" href="doctor-appointments.php"><i class="fas fa-calendar-plus"></i> New Appointment</a>
                    <a class="btn-action" href="doctor-records.php"><i class="fas fa-file-medical"></i> Quick Report</a>
                </div>
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="badge"><?php echo 0; ?></span>
                </div>
                <div class="date-display"><span id="current-date"><?php echo date('l, F j, Y'); ?></span></div>
            </div>
        </header>

        <section class="schedule-section">
            <div class="section-header"><h2>Upcoming Appointments</h2><div class="schedule-actions"><a class="btn-primary" href="doctor-appointments.php">View All</a></div></div>
            <div class="schedule-cards">
                <?php if (empty($appointments)): ?>
                    <div class="schedule-card"><div class="schedule-info"><div class="appointment-details"><h4>No upcoming appointments</h4><p>You're all caught up.</p></div></div></div>
                <?php else: foreach ($appointments as $a):
                    $dt = strtotime($a['datetime']);
                    $time = date('h:i A', $dt);
                    $status = $a['status'] ?? 'pending';
                ?>
                    <div class="schedule-card <?php echo ($status === 'urgent') ? 'urgent' : ''; ?>">
                        <div class="schedule-time"><?php echo esc($time); ?></div>
                        <div class="schedule-info">
                            <div class="patient-avatar"><img src="<?php echo avatar_url($a['first_name'] ?? 'Patient', $a['last_name'] ?? ''); ?>" alt="Patient"></div>
                            <div class="appointment-details">
                                <h4><?php echo esc(trim(($a['first_name'] ?? 'Guest').' '.($a['last_name'] ?? ''))); ?></h4>
                                <p><?php echo esc($a['email'] ?? ''); ?></p>
                                <div class="patient-meta"><span class="age">ID: <?php echo esc($a['patient_id'] ?? 'N/A'); ?></span></div>
                            </div>
                        </div>
                        <div class="appointment-actions">
                            <span class="status <?php echo esc($status); ?>"><?php echo esc(ucfirst($status)); ?></span>
                            <a class="btn-start" href="doctor-appointments.php#appointment-<?php echo (int)$a['id']; ?>">View</a>
                            <?php if (!empty($a['patient_user_id'])): ?>
                                <a class="btn-action" href="messages.php?to=<?php echo (int)$a['patient_user_id']; ?>">Chat</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </section>

        <section class="stats-section">
            <h2>Practice Overview</h2>
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-day"></i></div><div class="stat-info"><h3><?php echo count(array_filter($appointments, function($a){ return date('Y-m-d', strtotime($a['datetime'])) === date('Y-m-d'); })); ?></h3><p>Today's Appointments</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-user-injured"></i></div><div class="stat-info"><h3><?php echo '—'; ?></h3><p>Total Patients</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-file-medical"></i></div><div class="stat-info"><h3><?php echo '—'; ?></h3><p>Pending Reports</p></div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-comments"></i></div><div class="stat-info"><h3><?php echo '—'; ?></h3><p>Unread Messages</p></div></div>
            </div>
        </section>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // placeholder interactions
            document.querySelectorAll('.btn-start').forEach(btn => btn.addEventListener('click', function(e){ /* no-op */ }));
        });
    </script>
</body>
</html>
