<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$current = basename($_SERVER['PHP_SELF']);

// Handle POST: create a new user with role 'doctor' and doctor record
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $fees = isset($_POST['fees']) ? (float)$_POST['fees'] : 0.00;

    if ($first === '' || $last === '' || $email === '' || $username === '' || $password === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if (empty($errors)) {
        // Create user
        $pw_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (first_name,last_name,username,email,password,role) VALUES (?,?,?,?,?,?)');
        $role = 'doctor';
        $stmt->bind_param('ssssss', $first, $last, $username, $email, $pw_hash, $role);
        $ok = $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        if ($ok) {
            // Insert into doctors
            $stmt = $conn->prepare('INSERT INTO doctors (user_id, specialization, fees) VALUES (?,?,?)');
            $stmt->bind_param('isd', $user_id, $specialization, $fees);
            $stmt->execute();
            $stmt->close();
            redirect('admin-doctors.php?success=1');
        } else {
            $errors[] = 'Failed to create user. Email or username may already exist.';
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
    <title>Add Doctor - Admin</title>
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
            <li class="<?php echo ($current === 'admin-dashboard.php') ? 'active' : ''; ?>"><a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="<?php echo ($current === 'admin-doctors-create.php') ? 'active' : ''; ?>"><a href="admin-doctors.php"><i class="fas fa-user-md"></i> Doctors</a></li>
        </ul>
        <div class="sidebar-footer"><form method="post" action="logout.php"><button class="btn-logout">Logout</button></form></div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="content-header"><div class="header-left"><h1>Add Doctor</h1><p>Create a new doctor account</p></div></div>

            <?php if (!empty($errors)): ?>
                <div class="alert error"><?php echo esc(implode(' ', $errors)); ?></div>
            <?php endif; ?>

            <form method="post" action="admin-doctors-create.php" style="max-width:700px;">
                <div class="form-row">
                    <div class="form-group"><label>First Name</label><input name="first_name" required></div>
                    <div class="form-group"><label>Last Name</label><input name="last_name" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                    <div class="form-group"><label>Username</label><input name="username" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                    <div class="form-group"><label>Specialization</label><input name="specialization"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Fees</label><input type="number" step="0.01" name="fees" value="0.00"></div>
                </div>
                <div style="margin-top:12px;"><button class="btn-primary" type="submit">Create Doctor</button> <a class="card-link" href="admin-doctors.php">Cancel</a></div>
            </form>
        </div>
    </div>
</body>
</html>
