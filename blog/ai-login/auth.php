<?php
/**
 * Authentication and security helper for Blog Admin
 * Implements password_hash(), password_verify(), CSRF tokens, session hardening.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Session security parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

require_once dirname(__DIR__) . '/db.php';

/**
 * Check if the user is currently authenticated as an admin
 */
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_role']) && $_SESSION['admin_user_role'] === 'admin';
}

/**
 * Enforce admin login on private routes. Redirects to /blog/ai-login if unauthenticated.
 */
function require_admin_auth(): array {
    if (!is_admin_logged_in()) {
        header('Location: /blog/ai-login');
        exit;
    }
    return [
        'id' => $_SESSION['admin_user_id'],
        'username' => $_SESSION['admin_username'],
        'display_name' => $_SESSION['admin_display_name'],
        'email' => $_SESSION['admin_email'],
        'role' => $_SESSION['admin_user_role']
    ];
}

/**
 * Authenticate admin with username/email and password
 */
function login_admin(string $usernameOrEmail, string $password): bool {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, display_name, role FROM users WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1");
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_display_name'] = $user['display_name'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_user_role'] = $user['role'];
        $_SESSION['admin_logged_in_at'] = time();

        return true;
    }

    return false;
}

/**
 * Terminate session securely
 */
function logout_admin(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * CSRF token generation
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token verification
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper to safely escape output for HTML
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
