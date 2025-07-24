<?php
// Error Reporting for Development
define('ENVIRONMENT', 'development');
define('BASE_URL', 'http://127.0.0.1/freelancehub/');
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Database Configuration
$db_config = [
    'host' => 'localhost',
    'name' => 'freelancehub_v2', // Changed to match your schema.sql
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
];

// Initialize PDO
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset={$db_config['charset']}",
        $db_config['user'],
        $db_config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Don't show database errors to users in production
    if (ENVIRONMENT === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        error_log("DB Connection Failed: " . $e->getMessage());
        die("System temporarily unavailable. Please try again later.");
    }
}

// App Constants
define('APP_NAME', 'FreelanceHub');
define('BASE_PATH', '/freelancehub/');
define('BASE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_PATH);

// Initialize Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>