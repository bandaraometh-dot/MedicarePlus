<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    redirect('login.html');
}

$current = basename($_SERVER['PHP_SELF']);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') $errors[] = 'Please provide a name for the service.';

    if (empty($errors)) {
        $stmt = $conn->prepare('INSERT INTO services (name, category, description) VALUES (?,?,?)');
        $stmt->bind_param('sss', $name, $category, $description);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) redirect('admin-services.php?success=1');
        $errors[] = 'Failed to create service.';
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Service - Admin</title>
    <link rel="stylesheet" href="admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo"><img src="logo.png" width="40" height="40" alt="logo"><h2>MediCare Admin</h2></div>
            <div class="user-info"><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name'] ?? 'Admin'); ?>&background=2a7de1&color=fff" alt="admin"><div><h4><?php echo esc($_SESSION['name'] ?? 'Admin'); ?></h4><span>Administrator</span></div></div>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo ($current === 'admin-dashboard.php') ? 'active' : ''; ?>"><a href="admin-dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li class="<?php echo ($current === 'admin-services-create.php') ? 'active' : ''; ?>"><a href="admin-services.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
        </ul>
        <div class="sidebar-footer"><form method="post" action="logout.php"><button class="btn-logout">Logout</button></form></div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="content-header"><div class="header-left"><h1>Add Service</h1><p>Create a new service</p></div></div>
            <?php if (!empty($errors)): ?><div class="alert error"><?php echo esc(implode(' ', $errors)); ?></div><?php endif; ?>

            <form method="post" action="admin-services-create.php" style="max-width:700px;">
                <div class="form-row"><div class="form-group"><label>Name</label><input name="name" required></div></div>
                <div class="form-row"><div class="form-group"><label>Category</label><input name="category"></div></div>
                <div class="form-row"><div class="form-group full-width"><label>Description</label><textarea name="description" rows="4"></textarea></div></div>
                <div style="margin-top:12px;"><button class="btn-primary" type="submit">Create Service</button> <a class="card-link" href="admin-services.php">Cancel</a></div>
            </form>
        </div>
    </div>
</body>
</html>
