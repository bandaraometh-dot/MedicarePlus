<?php
include 'dbconnect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $specialization = $_POST['specialization'];
    $fees = $_POST['fees'];
    $availability = $_POST['availability'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into users table
        $sql1 = "INSERT INTO users (first_name, last_name, username, email, password, phone, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("sssssss", $first_name, $last_name, $username, $email, $password, $phone, $role);
        $stmt1->execute();
        
        // Get the last inserted user ID
        $user_id = $conn->insert_id;
        
        // Insert into doctors table
        $sql2 = "INSERT INTO doctors (user_id, specialization, fees, availability) 
                VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("isds", $user_id, $specialization, $fees, $availability);
        $stmt2->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to success page
        header("Location: doctor_register.php?success=1");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Check for duplicate entry
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            if (strpos($e->getMessage(), 'username') !== false) {
                header("Location: doctor_register.php?error=username_exists");
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                header("Location: doctor_register.php?error=email_exists");
            } else {
                header("Location: doctor_register.php?error=duplicate");
            }
        } else {
            header("Location: doctor_register.php?error=1");
        }
        exit();
    }
    
    $stmt1->close();
    $stmt2->close();
    $conn->close();
} else {
    // If someone tries to access directly
    header("Location: doctor_register.php");
    exit();
}
?>