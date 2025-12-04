<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'dbconnect.php';

$dept = isset($_GET['department']) ? trim($_GET['department']) : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$results = [];
try {
    if ($dept !== '') {
        $like = '%' . $dept . '%';
        $stmt = $conn->prepare("SELECT d.id AS doctor_id, u.first_name, u.last_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.specialization LIKE ? OR d.specialization LIKE ? ORDER BY u.first_name ASC LIMIT 200");
        // We bind the same like twice for simple matching; keep for compatibility
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $results[] = $r;
        $stmt->close();
    } elseif ($q !== '') {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare("SELECT d.id AS doctor_id, u.first_name, u.last_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR d.specialization LIKE ? ORDER BY u.first_name ASC LIMIT 200");
        $stmt->bind_param('sss', $like, $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $results[] = $r;
        $stmt->close();
    } else {
        $res = $conn->query("SELECT d.id AS doctor_id, u.first_name, u.last_name, d.specialization FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.first_name ASC LIMIT 200");
        if ($res) while ($r = $res->fetch_assoc()) $results[] = $r;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error']);
    exit;
}

echo json_encode($results);
