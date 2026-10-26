<?php
/**
 * StudentHub Portal - Authentication & RBAC Middleware
 * Practical 10: Session Security, Inactivity Timeout, and Role-Based Access Control
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/audit.php';

// Safe Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0, // Until browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Handle session inactivity timeout (Practical 10 Requirement: 15-minute inactivity).
 */
function check_session_timeout(): void {
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            $userId = $_SESSION['user_id'] ?? 'UNKNOWN';
            $role   = $_SESSION['role'] ?? 'guest';

            log_audit($userId, $role, 'SESSION_TIMEOUT', 'session', $userId, 'Session expired due to 15 minutes of inactivity');

            // Destroy session
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();

            // Redirect to Login with user-friendly timeout message
            header('Location: Login.php?msg=timeout');
            exit;
        }
        // Update activity timestamp
        $_SESSION['last_activity'] = time();
    }
}

// Check timeout on every include
check_session_timeout();

/**
 * Check if a user is currently authenticated.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Retrieve active user session profile data.
 *
 * @return array
 */
function get_logged_user(): array {
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'name'     => $_SESSION['user_name'] ?? 'Guest',
        'email'    => $_SESSION['user_email'] ?? '',
        'role'     => $_SESSION['role'] ?? 'guest',
        'course'   => $_SESSION['course'] ?? '',
        'semester' => $_SESSION['semester'] ?? ''
    ];
}

/**
 * Check if the active user possesses a specific role or one of given roles.
 *
 * @param string|array $roles Single role string or array of permitted roles
 * @return bool
 */
function has_role($roles): bool {
    if (!is_logged_in()) {
        return false;
    }

    $userRole = strtolower($_SESSION['role'] ?? '');
    if (is_array($roles)) {
        return in_array($userRole, array_map('strtolower', $roles), true);
    }

    return $userRole === strtolower($roles);
}

/**
 * Server-side route guard requiring authentication.
 * Redirects unauthenticated visitors to Login.php.
 */
function require_login(): void {
    if (!is_logged_in()) {
        $returnUrl = $_SERVER['REQUEST_URI'] ?? 'index.html';
        header('Location: Login.php?redirect=' . urlencode($returnUrl));
        exit;
    }
}

/**
 * Server-side route guard enforcing Role-Based Access Control (RBAC).
 *
 * @param string|array $allowedRoles
 */
function require_role($allowedRoles): void {
    require_login();

    if (!has_role($allowedRoles)) {
        $userId   = $_SESSION['user_id'] ?? 'UNKNOWN';
        $userRole = $_SESSION['role'] ?? 'guest';

        log_audit($userId, $userRole, 'ACCESS_DENIED', 'route', null, [
            'attempted_url' => $_SERVER['REQUEST_URI'] ?? '',
            'required_roles' => (array)$allowedRoles
        ]);

        // Redirect to appropriate role dashboard
        if ($userRole === 'admin') {
            header('Location: Admin.php?msg=forbidden');
        } elseif ($userRole === 'faculty') {
            header('Location: Faculty_dashboard.php?msg=forbidden');
        } else {
            header('Location: Student_dashboard.php?msg=forbidden');
        }
        exit;
    }
}

/**
 * Generate or retrieve CSRF token.
 *
 * @return string
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST or headers.
 *
 * @param string|null $token Token to check
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set up user session upon verified credentials.
 * Regenerates session ID to prevent session fixation attacks.
 *
 * @param array $user User database record
 * @param bool $rememberMe Whether remember-me was selected
 */
function login_user(array $user, bool $rememberMe = false): void {
    // Session Fixation Protection: regenerate session ID
    session_regenerate_id(true);

    $_SESSION['user_id']       = $user['student_id'];
    $_SESSION['user_name']     = $user['full_name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['role']          = strtolower($user['role']);
    $_SESSION['course']        = $user['course'] ?? '';
    $_SESSION['semester']      = $user['semester'] ?? '';
    $_SESSION['login_time']    = time();
    $_SESSION['last_activity'] = time();

    // Generate fresh CSRF token
    generate_csrf_token();

    // Update last_login in database
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE students SET last_login = NOW() WHERE student_id = :id");
        $stmt->execute([':id' => $user['student_id']]);
    } catch (Exception $e) {
        error_log('[StudentHub Login] ' . $e->getMessage());
    }

    // Handle Remember-Me Cookie (Practical 10 Requirement)
    if ($rememberMe) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $cookieValue = $user['student_id'] . ':' . $token;

        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE students SET remember_token_hash = :hash WHERE student_id = :id");
            $stmt->execute([
                ':hash' => $tokenHash,
                ':id'   => $user['student_id']
            ]);

            setcookie(
                'studenthub_remember',
                $cookieValue,
                time() + (86400 * REMEMBER_ME_DAYS),
                '/',
                '',
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                true // HTTPOnly
            );
        } catch (Exception $e) {
            error_log('[StudentHub RememberMe] ' . $e->getMessage());
        }
    }

    log_audit($user['student_id'], $user['role'], 'USER_LOGIN', 'students', $user['student_id'], 'User signed in successfully');
}

/**
 * Securely terminate user session and clear remember-me cookies.
 */
function logout_user(): void {
    $userId = $_SESSION['user_id'] ?? null;
    $role   = $_SESSION['role'] ?? 'guest';

    if ($userId) {
        log_audit($userId, $role, 'USER_LOGOUT', 'students', $userId, 'User signed out');

        // Clear remember token from database
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE students SET remember_token_hash = NULL WHERE student_id = :id");
            $stmt->execute([':id' => $userId]);
        } catch (Exception $e) {
            error_log('[StudentHub Logout] ' . $e->getMessage());
        }
    }

    // Clear remember-me cookie
    if (isset($_COOKIE['studenthub_remember'])) {
        setcookie('studenthub_remember', '', time() - 3600, '/');
    }

    // Destroy session
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
