<?php
require_once 'dbconnect.php';

echo "<h1>Doctors List</h1>";
echo "<table border='1'><tr><th>ID</th><th>User ID</th><th>Specialization</th></tr>";

$result = $conn->query("SELECT id, user_id, specialization FROM doctors");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['specialization']) . "</td>";
    echo "</tr>";
}
echo "</table>";
?>