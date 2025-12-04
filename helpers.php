<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function esc($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Flash messaging helpers (simple session-backed flash)
function set_flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}
