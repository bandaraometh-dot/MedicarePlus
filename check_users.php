<?php
require_once 'dbconnect.php';

echo "<h1>Users List</h1>";
echo "<table border='1'><tr><th>ID</th><th>Username</th><th>Role</th><th>Password Hash</th></tr>";

$result = $conn->query("SELECT id, username, role, password FROM users");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
    echo "<td>" . htmlspecialchars($row['role']) . "</td>";
    echo "<td>" . substr($row['password'], 0, 10) . "...</td>";
    echo "</tr>";
}
echo "</table>";
?>