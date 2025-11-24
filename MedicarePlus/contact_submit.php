<?php
require_once 'dbconnect.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contact.html');
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($name) || empty($email) || empty($message)) {
    redirect('contact.html?error=missing');
}

$stmt = $conn->prepare('INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)');
$stmt->bind_param('sss', $name, $email, $message);
$ok = $stmt->execute();
$stmt->close();

if ($ok) redirect('contact.html?sent=1');
else redirect('contact.html?error=server');
