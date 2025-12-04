<?php
require_once 'dbconnect.php';

echo "Starting data fix...<br>";

// 1. Fix passwords
$new_pass = password_hash('password123', PASSWORD_DEFAULT);
$users_to_fix = ['john.doe', 'puka.sududa'];

foreach ($users_to_fix as $username) {
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $new_pass, $username);
    if ($stmt->execute()) {
        echo "Updated password for user '$username'.<br>";
    } else {
        echo "Error updating password for '$username': " . $stmt->error . "<br>";
    }
    $stmt->close();
}

// 2. Fix missing doctor records
$sql = "SELECT id, username FROM users WHERE role = 'doctor'";
$result = $conn->query($sql);

while ($user = $result->fetch_assoc()) {
    $user_id = $user['id'];
    $username = $user['username'];
    
    // Check if exists in doctors
    $check = $conn->query("SELECT id FROM doctors WHERE user_id = $user_id");
    if ($check->num_rows == 0) {
        echo "User '$username' (ID: $user_id) is a doctor but has no doctor record. Creating one...<br>";
        
        // Insert default doctor record
        $stmt = $conn->prepare("INSERT INTO doctors (user_id, specialization, fees) VALUES (?, 'General Practice', 50.00)");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            echo "Created doctor record for '$username'.<br>";
        } else {
            echo "Error creating doctor record for '$username': " . $stmt->error . "<br>";
        }
        $stmt->close();
    }
}

echo "Data fix complete.<br>";
?>