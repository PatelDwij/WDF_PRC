<?php
/**
 * StudentHub Portal - Contact Support & Inquiry Form
 * Practical 7: Form Processing with Server-Side Validation & File/Database Storage
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit.php';

$message     = "";
$messageType = "";
$errors      = [];
$isPost      = ($_SERVER["REQUEST_METHOD"] === "POST");
$submitted   = false;

$name    = "";
$email   = "";
$subject = "";
$body    = "";

if ($isPost) {
    $name    = trim($_POST["name"] ?? "");
    $email   = strtolower(trim($_POST["email"] ?? ""));
    $subject = trim($_POST["subject"] ?? "");
    $body    = trim($_POST["message"] ?? "");

    // Validation
    if ($name === "") {
        $errors["name"] = "Please enter your name.";
    } elseif (mb_strlen($name) < 2) {
        $errors["name"] = "Name must be at least 2 characters.";
    }

    if ($email === "") {
        $errors["email"] = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Please enter a valid email address.";
    }

    if ($subject === "") {
        $errors["subject"] = "Please enter an inquiry subject.";
    }

    if ($body === "") {
        $errors["message"] = "Please describe your question or issue.";
    } elseif (mb_strlen($body) < 10) {
        $errors["message"] = "Please provide more details (minimum 10 characters).";
    }

    if (empty($errors)) {
        $submittedAt = date("Y-m-d H:i:s");

        // 1. Insert into database
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                "INSERT INTO contact_messages (name, email, subject, message, status, created_at)
                 VALUES (:name, :email, :subject, :message, 'unread', :created_at)"
            );
            $stmt->execute([
                ':name'       => $name,
                ':email'      => $email,
                ':subject'    => $subject,
                ':message'    => $body,
                ':created_at' => $submittedAt
            ]);
        } catch (Exception $e) {
            error_log('[StudentHub Contact DB Error] ' . $e->getMessage());
        }

        // 2. Safe file storage (Practical 7)
        $messagesFile = DATA_DIR . DIRECTORY_SEPARATOR . 'contact_messages.json';
        $inquiryRecord = [
            'id'           => 'MSG-' . date('Ymd') . '-' . substr(uniqid(), -4),
            'name'         => $name,
            'email'        => $email,
            'subject'      => $subject,
            'message'      => $body,
            'submitted_at' => $submittedAt
        ];

        $fp = @fopen($messagesFile, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $size = filesize($messagesFile);
            $existing = [];
            if ($size > 0) {
                rewind($fp);
                $raw = fread($fp, $size);
                if ($raw) {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) $existing = $decoded;
                }
            }
            $existing[] = $inquiryRecord;
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        log_audit(
            is_logged_in() ? get_logged_user()['id'] : 'GUEST',
            is_logged_in() ? get_logged_user()['role'] : 'guest',
            'CONTACT_INQUIRY',
            'contact_messages',
            null,
            ['subject' => $subject, 'email' => $email]
        );

        $message     = "Your message has been sent. We will get back to you shortly.";
        $messageType = "success";
        $submitted   = true;
        $name = $email = $subject = $body = "";
    } else {
        $message     = "Please check the highlighted fields and try again.";
        $messageType = "error";
    }
}

$pageTitle = 'Contact Helpdesk';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="contact-section">
        <div class="contact-grid">
            <div class="contact-info-card">
                <h2>Contact Helpdesk</h2>
                <p>Have questions about admissions, courses, campus events, or technical issues with your portal account? Reach out to the university support team.</p>

                <div class="info-block">
                    <strong>Email:</strong>
                    <a href="mailto:admin@studenthub.edu">admin@studenthub.edu</a>
                </div>

                <div class="info-block">
                    <strong>Helpline:</strong>
                    <span>+91 98765 43210</span>
                </div>

                <div class="info-block">
                    <strong>Campus Office:</strong>
                    <span>Department of Computer Engineering, CSPIT, CHARUSAT Campus, Changa, Gujarat 388421</span>
                </div>

                <div class="info-block">
                    <strong>Office Hours:</strong>
                    <span>Monday &ndash; Friday: 9:00 AM &ndash; 5:00 PM</span>
                </div>
            </div>

            <div class="contact-form-card">
                <h3>Send Us a Message</h3>

                <?php if (!empty($message)): ?>
                    <div class="alert-box alert-<?= $messageType ?>" role="alert">
                        <span><?= ($messageType === 'success') ? '✓' : '⚠' ?></span>
                        <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($submitted): ?>
                    <div class="submission-ack">
                        <p>Thank you for getting in touch. An academic counselor or technical support specialist will review your inquiry shortly.</p>
                        <a href="Contact.php" class="btn btn-primary">Send Another Inquiry</a>
                    </div>
                <?php else: ?>
                    <form action="Contact.php" method="POST" novalidate>
                        <div class="form-group">
                            <label for="name">Your Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span class="field-error"><?= $errors["name"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="email">Your Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="e.g. yourname@domain.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span class="field-error"><?= $errors["email"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject <span class="required">*</span></label>
                            <input type="text" id="subject" name="subject" placeholder="What is this inquiry about?" value="<?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span class="field-error"><?= $errors["subject"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="message">Message <span class="required">*</span></label>
                            <textarea id="message" name="message" rows="5" placeholder="Describe your question or issue in detail..." required><?= htmlspecialchars($body, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <span class="field-error"><?= $errors["message"] ?? "" ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Send Message</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<style>
.contact-section {
    max-width: 960px;
    margin: 20px auto;
    text-align: left;
}
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 30px;
}
@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}
.contact-info-card, .contact-form-card {
    background: #ffffff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}
.contact-info-card h2, .contact-form-card h3 {
    color: #1e3a8a;
    margin-top: 0;
    margin-bottom: 12px;
}
.contact-info-card p {
    color: #475569;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 25px;
}
.info-block {
    margin-bottom: 18px;
    font-size: 14px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.info-block strong {
    color: #0f172a;
}
.info-block a {
    color: #2563eb;
    text-decoration: none;
}
.info-block a:hover {
    text-decoration: underline;
}
.info-block span {
    color: #475569;
}
.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 14px;
}
.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
}
.required {
    color: #dc2626;
}
.form-group input, .form-group textarea {
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    box-sizing: border-box;
    width: 100%;
    font-family: inherit;
}
.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
}
.field-error {
    color: #dc2626;
    font-size: 12px;
    margin-top: 3px;
    min-height: 16px;
}
.alert-box {
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-success {
    background-color: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}
.alert-error {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
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
.submission-ack {
    padding: 20px 0;
    text-align: center;
    color: #334155;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
