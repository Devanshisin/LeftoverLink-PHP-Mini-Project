<?php
// auth.php – Authentication helpers and session management

require_once __DIR__ . '/config.php';

session_start();

/**
 * Check if a user is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged‑in user data as associative array.
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

/**
 * Enforce that the user is logged in, otherwise redirect to login.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Enforce that the logged‑in user has one of the required roles.
 */
function requireRole(array $roles): void {
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

/**
 * Generate a CSRF token and store it in session.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from a POST request.
 */
function verifyCsrfToken(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Helper to safely output text.
 */
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
