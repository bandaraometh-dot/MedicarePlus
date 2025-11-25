<?php
// dbconnect.php
// This file attempts to read DB credentials from a local `dbconfig.php` (recommended)
// or from environment variables (DB_HOST, DB_USER, DB_PASS, DB_NAME). If neither
// is present it falls back to sensible defaults for a typical XAMPP setup.

// Load an optional local config file (create `dbconfig.php` next to this file)
$localConfig = __DIR__ . '/dbconfig.php';
if (file_exists($localConfig)) {
    require $localConfig; // expects $db_host, $db_user, $db_pass, $db_name
}

$servername = isset($db_host) ? $db_host : (getenv('DB_HOST') ?: 'localhost');
$username   = isset($db_user) ? $db_user : (getenv('DB_USER') ?: 'root');
$password   = isset($db_pass) ? $db_pass : (getenv('DB_PASS') ?: '12345678');
$dbname     = isset($db_name) ? $db_name : (getenv('DB_NAME') ?: 'medicareplus');

// Use mysqli exceptions so we can display a helpful message without exposing secrets
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    $usingPassword = ($password !== '') ? 'YES' : 'NO';
    $msg = "Database connection failed: (" . $e->getCode() . ") " . $e->getMessage();
    $msg .= " — attempted as user '" . $username . "'@'" . $servername . "' (using password: " . $usingPassword . ").";
    $msg .= " Please create a local 'dbconfig.php' or set DB_HOST/DB_USER/DB_PASS/DB_NAME environment variables with correct credentials.";
    // Do not include the password in output. Show guidance only.
    exit($msg);
}
// Connected
?>