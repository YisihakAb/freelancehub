<?php
require_once __DIR__ . '/config.php';

/**
 * Authenticate user with email and password
 */
function authenticate_user(string $email, string $password): bool {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 1. Check if user exists with hashed password
    if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
        _set_user_session($user);
        return true;
    }
    
    // 2. Legacy support: Check plain password (remove after migration)
    if ($user && isset($user['password']) && $user['password'] === $password) {
        // Auto-upgrade to hashed password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash = ?, password = '' WHERE id = ?")
           ->execute([$hashed, $user['id']]);
        
        _set_user_session($user);
        return true;
    }
    
    return false;
}

/**
 * Set user session after successful authentication
 */
function _set_user_session(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['avatar'] = $user['avatar'] ?? 'default.jpg';
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Require authenticated user
 */
function require_auth(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
}

/**
 * Require specific user role
 */
function require_role(string $role): void {
    require_auth();
    if ($_SESSION['user_role'] !== $role) {
        header("HTTP/1.1 403 Forbidden");
        die("Access denied. Required role: " . htmlspecialchars($role));
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Generate CSRF token
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}