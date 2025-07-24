<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    die("Invalid CSRF token");
}

// Destroy session completely
$_SESSION = [];
session_destroy();

// Clear session cookie
setcookie(
    session_name(),
    '',
    [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]
);

// Redirect to home with cache prevention
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Location: " . BASE_URL);
exit();