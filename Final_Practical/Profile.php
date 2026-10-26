<?php
/**
 * StudentHub Portal - User Profile Management
 * Practical 10 & 11: Profile Inspection & Secure Self-Update
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit.php';

require_login();

$userId      = $_SESSION['user_id'];
$message     = "";
$messageType = "";
$errors      = [];

$pdo = getDB();

// Fetch current student profile
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim($_POST['full_name'] ?? '');
    $mobile          = trim($_POST['mobile'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if ($fullName === "") {
        $errors['full_name'] = "Full name is required.";
    } elseif (mb_strlen($fullName) < 3) {
        $errors['full_name'] = "Full name must be at least 3 characters.";
    }

    if ($mobile === "") {
        $errors['mobile'] = "Mobile number is required.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        $errors['mobile'] = "Enter a valid 10-digit mobile number.";
    }

    $passwordUpdate = false;
    if (!empty($newPassword) || !empty($currentPassword)) {
        if (empty($currentPassword)) {
            $errors['current_password'] = "Please enter your current password to change it.";
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors['current_password'] = "Current password does not match.";
        }

        if (strlen($newPassword) < 8) {
            $errors['new_password'] = "New password must be at least 8 characters.";
        }

        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = "New passwords do not match.";
        }

        if (empty($errors)) {
            $passwordUpdate = true;
        }
    }

    if (empty($errors)) {
        try {
            if ($passwordUpdate) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare(
                    "UPDATE students SET full_name = :name, mobile = :mobile, password = :pass, updated_at = NOW()
                     WHERE student_id = :id"
                );
                $updateStmt->execute([
                    ':name'   => $fullName,
                    ':mobile' => $mobile,
                    ':pass'   => $hash,
                    ':id'     => $userId
                ]);
            } else {
                $updateStmt = $pdo->prepare(
                    "UPDATE students SET full_name = :name, mobile = :mobile, updated_at = NOW()
                     WHERE student_id = :id"
                );
                $updateStmt->execute([
                    ':name'   => $fullName,
                    ':mobile' => $mobile,
                    ':id'     => $userId
                ]);
            }

            // Update session cache
            $_SESSION['user_name'] = $fullName;

            log_audit($userId, $user['role'], 'PROFILE_UPDATED', 'students', $userId, [
                'password_changed' => $passwordUpdate
            ]);

            $message     = "Your changes have been saved.";
            $messageType = "success";

            // Refresh user record
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

        } catch (PDOException $e) {
            error_log('[StudentHub Profile Update Error] ' . $e->getMessage());
            $message     = "Unable to save profile changes. Please try again.";
            $messageType = "error";
        }
    } else {
        $message     = "Please check the highlighted fields and try again.";
        $messageType = "error";
    }
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="profile-container">
        <div class="profile-card">
            <div class="profile-header-banner">
                <div class="profile-avatar-circle">
                    <img src="Pics/IMG-20250429-WA0018.jpg" alt="Profile Picture" onerror="this.onerror=null; this.src='avatar.svg';">
                </div>
                <div class="profile-header-meta">
                    <h2><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="profile-role-line">
                        <span class="role-badge role-<?= strtolower($user['role']) ?>"><?= ucfirst(htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8')) ?></span> &bull;
                        <span><?= htmlspecialchars($user['student_id'], ENT_QUOTES, 'UTF-8') ?></span> &bull;
                        <span><?= htmlspecialchars($user['course'], ENT_QUOTES, 'UTF-8') ?></span>
                    </p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-box alert-<?= $messageType ?>" role="alert">
                    <span><?= ($messageType === 'success') ? '✓' : '⚠' ?></span>
                    <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <form action="Profile.php" method="POST" class="profile-form">
                <div class="section-title">
                    <h3>Academic &amp; Personal Information</h3>
                </div>

                <div class="profile-grid">
                    <div class="form-group">
                        <label>Student / Faculty ID</label>
                        <input type="text" value="<?= htmlspecialchars($user['student_id'], ENT_QUOTES, 'UTF-8') ?>" disabled class="input-disabled">
                        <span class="field-hint">Institutional ID cannot be changed.</span>
                    </div>

                    <div class="form-group">
                        <label>University Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>" disabled class="input-disabled">
                        <span class="field-hint">Email address is managed by the university registrar.</span>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <span class="field-error"><?= $errors['full_name'] ?? '' ?></span>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile Number <span class="required">*</span></label>
                        <input type="tel" id="mobile" name="mobile" maxlength="10" value="<?= htmlspecialchars($user['mobile'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <span class="field-error"><?= $errors['mobile'] ?? '' ?></span>
                    </div>

                    <div class="form-group">
                        <label>Department / Course</label>
                        <input type="text" value="<?= htmlspecialchars($user['course'], ENT_QUOTES, 'UTF-8') ?>" disabled class="input-disabled">
                    </div>

                    <div class="form-group">
                        <label>Academic Year &amp; Semester</label>
                        <input type="text" value="<?= htmlspecialchars($user['year'], ENT_QUOTES, 'UTF-8') ?> &bull; Semester <?= htmlspecialchars((string)$user['semester'], ENT_QUOTES, 'UTF-8') ?>" disabled class="input-disabled">
                    </div>
                </div>

                <div class="section-title" style="margin-top: 25px;">
                    <h3>Change Password</h3>
                    <p style="font-size: 13px; color: #64748b; margin: 4px 0 15px 0;">Leave password fields empty if you do not wish to change your password.</p>
                </div>

                <div class="profile-grid">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                        <span class="field-error"><?= $errors['current_password'] ?? '' ?></span>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Minimum 8 characters">
                        <span class="field-error"><?= $errors['new_password'] ?? '' ?></span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                        <span class="field-error"><?= $errors['confirm_password'] ?? '' ?></span>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 25px; text-align: right;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </section>
</main>

<style>
.profile-container {
    max-width: 860px;
    margin: 25px auto;
    text-align: left;
}
.profile-card {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    padding: 30px;
}
.profile-header-banner {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 25px;
    border-bottom: 2px solid #55b7cf;
    margin-bottom: 25px;
}
.profile-avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #30bfe3;
    background: #f1f5f9;
    flex-shrink: 0;
}
.profile-avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-header-meta h2 {
    margin: 0 0 6px 0;
    color: #1e3a8a;
}
.profile-role-line {
    margin: 0;
    font-size: 14px;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.role-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
}
.role-student { background: #dbeafe; color: #1e40af; }
.role-faculty { background: #fef3c7; color: #92400e; }
.role-admin { background: #fee2e2; color: #991b1b; }
.section-title h3 {
    margin: 0;
    color: #1e293b;
    font-size: 17px;
}
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 24px;
}
@media (max-width: 640px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.required { color: #dc2626; }
.form-group input {
    padding: 10px 14px;
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
.input-disabled {
    background-color: #f1f5f9 !important;
    color: #64748b !important;
    cursor: not-allowed;
}
.field-hint {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
}
.field-error {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    min-height: 16px;
}
.alert-box {
    padding: 12px 18px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.btn {
    display: inline-block;
    padding: 11px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    text-decoration: none;
}
.btn-primary { background: #2563eb; color: #ffffff; }
.btn-primary:hover { background: #1d4ed8; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
