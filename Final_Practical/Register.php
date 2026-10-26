<?php
/**
 * StudentHub Portal - User Registration
 * Practicals 7 & 9: Server-Side Validation, Dual JSON/CSV File Storage & Secure Database Registration
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit.php';

// Storage configuration for Practical 7 file storage
$dataDir  = DATA_DIR;
$jsonFile = $dataDir . DIRECTORY_SEPARATOR . 'registrations.json';
$csvFile  = $dataDir . DIRECTORY_SEPARATOR . 'students.csv';

$message       = "";
$messageType   = ""; // "success", "error"
$errors        = [];
$isPost        = ($_SERVER["REQUEST_METHOD"] === "POST");
$recordCreated = false;

// Form field repopulation values
$studentId     = "";
$fullName      = "";
$email         = "";
$mobile        = "";
$gender        = "";
$year          = "";
$semester      = "";
$course        = "";
$role          = "student";
$termsAccepted = false;

// Safe JSON file-storage helper with LOCK_EX (Practical 7)
function append_to_json_storage(string $filePath, array $record): array {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fp = @fopen($filePath, 'c+');
    if (!$fp) {
        return ['success' => false, 'error' => 'Unable to open storage file.'];
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return ['success' => false, 'error' => 'Unable to acquire file lock.'];
    }

    $fileSize = filesize($filePath);
    $records  = [];

    if ($fileSize > 0) {
        rewind($fp);
        $raw = fread($fp, $fileSize);
        if ($raw !== false && trim($raw) !== "") {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $records = $decoded;
            }
        }
    }

    $records[] = $record;
    $output = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $output);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return ['success' => true];
}

// Safe CSV file-storage helper with header check (Practical 7)
function append_to_csv_storage(string $filePath, array $record): void {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $fileExists = file_exists($filePath) && (filesize($filePath) > 0);
    $fp = @fopen($filePath, 'a');
    if (!$fp) {
        return;
    }

    if (flock($fp, LOCK_EX)) {
        if (!$fileExists) {
            fputcsv($fp, [
                'Student ID', 'Full Name', 'Email', 'Mobile', 'Gender',
                'Year', 'Semester', 'Course', 'Role', 'Password Hash', 'Registered At'
            ]);
        }
        fputcsv($fp, [
            $record['student_id'] ?? '',
            $record['full_name'] ?? '',
            $record['email'] ?? '',
            $record['mobile'] ?? '',
            $record['gender'] ?? '',
            $record['year'] ?? '',
            $record['semester'] ?? '',
            $record['course'] ?? '',
            $record['role'] ?? '',
            $record['password_hash'] ?? '',
            $record['registered_at'] ?? date('Y-m-d H:i:s')
        ]);
        fflush($fp);
        flock($fp, LOCK_UN);
    }
    fclose($fp);
}

if ($isPost) {
    // 1. Sanitize input strings
    $studentId     = strtoupper(trim($_POST["studentId"] ?? ""));
    $fullName      = trim($_POST["fullName"] ?? "");
    $email         = strtolower(trim($_POST["email"] ?? ""));
    $password      = $_POST["password"] ?? "";
    $confirmPass   = $_POST["confirmPassword"] ?? "";
    $mobile        = trim($_POST["mobile"] ?? "");
    $gender        = trim($_POST["gender"] ?? "");
    $year          = trim($_POST["year"] ?? "");
    $semester      = trim($_POST["semester"] ?? "");
    $course        = trim($_POST["course"] ?? "");
    $role          = strtolower(trim($_POST["role"] ?? "student"));
    $termsAccepted = isset($_POST["terms"]) && $_POST["terms"] == "1";

    // 2. Server-side validation rules
    // Rule A: Student ID format
    if ($studentId === "") {
        $errors["studentId"] = "Student ID is required.";
    } elseif (!preg_match('/^2[0-9][A-Z]{2,3}[0-9]{3}$/i', $studentId) && !preg_match('/^(ADM|FAC)[0-9]{3}$/i', $studentId)) {
        $errors["studentId"] = "Please enter a valid university ID format (e.g. 25CE077).";
    }

    // Rule B: Full Name
    if ($fullName === "") {
        $errors["fullName"] = "Full name is required.";
    } elseif (mb_strlen($fullName) < 3) {
        $errors["fullName"] = "Full name must be at least 3 characters.";
    } elseif (!preg_match("/^[a-zA-Z\s\.\'-]+$/u", $fullName)) {
        $errors["fullName"] = "Name may only contain letters, spaces, and hyphens.";
    }

    // Rule C: Email address
    if ($email === "") {
        $errors["email"] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Please enter a valid email address.";
    }

    // Rule D: Password validation
    if ($password === "") {
        $errors["password"] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors["password"] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors["password"] = "Password must include both letters and numbers.";
    }

    // Rule E: Confirm Password match
    if ($confirmPass === "") {
        $errors["confirmPassword"] = "Please confirm your password.";
    } elseif ($password !== $confirmPass) {
        $errors["confirmPassword"] = "Passwords do not match.";
    }

    // Rule F: Mobile number
    if ($mobile === "") {
        $errors["mobile"] = "Mobile number is required.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        $errors["mobile"] = "Please enter a valid 10-digit mobile number.";
    }

    // Rule G: Gender whitelist
    $validGenders = ['Male', 'Female', 'Other'];
    if ($gender === "" || !in_array($gender, $validGenders, true)) {
        $errors["gender"] = "Please select your gender.";
    }

    // Rule H: Year whitelist
    $validYears = ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Faculty'];
    if ($year === "" || !in_array($year, $validYears, true)) {
        $errors["year"] = "Please select your academic year.";
    }

    // Rule I: Semester whitelist
    $validSemesters = ['1', '2', '3', '4', '5', '6', '7', '8', '0'];
    if ($semester === "" || !in_array($semester, $validSemesters, true)) {
        $errors["semester"] = "Please select your semester.";
    }

    // Rule J: Course whitelist
    $validCourses = [
        'Computer Engineering',
        'Information Technology',
        'Computer Science',
        'Electronics Engineering'
    ];
    if ($course === "" || !in_array($course, $validCourses, true)) {
        $errors["course"] = "Please select your course or department.";
    }

    // Rule K: Role whitelist
    $validRoles = ['student', 'faculty'];
    if (!in_array($role, $validRoles, true)) {
        $role = 'student';
    }

    // Rule L: Terms acceptance
    if (!$termsAccepted) {
        $errors["terms"] = "You must agree to the portal terms and conditions.";
    }

    // 3. Database Duplicate Verification (Practical 9)
    if (empty($errors)) {
        try {
            $pdo = getDB();

            // Check if email already exists
            $emailStmt = $pdo->prepare("SELECT student_id FROM students WHERE email = :email LIMIT 1");
            $emailStmt->execute([':email' => $email]);
            if ($emailStmt->fetch()) {
                $errors['email'] = "This email is already registered. Please sign in or use another email.";
            }

            // Check if student ID already exists
            $idStmt = $pdo->prepare("SELECT email FROM students WHERE student_id = :id LIMIT 1");
            $idStmt->execute([':id' => $studentId]);
            if ($idStmt->fetch()) {
                $errors['studentId'] = "This Student ID is already registered. Please sign in or verify your ID.";
            }
        } catch (PDOException $e) {
            error_log('[StudentHub Registration DB Error] ' . $e->getMessage());
            $message = "Unable to process registration at this moment. Please try again later.";
            $messageType = "error";
        }
    }

    // 4. Persistence upon successful validation
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $registeredAt   = date("Y-m-d H:i:s");

            // Insert into Database (Practical 9)
            $pdo = getDB();
            $insertStmt = $pdo->prepare(
                "INSERT INTO students (student_id, full_name, email, mobile, gender, year, semester, course, role, password, status, created_at)
                 VALUES (:student_id, :full_name, :email, :mobile, :gender, :year, :semester, :course, :role, :password, 'active', :created_at)"
            );

            $insertStmt->execute([
                ':student_id' => $studentId,
                ':full_name'  => $fullName,
                ':email'      => $email,
                ':mobile'     => $mobile,
                ':gender'     => $gender,
                ':year'       => $year,
                ':semester'   => (int)$semester,
                ':course'     => $course,
                ':role'       => $role,
                ':password'   => $hashedPassword,
                ':created_at' => $registeredAt
            ]);

            // Dual persistence into JSON & CSV files (Practical 7)
            $fileRecord = [
                "id"            => "REG-" . date("Ymd") . "-" . substr(uniqid(), -4),
                "student_id"    => $studentId,
                "full_name"     => $fullName,
                "email"         => $email,
                "mobile"        => $mobile,
                "gender"        => $gender,
                "year"          => $year,
                "semester"      => $semester,
                "course"        => $course,
                "role"          => ucfirst($role),
                "password_hash" => $hashedPassword,
                "registered_at" => $registeredAt
            ];

            append_to_json_storage($jsonFile, $fileRecord);
            append_to_csv_storage($csvFile, $fileRecord);

            // Audit log entry (Practical 14)
            log_audit($studentId, $role, 'USER_REGISTERED', 'students', $studentId, [
                'full_name' => $fullName,
                'email'     => $email,
                'course'    => $course
            ]);

            $message       = "Your registration was submitted successfully. You can now sign in.";
            $messageType   = "success";
            $recordCreated = true;

            // Clear form state
            $studentId = $fullName = $email = $mobile = $gender = $year = $semester = $course = "";
            $termsAccepted = false;

        } catch (PDOException $e) {
            error_log('[StudentHub Registration DB Insert Error] ' . $e->getMessage());
            $message = "Something went wrong while creating your account. Please try again.";
            $messageType = "error";
        }
    } else {
        $message = "Please check the highlighted fields and try again.";
        $messageType = "error";
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="register-section">
        <div class="auth-card">
            <h2>Create Your StudentHub Account</h2>
            <p class="auth-subtitle">Join the centralized academic portal to access courses, campus events, and student services.</p>

            <?php if (!empty($message)): ?>
                <div class="alert-box alert-<?= $messageType ?>" role="alert">
                    <span><?= ($messageType === 'success') ? '✓' : '⚠' ?></span>
                    <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            <?php endif; ?>

            <?php if ($recordCreated): ?>
                <div class="success-prompt">
                    <h3>Account Ready</h3>
                    <p>Welcome to StudentHub! Your academic profile has been created.</p>
                    <div class="prompt-actions">
                        <a href="Login.php" class="btn btn-primary">Sign In to Your Account</a>
                        <a href="Register.php" class="btn btn-secondary">Register Another Account</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="registerForm" action="Register.php" method="POST" novalidate>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="studentId">Student / Faculty ID <span class="required">*</span></label>
                            <input type="text" id="studentId" name="studentId" placeholder="e.g. 25CE077" value="<?= htmlspecialchars($studentId, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span id="studentIdError" class="field-error"><?= $errors["studentId"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="fullName">Full Name <span class="required">*</span></label>
                            <input type="text" id="fullName" name="fullName" placeholder="Enter your full name" value="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span id="nameError" class="field-error"><?= $errors["fullName"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="email">University Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="e.g. name@studenthub.edu" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span id="emailError" class="field-error"><?= $errors["email"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="mobile">Mobile Number <span class="required">*</span></label>
                            <input type="tel" id="mobile" name="mobile" placeholder="10-digit mobile number" maxlength="10" value="<?= htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') ?>" required>
                            <span id="mobileError" class="field-error"><?= $errors["mobile"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" placeholder="Minimum 8 characters" required>
                            <div id="passwordStrength" class="password-meter"></div>
                            <span id="passwordError" class="field-error"><?= $errors["password"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Re-enter password" required>
                            <span id="confirmPasswordError" class="field-error"><?= $errors["confirmPassword"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                            <span id="genderError" class="field-error"><?= $errors["gender"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="role">Account Type <span class="required">*</span></label>
                            <select id="role" name="role" required>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Student</option>
                                <option value="faculty" <?= $role === 'faculty' ? 'selected' : '' ?>>Faculty Member</option>
                            </select>
                            <span id="roleError" class="field-error"><?= $errors["role"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="course">Program / Department <span class="required">*</span></label>
                            <select id="course" name="course" required>
                                <option value="">Select Department</option>
                                <option value="Computer Engineering" <?= $course === 'Computer Engineering' ? 'selected' : '' ?>>Computer Engineering</option>
                                <option value="Information Technology" <?= $course === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                <option value="Computer Science" <?= $course === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Electronics Engineering" <?= $course === 'Electronics Engineering' ? 'selected' : '' ?>>Electronics Engineering</option>
                            </select>
                            <span id="courseError" class="field-error"><?= $errors["course"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="year">Academic Year <span class="required">*</span></label>
                            <select id="year" name="year" required>
                                <option value="">Select Year</option>
                                <option value="First Year" <?= $year === 'First Year' ? 'selected' : '' ?>>First Year</option>
                                <option value="Second Year" <?= $year === 'Second Year' ? 'selected' : '' ?>>Second Year</option>
                                <option value="Third Year" <?= $year === 'Third Year' ? 'selected' : '' ?>>Third Year</option>
                                <option value="Fourth Year" <?= $year === 'Fourth Year' ? 'selected' : '' ?>>Fourth Year</option>
                                <option value="Faculty" <?= $year === 'Faculty' ? 'selected' : '' ?>>Faculty / Staff</option>
                            </select>
                            <span id="yearError" class="field-error"><?= $errors["year"] ?? "" ?></span>
                        </div>

                        <div class="form-group">
                            <label for="semester">Semester <span class="required">*</span></label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <option value="0" <?= $semester === '0' ? 'selected' : '' ?>>N/A (Faculty)</option>
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= $semester === (string)$s ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                            <span id="semesterError" class="field-error"><?= $errors["semester"] ?? "" ?></span>
                        </div>
                    </div>

                    <div class="form-terms">
                        <label class="checkbox-container">
                            <input type="checkbox" id="terms" name="terms" value="1" <?= $termsAccepted ? 'checked' : '' ?>>
                            <span>I agree to the StudentHub Portal Terms of Service and Academic Code of Conduct.</span>
                        </label>
                        <span id="termsError" class="field-error"><?= $errors["terms"] ?? "" ?></span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-submit" id="submitBtn">Create Account</button>
                    </div>

                    <div class="auth-footer-links">
                        Already have an account? <a href="Login.php">Sign In here</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
/* Modern Student Portal Registration Styling */
.register-section {
    max-width: 820px;
    margin: 20px auto;
    text-align: left;
}
.auth-card {
    background: #ffffff;
    padding: 35px 40px;
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
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px 24px;
}
@media (max-width: 640px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    .auth-card {
        padding: 24px 20px;
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
.required {
    color: #dc2626;
}
.form-group input, .form-group select {
    padding: 10px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.2s ease;
    box-sizing: border-box;
    width: 100%;
}
.form-group input:focus, .form-group select:focus {
    outline: none;
    border-color: #2563eb;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
}
.field-error {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
    min-height: 16px;
    display: block;
}
.form-terms {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}
.checkbox-container {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 13px;
    color: #475569;
}
.checkbox-container input {
    margin-top: 3px;
    width: 16px;
    height: 16px;
    accent-color: #2563eb;
}
.form-actions {
    margin-top: 25px;
    text-align: center;
}
.btn {
    display: inline-block;
    padding: 11px 24px;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}
.btn-submit {
    background-color: #2563eb;
    color: #ffffff;
    width: 100%;
    padding: 12px;
}
.btn-submit:hover {
    background-color: #1d4ed8;
}
.btn-primary {
    background-color: #2563eb;
    color: #ffffff;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}
.btn-secondary:hover {
    background-color: #cbd5e1;
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
.success-prompt {
    text-align: center;
    padding: 30px 20px;
}
.success-prompt h3 {
    color: #065f46;
    margin-bottom: 10px;
}
.success-prompt p {
    color: #475569;
    margin-bottom: 25px;
}
.prompt-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}
.password-meter {
    font-size: 12px;
    margin-top: 4px;
    font-weight: 600;
}
</style>

<?php
$extraJs = '<script src="Register.js"></script>';
require_once __DIR__ . '/includes/footer.php';
?>
