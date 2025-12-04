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
    $sql = "SELECT a.id, a.datetime, a.status, d.id as doctor_id, u.id as doctor_user_id, u.first_name as doctor_first, u.last_name as doctor_last, d.specialization
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

// Fetch recent medical reports (join appointments)
$medical_reports = [];
try {
    $sql = "SELECT mr.id, mr.file_name, mr.file_path, mr.uploaded_at, a.id AS appointment_id, d.id AS doctor_id, u.first_name AS doctor_first, u.last_name AS doctor_last
        FROM medical_reports mr
        JOIN appointments a ON mr.appointment_id = a.id
        LEFT JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN users u ON d.user_id = u.id
        WHERE a.patient_id = ?
        ORDER BY mr.uploaded_at DESC
        LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $medical_reports[] = $row;
    $stmt->close();
} catch (Exception $e) {
    // table may not exist or other DB error — ignore and show none
    $medical_reports = [];
}

// Fetch recent prescriptions if table exists
$prescriptions = [];
$has_prescriptions = false;
try {
    $r = $conn->query("SHOW TABLES LIKE 'prescriptions'");
    if ($r && $r->num_rows > 0) {
        $has_prescriptions = true;
        $sql = "SELECT id, medicine_name, dosage, instructions, issued_at, doctor_id FROM prescriptions WHERE patient_id = ? ORDER BY issued_at DESC LIMIT 6";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $prescriptions[] = $row;
        $stmt->close();
    }
} catch (Exception $e) {
    $prescriptions = [];
}

// Fetch recent messages (inbox)
$messages = [];
try {
    $sql = 'SELECT m.id, m.from_user, m.subject, m.body, m.timestamp, u.first_name, u.last_name FROM messages m LEFT JOIN users u ON m.from_user = u.id WHERE m.to_user = ? ORDER BY m.timestamp DESC LIMIT 6';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $messages[] = $row;
    $stmt->close();
} catch (Exception $e) {
    $messages = [];
}

// Show flash messages if any
$flash = null;
try {
    $flash = get_flash();
} catch (Exception $e) {
    $flash = null;
}

// Handle doctor search (optional query param `q`)
$doctor_search_results = [];
$search_query = '';
$show_all = !empty($_GET['show_all']) && $_GET['show_all'] == '1';
if ($show_all) {
    // Return all doctors (limit to 200 to avoid huge resultsets)
    $sql = "SELECT d.id AS doctor_id, u.id AS user_id, u.first_name, u.last_name, d.specialization
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            ORDER BY u.first_name ASC, u.last_name ASC
            LIMIT 200";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $doctor_search_results[] = $r;
        $stmt->close();
    } catch (Exception $e) {
        $doctor_search_results = [];
    }
} elseif (!empty($_GET['q'])) {
    $search_query = trim($_GET['q']);
    // Simple safe search: match against users.first_name/last_name and doctors.specialization
    $like = '%' . $conn->real_escape_string($search_query) . '%';
    $sql = "SELECT d.id AS doctor_id, u.id AS user_id, u.first_name, u.last_name, d.specialization
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR d.specialization LIKE ?
            LIMIT 200";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $doctor_search_results[] = $r;
        $stmt->close();
    } catch (Exception $e) {
        $doctor_search_results = [];
    }
}

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
                    <li><a href="doctors.php">Doctors</a></li>
                    <li><a href="medical-records.php">Medical Records</a></li>
                    <li><a href="prescriptions.php">Prescriptions</a></li>
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
                    <form method="post" action="logout.php" class="logout-form">
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
                <?php if (!empty($flash)): ?>
                    <div class="alert <?php echo esc($flash['type'] ?? ''); ?>"><?php echo esc($flash['message'] ?? ''); ?></div>
                <?php endif; ?>
                <?php if (!empty($_GET['error'])): ?>
                    <div class="alert error"><?php echo esc($_GET['error']); ?></div>
                <?php endif; ?>
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
                                        <div class="appointment-status-wrapper">
                                            <span class="status <?php echo esc(strtolower($appt['status'] ?? 'pending')); ?>"><?php echo esc(ucfirst($appt['status'] ?? 'Pending')); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <?php $isUpcoming = (strtotime($appt['datetime']) >= time()); ?>
                                    <?php if (in_array(strtolower($appt['status']), ['pending', 'rescheduled']) && $isUpcoming): ?>
                                        <a class="btn-secondary" href="reschedule.php?id=<?php echo (int)$appt['id']; ?>">Reschedule</a>
                                        <a class="btn-danger" href="cancel_appointment.php?id=<?php echo (int)$appt['id']; ?>" onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</a>
                                        <?php if (!empty($appt['doctor_user_id'])): ?>
                                            <a class="btn-action" href="messages.php?to=<?php echo (int)$appt['doctor_user_id']; ?>">Chat</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="no-actions-text">No actions</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Quick Actions -->
            <!-- Doctor Search -->
            <section class="doctor-search-section">
                <h3>Find & Book Doctors</h3>
                <form method="get" action="patient-dashboard.php" class="doctor-search-form">
                    <input type="text" name="q" placeholder="Search by name or specialization" value="<?php echo esc($search_query); ?>" class="search-input">
                    <button class="btn-primary" type="submit">Search</button>
                    <a class="btn-action btn-search-action" href="patient-dashboard.php?show_all=1">Show All</a>
                    <a class="btn-secondary btn-search-action" href="patient-dashboard.php">Clear</a>
                </form>
                <?php if ($search_query !== '' || $show_all): ?>
                    <div class="search-results-header">
                        <?php if ($show_all): ?>
                            <strong>All Doctors</strong>
                        <?php else: ?>
                            <strong>Results for:</strong> <?php echo esc($search_query); ?>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($doctor_search_results)): ?>
                        <p>No doctors found.</p>
                    <?php else: ?>
                        <div class="doctors-search-results">
                            <?php foreach ($doctor_search_results as $d): ?>
                                <?php $fullname = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')); ?>
                                <div class="doctor-card-small">
                                    <div class="doctor-info-small">
                                        <img src="<?php echo avatar_url($d['first_name'], $d['last_name']); ?>" alt="<?php echo esc($fullname); ?>" class="doctor-avatar-small">
                                        <div>
                                            <strong><?php echo esc($fullname); ?></strong>
                                            <div class="doctor-specialization-small"><?php echo esc($d['specialization']); ?></div>
                                        </div>
                                    </div>
                                    <div class="doctor-actions-small">
                                        <a class="btn-action" href="appointments.html?doctor=<?php echo (int)$d['doctor_id']; ?>&doctor_name=<?php echo urlencode($fullname); ?>">Book</a>
                                        <a class="btn-secondary" href="doctors.php">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions-section">
                <h3>Quick Actions</h3>
                <div class="actions-grid">
                    <div class="action-card">
                        <i class="fas fa-search"></i>
                        <h4>Find Doctors</h4>
                        <p>Search and book appointments with specialists</p>
                        <a class="btn-action" href="doctors.php">Explore</a>
                    </div>
                    <div class="action-card">
                        <i class="fas fa-file-medical"></i>
                        <h4>Medical Records</h4>
                        <p>View your test results and medical history</p>
                        <a class="btn-action" href="medical-records.php">View</a>
                    </div>
                    <div class="action-card">
                        <i class="fas fa-prescription"></i>
                        <h4>Prescriptions</h4>
                        <p>Access your current and past prescriptions</p>
                        <a class="btn-action" href="prescriptions.php">Check</a>
                    </div>
                    <div class="action-card">
                        <i class="fas fa-comments"></i>
                        <h4>Messages</h4>
                        <p>Communicate with your healthcare providers</p>
                        <a class="btn-action" href="messages.php">Open</a>
                    </div>
                </div>
            </section>

            <!-- Medical Reports, Prescriptions, Messages -->
            <section class="patient-resources-section">
                <div class="section-header"><h3>Your Documents & Messages</h3></div>
                <div class="resources-grid"> 
                    <div class="resource-section">
                        <h4>Recent Medical Reports</h4>
                        <?php if (empty($medical_reports)): ?>
                            <p>No reports available.</p>
                        <?php else: ?>
                            <ul class="resource-list">
                                <?php foreach ($medical_reports as $r): ?>
                                    <li class="resource-item">
                                        <div>
                                            <strong><?php echo esc($r['file_name']); ?></strong>
                                            <div class="resource-meta"><?php echo esc($r['doctor_first'].' '.$r['doctor_last']); ?> — <?php echo esc(date('M d, Y', strtotime($r['uploaded_at']))); ?></div>
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
                        <?php endif; ?>
                    </div>

                    <div class="resource-section">
                        <h4>Recent Prescriptions</h4>
                        <?php if (!$has_prescriptions): ?>
                            <p>No prescription module installed.</p>
                        <?php elseif (empty($prescriptions)): ?>
                            <p>No prescriptions found.</p>
                        <?php else: ?>
                            <ul class="resource-list">
                                <?php foreach ($prescriptions as $p): ?>
                                    <li class="resource-item">
                                        <div>
                                            <strong><?php echo esc($p['medicine_name']); ?></strong>
                                            <div class="resource-meta"><?php echo esc($p['dosage'] ?? ''); ?> — <?php echo esc(date('M d, Y', strtotime($p['issued_at'] ?? ''))); ?></div>
                                        </div>
                                        <div><span class="card-link">View</span></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="resource-section">
                        <h4>Recent Messages</h4>
                        <?php if (empty($messages)): ?>
                            <p>No messages yet.</p>
                        <?php else: ?>
                            <ul class="resource-list">
                                <?php foreach ($messages as $m): ?>
                                    <li class="resource-item">
                                        <div>
                                            <strong><?php echo esc($m['subject'] ?: 'No subject'); ?></strong>
                                            <div class="resource-meta">From <?php echo esc($m['first_name'].' '.$m['last_name']); ?> — <?php echo esc(date('M d, Y H:i', strtotime($m['timestamp']))); ?></div>
                                        </div>
                                        <div><a class="card-link" href="messages.php#message-<?php echo (int)$m['id']; ?>">Open</a></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
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
