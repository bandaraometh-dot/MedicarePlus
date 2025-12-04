<?php
include 'dbconnect.php';

$sql = "SELECT u.first_name, u.last_name, u.email, u.phone, 
               d.specialization, d.fees, d.availability 
        FROM users u 
        JOIN doctors d ON u.id = d.user_id 
        WHERE u.role = 'doctor'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Doctors</title>
</head>
<body>
    <h2>Registered Doctors</h2>
    <table border="1">
        <tr>
            <th>Name</th><th>Email</th><th>Phone</th>
            <th>Specialization</th><th>Fees</th><th>Availability</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td><?php echo $row['phone']; ?></td>
            <td><?php echo $row['specialization']; ?></td>
            <td>$<?php echo $row['fees']; ?></td>
            <td><?php echo $row['availability']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>