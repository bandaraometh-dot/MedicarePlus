<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) redirect('admin-doctors.php?error=invalid_id');

// Load doctor to show confirmation
$stmt = $conn->prepare('SELECT d.id AS doctor_id, d.user_id, u.first_name, u.last_name, u.email FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$doctor = $res->fetch_assoc();
$stmt->close();

if (!$doctor) redirect('admin-doctors.php?error=not_found');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Perform deletion inside transaction: remove appointments, doctor, then user
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('DELETE FROM appointments WHERE doctor_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM doctors WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $doctor['user_id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        redirect('admin-doctors.php?deleted=1');
    } catch (Exception $e) {
        $conn->rollback();
        redirect('admin-doctors.php?error=delete_failed');
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
    <title>Delete Doctor - Admin</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><img src="logo.png" width="40" height="40" alt="logo"><h2>MediCare Admin</h2></div>
            <div class="user-info"><img src="<?php echo avatar_url($_SESSION['name'] ?? 'Admin',''); ?>" alt="admin"><div><h4><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></h4><span>Administrator</span></div></div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="active"><a href="admin-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
        </ul>
        <div class="sidebar-footer"><form method="post" action="logout.php"><button class="btn-logout">Logout</button></form></div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="content-header"><div class="header-left"><h1>Delete Doctor</h1><p>Confirm deletion of this doctor and associated data</p></div></div>

            <div class="card" style="max-width:700px;">
                <h3>Are you sure you want to delete?</h3>
                <p><strong><?php echo esc($doctor['first_name'].' '.$doctor['last_name']); ?></strong> — <?php echo esc($doctor['email']); ?></p>
                <p class="small">This will remove the doctor's account and any appointments linked to them. This action cannot be undone.</p>

                <form method="post" action="admin-doctors-delete.php?id=<?php echo (int)$id; ?>">
                    <button class="btn-danger" type="submit">Yes, delete</button>
                    <a class="card-link" href="admin-doctors.php">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
