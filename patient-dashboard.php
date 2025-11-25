<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

// Ensure user is logged in
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    redirect('login.html');
}

$user_id = (int) $_SESSION['user_id'];

// Fetch basic user info
$user = null;
$stmt = $conn->prepare('SELECT first_name, last_name, email FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $email);
if ($stmt->fetch()) {
    $user = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
    ];
}
$stmt->close();

// Get patient id (patients table references users)
$patient_id = null;
$stmt = $conn->prepare('SELECT id FROM patients WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($pid);
if ($stmt->fetch()) {
    $patient_id = (int) $pid;
}
$stmt->close();

// Fetch upcoming appointments for this patient
$appointments = [];
if ($patient_id) {
    $sql = "SELECT a.id, a.datetime, a.status, d.id as doctor_id, u.first_name as doctor_first, u.last_name as doctor_last, d.specialization
            FROM appointments a
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE a.patient_id = ? AND a.datetime >= NOW()
            ORDER BY a.datetime ASC
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}

// Count unread messages
$unread_count = 0;
$stmt = $conn->prepare('SELECT COUNT(*) FROM messages WHERE to_user = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($unread_count);
$stmt->fetch();
$stmt->close();

function avatar_url($first, $last) {
    $name = urlencode(trim($first . ' ' . $last));
    return "https://ui-avatars.com/api/?name={$name}&background=2a7de1&color=fff";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MediCare Plus</title>
    <link rel="stylesheet" href="patient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="patient-header">
        <div class="container">
            <div class="logo">
                <img src="logo.png" width="100px" height="100px">
                <h1>MediCare Plus</h1>
            </div>
            <nav class="patient-nav">
                <ul>
                    <li class="active"><a href="patient-dashboard.php">Dashboard</a></li>
                    <li><a href="appointments.html">Appointments</a></li>
                    <li><a href="doctors.html">Doctors</a></li>
                    <li><a href="medical-records.html">Medical Records</a></li>
                    <li><a href="prescriptions.html">Prescriptions</a></li>
                    <li><a href="messages.php">Messages</a></li>
                </ul>
            </nav>
            <div class="patient-actions">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="badge"><?php echo (int) $unread_count; ?></span>
                </div>
                <div class="user-menu">
                    <img src="<?php echo avatar_url($user['first_name'] ?? '', $user['last_name'] ?? ''); ?>" alt="Patient">
                    <span><?php echo esc(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></span>
                    <form method="post" action="logout.php" style="display:inline;">
                        <button class="btn-logout" type="submit">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="patient-main">
        <div class="container">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <h2>Welcome back, <?php echo esc($user['first_name'] ?? ''); ?>!</h2>
                <p>Here's your health overview and upcoming appointments</p>
            </section>

            <!-- Upcoming Appointments -->
            <section class="appointments-section">
                <div class="section-header">
                    <h3>Upcoming Appointments</h3>
                    <a class="btn-primary" href="appointments.html">Book New Appointment</a>
                </div>
                <div class="appointments-grid">
                    <?php if (empty($appointments)): ?>
                        <p>No upcoming appointments.</p>
                    <?php else: ?>
                        <?php foreach ($appointments as $appt): ?>
                            <div class="appointment-card">
                                <div class="appointment-info">
                                    <div class="doctor-avatar">
                                        <img src="<?php echo avatar_url($appt['doctor_first'] ?? 'Dr', $appt['doctor_last'] ?? ''); ?>" alt="Doctor">
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?php echo esc(($appt['doctor_first'] ?? '') . ' ' . ($appt['doctor_last'] ?? '')); ?></h4>
                                        <p><?php echo esc($appt['specialization'] ?? ''); ?></p>
                                        <span class="appointment-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo esc(date('M d, Y, H:i', strtotime($appt['datetime']))); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <a class="btn-secondary" href="reschedule.php?id=<?php echo (int)$appt['id']; ?>">Reschedule</a>
                                    <a class="btn-danger" href="cancel_appointment.php?id=<?php echo (int)$appt['id']; ?>">Cancel</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions-section">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <div class="action-card">
                        <i class="fas fa-search"></i>
                        <h4>Find Doctors</h4>
                        <p>Search and book appointments with specialists</p>
                        <a class="btn-action" href="doctors.html">Explore</a>
                    </div>
                    <div class="action-card">
                        <i class="fas fa-file-medical"></i>
                        <h4>Medical Records</h4>
                        <p>View your test results and medical history</p>
                        <a class="btn-action" href="medical-records.html">View</a>
                    </div>
                    <div class="action-card">
                        <i class="fas fa-prescription"></i>
                        <h4>Prescriptions</h4>
                        <p>Access your current and past prescriptions</p>
                        <a class="btn-action" href="prescriptions.html">Check</a>
                    </div>
                    <div class="action-card">
                        <i class="fas fa-comments"></i>
                        <h4>Messages</h4>
                        <p>Communicate with your healthcare providers</p>
                        <a class="btn-action" href="messages.php">Open</a>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="recent-activity-section">
                <h3>Recent Activity</h3>
                <div class="activity-list">
                    <div class="activity-item">
                        <i class="fas fa-calendar-check success"></i>
                        <div>
                            <p>Appointment booked</p>
                            <span>Recent</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Optional client-side enhancements can go here
        });
    </script>
</body>
</html>
