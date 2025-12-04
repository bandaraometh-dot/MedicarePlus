<?php
// Ensure session is started before any output or DB includes
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'dbconnect.php';
require_once 'helpers.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.html');
}

try {
    // Role (patient/doctor/admin) is provided via query string from the form action
    $role = isset($_GET['role']) ? $_GET['role'] : 'patient';
    // The login field accepts either username OR email — allow users to enter either.
    $identifier = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $identifier = esc($identifier);
    if (empty($identifier) || empty($password)) {
        redirect('login.html?error=missing');
    }

    // Find user by username OR email (don't require the requested role to match)
    $stmt = $conn->prepare('SELECT id, password, role, first_name, last_name FROM users WHERE (username = ? OR email = ?) LIMIT 1');
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        redirect('login.html?error=invalid');
    }

    $stmt->bind_result($id, $password_hash, $db_role, $first_name, $last_name);
    $stmt->fetch();

    if (empty($password_hash) || !password_verify($password, $password_hash)) {
        $stmt->close();
        redirect('login.html?error=invalid');
    }

    // Authentication successful
    // If the user submitted a requested role from the login form, ensure it matches the account's role.
    $requested_role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';
    $db_role = is_string($db_role) ? strtolower(trim($db_role)) : $db_role;
    if (!empty($requested_role) && $requested_role !== $db_role) {
        // Provide a clear error so the user knows to use the correct login tab or contact admin
        redirect('login.html?error=role_mismatch');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    // store the identifier (username or email) used to login
    $_SESSION['username'] = $identifier;
    // store normalized role and display name
    $_SESSION['role'] = $db_role;
    $_SESSION['name'] = $first_name . ' ' . $last_name;

    // set friendly flash messages for dashboards
    if ($db_role === 'doctor') {
        set_flash('success', 'Successfully logged in as doctor.');
    } elseif ($db_role === 'admin') {
        set_flash('success', 'Successfully logged in as administrator.');
    } else {
        set_flash('success', 'Successfully logged in.');
    }

    $stmt->close();

    // Ensure session is written before redirecting
    session_write_close();

    // Redirect to dashboard by role (prefer .php dashboard if available)
    // Redirect to dashboard by role (prefer .php dashboard if available)
    if ($db_role === 'admin') {
        redirect('admin-dashboard.php');
    } elseif ($db_role === 'doctor') {
        if (file_exists(__DIR__ . '/doctor-dashboard.php')) {
            redirect('doctor-dashboard.php');
        }
        // Fallback to HTML dashboard if PHP missing
        redirect('doctor-dashboard.html');
    } else {
        if (file_exists(__DIR__ . '/patient-dashboard.php')) {
            redirect('patient-dashboard.php');
        }
        redirect('patient-dashboard.html');
    }

} catch (Exception $e) {
    dbg_login('Exception during login: ' . $e->getMessage());
    // Don't expose internals to the user; redirect with a generic server error
    redirect('login.html?error=server');
}
