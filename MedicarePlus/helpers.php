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
