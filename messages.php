<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    redirect('login.html');
}

$user_id = (int)$_SESSION['user_id'];

// If id provided show message detail
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id > 0) {
    $stmt = $conn->prepare('SELECT m.id, m.from_user, m.subject, m.body, m.timestamp, u.first_name, u.last_name FROM messages m LEFT JOIN users u ON m.from_user = u.id WHERE m.id = ? AND m.to_user = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $message = $res->fetch_assoc();
    $stmt->close();
    if (!$message) redirect('messages.php?error=not_found');
}

// Handle sending a new message (compose)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $to_user = isset($_POST['to_user']) ? (int)$_POST['to_user'] : 0;
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $body = isset($_POST['body']) ? trim($_POST['body']) : '';

    // Basic validation
    if ($to_user <= 0 || $body === '') {
        redirect('messages.php?error=invalid_input');
    }

    // Ensure patients can only message doctors (enforce chat restriction)
    if ($_SESSION['role'] === 'patient') {
        $stmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $to_user);
        $stmt->execute();
        $stmt->bind_result($target_role);
        $ok = $stmt->fetch();
        $stmt->close();
        if (!$ok || strtolower(trim($target_role)) !== 'doctor') {
            redirect('messages.php?error=not_allowed');
        }
    }

    // Insert message
    $stmt = $conn->prepare('INSERT INTO messages (from_user, to_user, subject, body) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiss', $user_id, $to_user, $subject, $body);
    $stmt->execute();
    $stmt->close();

    redirect('messages.php?to=' . (int)$to_user . '&sent=1');
}

// Fetch inbox list
$messages = [];
$stmt = $conn->prepare('SELECT m.id, m.from_user, m.subject, m.timestamp, u.first_name, u.last_name FROM messages m LEFT JOIN users u ON m.from_user = u.id WHERE m.to_user = ? ORDER BY m.timestamp DESC LIMIT 200');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $messages[] = $row;
$stmt->close();

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
    <title>Messages - MediCare</title>
    <link rel="stylesheet" href="patient-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.messages-grid{display:grid;grid-template-columns:300px 1fr;gap:20px}.msg-list{background:#fff;padding:12px;border-radius:8px}.msg-item{padding:10px;border-bottom:1px solid #eee}.msg-item a{text-decoration:none;color:inherit;display:block}.msg-detail{background:#fff;padding:16px;border-radius:8px}</style>
</head>
<body>
    <div style="max-width:1100px;margin:20px auto;padding:0 12px;">
        <a class="back-btn" href="patient-dashboard.php">&larr; Back to Dashboard</a>
        <h2>Inbox</h2>
        <?php if (!empty($_GET['error'])): ?><div class="alert error"><?php echo esc($_GET['error']); ?></div><?php endif; ?>
        <div class="messages-grid">
            <div class="msg-list">
                <?php if (empty($messages)): ?><p>No messages.</p><?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="msg-item"><a href="messages.php?id=<?php echo (int)$m['id']; ?>"><strong><?php echo esc($m['subject'] ?: 'No subject'); ?></strong><div style="font-size:12px;color:#666;">From <?php echo esc($m['first_name'].' '.$m['last_name']); ?> — <?php echo esc(date('M d, Y H:i', strtotime($m['timestamp']))); ?></div></a></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div>
                <!-- Compose / Chat box -->
                <?php
                $to = isset($_GET['to']) ? (int)$_GET['to'] : 0;
                $target_user = null;
                if ($to > 0) {
                    $stmt = $conn->prepare('SELECT id, first_name, last_name, role FROM users WHERE id = ? LIMIT 1');
                    $stmt->bind_param('i', $to);
                    $stmt->execute();
                    $resu = $stmt->get_result();
                    $target_user = $resu->fetch_assoc();
                    $stmt->close();
                }
                ?>
                <?php
                // If no explicit target and current user is a doctor, fetch recent patients so doctor can start chats
                $doctor_patients = [];
                if (empty($target_user) && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'doctor') {
                    // find doctor id for this user
                    $stmt = $conn->prepare('SELECT id FROM doctors WHERE user_id = ? LIMIT 1');
                    $stmt->bind_param('i', $user_id);
                    $stmt->execute();
                    $stmt->bind_result($did);
                    $has_doctor = $stmt->fetch();
                    $stmt->close();
                    if ($has_doctor && $did) {
                        $sql = "SELECT DISTINCT p.user_id as user_id, u.first_name, u.last_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN users u ON p.user_id = u.id WHERE a.doctor_id = ? ORDER BY a.datetime DESC LIMIT 50";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $did);
                        $stmt->execute();
                        $resu = $stmt->get_result();
                        while ($r = $resu->fetch_assoc()) $doctor_patients[] = $r;
                        $stmt->close();
                    }
                }

                if (!empty($target_user)): ?>
                    <div class="resource-section">
                        <h3>Chat with <?php echo esc($target_user['first_name'].' '.$target_user['last_name']); ?></h3>
                        <?php if (!empty($_GET['sent'])): ?><div class="alert success">Message sent.</div><?php endif; ?>
                        <?php if ($_SESSION['role'] === 'patient' && strtolower($target_user['role']) !== 'doctor'): ?>
                            <div class="alert error">You may only message doctors.</div>
                        <?php else: ?>
                            <form method="post" action="messages.php">
                                <input type="hidden" name="action" value="send_message">
                                <input type="hidden" name="to_user" value="<?php echo (int)$target_user['id']; ?>">
                                <div style="margin-bottom:8px;"><input type="text" name="subject" placeholder="Subject (optional)" style="width:100%;padding:8px;border:1px solid #e9ecef;border-radius:6px;" /></div>
                                <div style="margin-bottom:8px;"><textarea name="body" placeholder="Type your message..." rows="6" style="width:100%;padding:8px;border:1px solid #e9ecef;border-radius:6px;"></textarea></div>
                                <div><button class="btn-action" type="submit">Send Message</button></div>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php if (!empty($doctor_patients)): ?>
                        <div class="resource-section">
                            <h3>Start Chat with a Patient</h3>
                            <p class="small-note">Recent patients. Click a name to start a conversation.</p>
                            <ul class="resource-list" style="margin-top:12px;">
                                <?php foreach ($doctor_patients as $p): ?>
                                    <li class="resource-item">
                                        <div>
                                            <strong><?php echo esc($p['first_name'].' '.$p['last_name']); ?></strong>
                                        </div>
                                        <div><a class="card-link" href="messages.php?to=<?php echo (int)$p['user_id']; ?>">Chat</a></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="msg-detail"><p>Select a message from the left to view its content, or use the Chat links from appointments to start a conversation.</p></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
