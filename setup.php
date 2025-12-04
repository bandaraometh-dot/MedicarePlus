<?php
// setup.php - Run this to initialize the database

// Load config
if (file_exists('dbconfig.php')) {
    require_once 'dbconfig.php';
} else {
    die("dbconfig.php not found. Please copy dbconfig.example.php to dbconfig.php and configure it.");
}

// Connect without DB first to create it
$conn = new mysqli($db_host, $db_user, $db_pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . ". Please check your dbconfig.php settings.");
}

// Create DB
$sql = "CREATE DATABASE IF NOT EXISTS $db_name DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database '$db_name' created or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db($db_name);

// Read SQL file
$sqlFile = file_get_contents('create_db.sql');
// Remove comments to avoid issues with splitting
$sqlFile = preg_replace('/--.*$/m', '', $sqlFile);
// Split by semicolon
$queries = explode(';', $sqlFile);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($conn->query($query) === TRUE) {
            // echo "Query executed successfully.<br>";
        } else {
            // Ignore "Database changed" or empty queries
            echo "Error executing query: " . $conn->error . "<br>Query: " . htmlspecialchars($query) . "<br>";
        }
    }
}
echo "Tables initialized.<br>";

// Check if admin exists
$result = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
if ($result && $result->num_rows == 0) {
    // Create default admin
    $pass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $fn = 'System'; $ln = 'Admin'; $un = 'admin'; $em = 'admin@medicareplus.com'; $role = 'admin';
    $stmt->bind_param("ssssss", $fn, $ln, $un, $em, $pass, $role);
    if ($stmt->execute()) {
        echo "Default admin user created (User: admin, Pass: admin123).<br>";
    } else {
        echo "Error creating admin: " . $stmt->error . "<br>";
    }
} else {
    echo "Admin user already exists.<br>";
}

echo "Setup complete. <a href='login.html'>Go to Login</a>";
?>
