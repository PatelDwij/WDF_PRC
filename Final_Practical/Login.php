<?php
/**
 * StudentHub Portal - Secure Authentication
 * Practical 10: Login, Logout, Sessions, Inactivity Timeout, and Role-Based Access Control
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit.php';

// If already authenticated, redirect to appropriate role dashboard
if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'student';
    if ($role === 'admin') {
        header('Location: Admin.php');
    } elseif ($role === 'faculty') {
        header('Location: Faculty_dashboard.php');
    } else {
        header('Location: Student_dashboard.php');
    }
    exit;
}

$errorMessage   = "";
$infoMessage    = "";
$loginIdentifier = "";
$redirectUrl    = $_GET['redirect'] ?? '';

// Handle redirect query alerts
$msgCode = $_GET['msg'] ?? '';
if ($msgCode === 'logged_out') {
    $infoMessage = "You have been signed out successfully.";
} elseif ($msgCode === 'timeout') {
    $infoMessage = "Your session expired after 15 minutes of inactivity. Please sign in again.";
} elseif ($msgCode === 'forbidden') {
    $errorMessage = "Access restricted: You do not have permission to access that area.";
} elseif ($msgCode === 'login_required') {
    $infoMessage = "Please sign in to access this feature.";
}

// Process POST authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIdentifier = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $rememberMe      = isset($_POST['remember_me']) && $_POST['remember_me'] == '1';

    if ($loginIdentifier === "" || $password === "") {
        $errorMessage = "Please enter both your identifier and password.";
    } else {
        try {
            $pdo = getDB();

            // Find user by email or student ID
            $stmt = $pdo->prepare(
                "SELECT student_id, full_name, email, mobile, gender, year, semester, course, role, password, status
                 FROM students
                 WHERE email = :email OR student_id = :sid
                 LIMIT 1"
            );
            $stmt->execute([
                ':email' => $loginIdentifier,
                ':sid'   => $loginIdentifier
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $errorMessage = "This account is inactive. Please contact the university administrator.";
                    log_audit($user['student_id'], $user['role'], 'LOGIN_FAILED_INACTIVE', 'students', $user['student_id'], 'Attempted sign in to deactivated account');
                } else {
                    // Successful authentication: initialize session with regenerated ID
                    login_user($user, $rememberMe);

                    // Redirect safely
                    if (!empty($redirectUrl) && !preg_match('/^(http:\/\/|https:\/\/|\/\/)/i', $redirectUrl)) {
                        header('Location: ' . $redirectUrl);
                    } else {
                        $userRole = strtolower($user['role']);
                        if ($userRole === 'admin') {
                            header('Location: Admin.php');
                        } elseif ($userRole === 'faculty') {
                            header('Location: Faculty_dashboard.php');
                        } else {
                            header('Location: Student_dashboard.php');
                        }
                    }
                    exit;
                }
            } else {
                $errorMessage = "Invalid email/ID or password. Please try again.";
                log_audit('GUEST', 'guest', 'LOGIN_FAILED', 'students', null, [
                    'identifier' => $loginIdentifier
                ]);
            }
        } catch (PDOException $e) {
            error_log('[StudentHub Login DB Error] ' . $e->getMessage());
            $errorMessage = "Authentication service temporarily unavailable. Please try again shortly.";
        }
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="login-section">
        <div class="auth-card">
            <h2>Sign In to StudentHub</h2>
            <p class="auth-subtitle">Access your student portal, courses, timetable, attendance, and campus events.</p>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert-box alert-error" role="alert">
                    <span>⚠</span>
                    <div><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($infoMessage)): ?>
                <div class="alert-box alert-info" role="alert">
                    <span>ℹ</span>
                    <div><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form action="Login.php<?= !empty($redirectUrl) ? '?redirect=' . urlencode($redirectUrl) : '' ?>" method="POST" novalidate>
                <div class="form-group">
                    <label for="email">University Email or Student ID</label>
                    <input type="text" id="email" name="email" placeholder="e.g. 25CE077 or name@studenthub.edu" value="<?= htmlspecialchars($loginIdentifier, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password">Password</label>
                    </div>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-row-between">
                    <label class="checkbox-container">
                        <input type="checkbox" id="remember_me" name="remember_me" value="1">
                        <span>Stay signed in for 30 days</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </div>

                <div class="demo-credentials-box">
                    <strong>Demo Portals:</strong>
                    <ul>
                        <li><strong>Student:</strong> <code>25CE077</code> &bull; Pass: <code>Password@123</code></li>
                        <li><strong>Faculty:</strong> <code>rajesh.sharma@charusat.edu.in</code> &bull; Pass: <code>Password@123</code></li>
                        <li><strong>Admin:</strong> <code>admin@studenthub.edu</code> &bull; Pass: <code>Password@123</code></li>
                    </ul>
                </div>

                <div class="auth-footer-links">
                    Don't have an account? <a href="Register.php">Create Account</a> &bull;
                    <a href="Contact.php">Need Help?</a>
                </div>
            </form>
        </div>
    </section>
</main>

<style>
.login-section {
    max-width: 480px;
    margin: 30px auto;
    text-align: left;
}
.auth-card {
    background: #ffffff;
    padding: 35px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
}
.auth-card h2 {
    color: #1e3a8a;
    margin-top: 0;
    margin-bottom: 6px;
    font-size: 24px;
}
.auth-subtitle {
    color: #64748b;
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 14px;
}
.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 18px;
}
.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.label-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.form-group input {
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    box-sizing: border-box;
    width: 100%;
}
.form-group input:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
}
.form-row-between {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    color: #475569;
}
.checkbox-container input {
    width: 16px;
    height: 16px;
    accent-color: #2563eb;
}
.btn-block {
    width: 100%;
    padding: 12px;
}
.demo-credentials-box {
    margin-top: 25px;
    padding: 12px 16px;
    background: #f1f5f9;
    border-radius: 6px;
    font-size: 12px;
    color: #334155;
    border-left: 3px solid #30bfe3;
}
.demo-credentials-box strong {
    color: #0f172a;
}
.demo-credentials-box ul {
    margin: 6px 0 0 0;
    padding-left: 18px;
}
.demo-credentials-box li {
    margin-bottom: 4px;
}
.demo-credentials-box code {
    background: #e2e8f0;
    padding: 1px 5px;
    border-radius: 3px;
    color: #0f172a;
}
.alert-box {
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-error {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}
.alert-info {
    background-color: #f0f9ff;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
    text-align: center;
}
.btn-primary {
    background-color: #2563eb;
    color: #ffffff;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.auth-footer-links {
    margin-top: 20px;
    text-align: center;
    font-size: 13px;
    color: #64748b;
}
.auth-footer-links a {
    color: #2563eb;
    font-weight: 600;
    text-decoration: none;
}
.auth-footer-links a:hover {
    text-decoration: underline;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
