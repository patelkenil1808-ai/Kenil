<?php
/**
 * auth.php
 * Session management, secure login logic, password hashing, and Role-Based Access Control (RBAC).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Checks if the user is logged in.
 * Returns true if logged in, false otherwise.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Checks if the logged in user has the "super_admin" role.
 */
function isSuperAdmin(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'super_admin';
}

/**
 * Checks if the logged in user has the "staff" role.
 */
function isStaff(): bool {
    return isLoggedIn() && $_SESSION['user_role'] === 'staff';
}

/**
 * Middleware: Requires the user to be logged in. 
 * Redirects to index.php with error or returns JSON error depending on request type.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjaxRequest()) {
            sendJsonError('Unauthorized access. Please login.', 401);
        } else {
            $_SESSION['auth_error'] = 'Please log in to access the system.';
            header('Location: index.php');
            exit();
        }
    }
}

/**
 * Middleware: Requires the user to have super_admin role.
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        if (isAjaxRequest()) {
            sendJsonError('Forbidden: This action requires Super Admin permissions.', 403);
        } else {
            $_SESSION['auth_error'] = 'Access denied. You do not have permission to view that page.';
            header('Location: index.php');
            exit();
        }
    }
}

/**
 * Helper to log operational actions inside MySQL for auditing.
 */
function logActivity(PDO $pdo, string $description) {
    if (!isLoggedIn()) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action_description) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $description]);
    } catch (Exception $e) {
        // Silently handle logging errors so they don't block critical customer operations
    }
}

/**
 * Authenticate login request.
 */
function loginUser(PDO $pdo, string $username, string $password): array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Store session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        // Audit action
        logActivity($pdo, "User '{$user['username']}' logged in successfully.");
        return ['success' => true, 'role' => $user['role'], 'username' => $user['username']];
    }
    
    return ['success' => false, 'error' => 'Invalid username or password.'];
}

/**
 * Logout utility.
 */
function logoutUser(PDO $pdo) {
    if (isLoggedIn()) {
        logActivity($pdo, "User '{$_SESSION['user_username']}' logged out.");
    }
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
 * Helper to detect AJAX fetches.
 */
function isAjaxRequest(): bool {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json') !== false)
        || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
}

/**
 * Utility function to send JSON error responses.
 */
function sendJsonError(string $message, int $statusCode = 400) {
    header('HTTP/1.1 ' . $statusCode . ' ' . getHttpStatusMessage($statusCode));
    header('Content-Type: application/json');
    echo json_encode(['error' => $message]);
    exit();
}

function getHttpStatusMessage(int $code): string {
    $status = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error'
    ];
    return $status[$code] ?? 'Error';
}
