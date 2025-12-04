<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$current = basename($_SERVER['PHP_SELF']);

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('admin-doctors.php?error=invalid_id');

// Load doctor + user
$stmt = $conn->prepare('SELECT d.id AS doctor_id, d.user_id, d.specialization, d.fees, u.first_name, u.last_name, u.email, u.username FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$doctor = $res->fetch_assoc();
$stmt->close();

if (!$doctor) redirect('admin-doctors.php?error=not_found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $fees = isset($_POST['fees']) ? (float)$_POST['fees'] : 0.00;

    if ($first === '' || $last === '' || $email === '' || $username === '') {
        $errors[] = 'Please fill in required fields.';
    }

    if (empty($errors)) {
        // Use transaction to update both tables
        $conn->begin_transaction();
        try {
            // Update users
            if ($password !== '') {
                $pw_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET first_name=?, last_name=?, email=?, username=?, password=? WHERE id=?');
                $stmt->bind_param('sssssi', $first, $last, $email, $username, $pw_hash, $doctor['user_id']);
            } else {
                $stmt = $conn->prepare('UPDATE users SET first_name=?, last_name=?, email=?, username=? WHERE id=?');
                $stmt->bind_param('ssssi', $first, $last, $email, $username, $doctor['user_id']);
            }
            $stmt->execute();
            $stmt->close();

            // Update doctors
            $stmt = $conn->prepare('UPDATE doctors SET specialization=?, fees=? WHERE id=?');
            $stmt->bind_param('sdi', $specialization, $fees, $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            redirect('admin-doctors.php?updated=1');
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'Failed to update doctor: ' . $e->getMessage();
        }
    }
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
    <title>Edit Doctor - Admin</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>.small-note{font-size:12px;color:#666;margin-top:6px}</style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><img src="logo.png" width="40" height="40" alt="logo"><h2>MediCare Admin</h2></div>
            <div class="user-info"><img src="<?php echo avatar_url($_SESSION['name'] ?? 'Admin',''); ?>" alt="admin"><div><h4><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></h4><span>Administrator</span></div></div>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current === 'admin-dashboard.php') ? 'active' : ''; ?>"><a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="<?php echo ($current === 'admin-doctors.php') ? 'active' : ''; ?>"><a href="admin-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
        </ul>
        <div class="sidebar-footer"><form method="post" action="logout.php"><button class="btn-logout">Logout</button></form></div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="content-header"><div class="header-left"><h1>Edit Doctor</h1><p>Modify doctor profile and account</p></div></div>

            <?php if (!empty($errors)): ?><div class="alert error"><?php echo esc(implode(' ', $errors)); ?></div><?php endif; ?>

            <form method="post" action="admin-doctors-edit.php?id=<?php echo (int)$id; ?>" style="max-width:700px;">
                <div class="form-row">
                    <div class="form-group"><label>First Name</label><input name="first_name" required value="<?php echo esc($doctor['first_name']); ?>"></div>
                    <div class="form-group"><label>Last Name</label><input name="last_name" required value="<?php echo esc($doctor['last_name']); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" name="email" required value="<?php echo esc($doctor['email']); ?>"></div>
                    <div class="form-group"><label>Username</label><input name="username" required value="<?php echo esc($doctor['username']); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>New Password (leave blank to keep)</label><input type="password" name="password"></div>
                    <div class="form-group"><label>Specialization</label><input name="specialization" value="<?php echo esc($doctor['specialization']); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Fees</label><input type="number" step="0.01" name="fees" value="<?php echo esc($doctor['fees']); ?>"></div>
                </div>
                <div class="small-note">Leaving the password blank will not change the current password.</div>
                <div style="margin-top:12px;"><button class="btn-primary" type="submit">Save Changes</button> <a class="card-link" href="admin-doctors.php">Cancel</a></div>
            </form>
        </div>
    </div>
</body>
</html>
