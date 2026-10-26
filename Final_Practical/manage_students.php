<?php
/**
 * StudentHub Portal - Student Management CRUD
 * Practical 11: Complete Student CRUD with Search, Filter & Role Authorization
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit.php';

// Route Guard: Admin & Faculty only
require_role(['admin', 'faculty']);

$pdo = getDB();
$user = get_logged_user();
$isAdmin = ($user['role'] === 'admin');

$message     = "";
$messageType = "";
$errors      = [];

// Generate CSRF token for write operations
$csrfToken = generate_csrf_token();

// -----------------------------------------------------------------------------
// POST Request Handling: Create, Update, Delete
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $message = "Invalid security token. Please refresh and try again.";
        $messageType = "error";
    } else {
        // --- CREATE STUDENT ---
        if ($action === 'create') {
            $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
            $fullName  = trim($_POST['full_name'] ?? '');
            $email     = strtolower(trim($_POST['email'] ?? ''));
            $mobile    = trim($_POST['mobile'] ?? '');
            $gender    = trim($_POST['gender'] ?? 'Male');
            $year      = trim($_POST['year'] ?? 'Second Year');
            $semester  = (int)($_POST['semester'] ?? 3);
            $course    = trim($_POST['course'] ?? 'Computer Engineering');
            $role      = strtolower(trim($_POST['role'] ?? 'student'));
            $password  = $_POST['password'] ?? 'Password@123';

            if ($studentId === "" || !preg_match('/^2[0-9][A-Z]{2,3}[0-9]{3}$/i', $studentId) && !preg_match('/^(ADM|FAC)[0-9]{3}$/i', $studentId)) {
                $errors['student_id'] = "Valid ID format required (e.g. 25CE077).";
            }
            if ($fullName === "" || mb_strlen($fullName) < 3) {
                $errors['full_name'] = "Name must be at least 3 characters.";
            }
            if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Valid email address required.";
            }
            if ($mobile === "" || !preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
                $errors['mobile'] = "Valid 10-digit mobile number required.";
            }

            if (empty($errors)) {
                try {
                    // Check duplicate
                    $chk = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ? OR email = ?");
                    $chk->execute([$studentId, $email]);
                    if ($chk->fetch()) {
                        $message = "A student with this ID or email already exists.";
                        $messageType = "error";
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare(
                            "INSERT INTO students (student_id, full_name, email, mobile, gender, year, semester, course, role, password, status, created_at)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
                        );
                        $stmt->execute([$studentId, $fullName, $email, $mobile, $gender, $year, $semester, $course, $role, $hash]);

                        log_audit($user['id'], $user['role'], 'STUDENT_CREATED', 'students', $studentId, [
                            'name' => $fullName, 'course' => $course
                        ]);

                        $message = "Student record for '{$fullName}' created successfully.";
                        $messageType = "success";
                    }
                } catch (PDOException $e) {
                    error_log('[StudentHub Create Error] ' . $e->getMessage());
                    $message = "Database error while adding student.";
                    $messageType = "error";
                }
            } else {
                $message = "Please check the highlighted fields and try again.";
                $messageType = "error";
            }
        }

        // --- UPDATE STUDENT ---
        elseif ($action === 'update') {
            $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
            $fullName  = trim($_POST['full_name'] ?? '');
            $email     = strtolower(trim($_POST['email'] ?? ''));
            $mobile    = trim($_POST['mobile'] ?? '');
            $gender    = trim($_POST['gender'] ?? 'Male');
            $year      = trim($_POST['year'] ?? 'Second Year');
            $semester  = (int)($_POST['semester'] ?? 3);
            $course    = trim($_POST['course'] ?? 'Computer Engineering');
            $role      = strtolower(trim($_POST['role'] ?? 'student'));
            $status    = trim($_POST['status'] ?? 'active');

            if ($fullName === "" || mb_strlen($fullName) < 3) {
                $errors['full_name'] = "Name must be at least 3 characters.";
            }
            if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Valid email address required.";
            }
            if ($mobile === "" || !preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
                $errors['mobile'] = "Valid 10-digit mobile number required.";
            }

            if (empty($errors)) {
                try {
                    // Check duplicate email on another student
                    $chk = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
                    $chk->execute([$email, $studentId]);
                    if ($chk->fetch()) {
                        $message = "This email is in use by another account.";
                        $messageType = "error";
                    } else {
                        $stmt = $pdo->prepare(
                            "UPDATE students SET full_name = ?, email = ?, mobile = ?, gender = ?, year = ?, semester = ?, course = ?, role = ?, status = ?, updated_at = NOW()
                             WHERE student_id = ?"
                        );
                        $stmt->execute([$fullName, $email, $mobile, $gender, $year, $semester, $course, $role, $status, $studentId]);

                        log_audit($user['id'], $user['role'], 'STUDENT_UPDATED', 'students', $studentId, [
                            'name' => $fullName, 'status' => $status
                        ]);

                        $message = "Student record '{$studentId}' updated successfully.";
                        $messageType = "success";
                    }
                } catch (PDOException $e) {
                    error_log('[StudentHub Update Error] ' . $e->getMessage());
                    $message = "Database error while updating student.";
                    $messageType = "error";
                }
            } else {
                $message = "Please check the highlighted fields and try again.";
                $messageType = "error";
            }
        }

        // --- DELETE STUDENT ---
        elseif ($action === 'delete') {
            if (!$isAdmin) {
                $message = "Only administrators have permission to delete student records.";
                $messageType = "error";
            } else {
                $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
                if ($studentId === $user['id']) {
                    $message = "You cannot delete your own administrative account.";
                    $messageType = "error";
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
                        $stmt->execute([$studentId]);

                        log_audit($user['id'], $user['role'], 'STUDENT_DELETED', 'students', $studentId, "Deleted student record: {$studentId}");

                        $message = "Student record '{$studentId}' was removed successfully.";
                        $messageType = "success";
                    } catch (PDOException $e) {
                        error_log('[StudentHub Delete Error] ' . $e->getMessage());
                        $message = "Database error while deleting student record.";
                        $messageType = "error";
                    }
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// GET Filtering & Search Query
// -----------------------------------------------------------------------------
$searchQuery  = trim($_GET['q'] ?? '');
$filterCourse = trim($_GET['course'] ?? '');
$filterSem    = trim($_GET['semester'] ?? '');

$sql = "SELECT student_id, full_name, email, mobile, gender, year, semester, course, role, status, created_at
        FROM students WHERE 1=1";
$params = [];

if ($searchQuery !== "") {
    $sql .= " AND (student_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $like = "%{$searchQuery}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($filterCourse !== "") {
    $sql .= " AND course = ?";
    $params[] = $filterCourse;
}

if ($filterSem !== "") {
    $sql .= " AND semester = ?";
    $params[] = (int)$filterSem;
}

$sql .= " ORDER BY role DESC, student_id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
$totalRecords = count($students);

$pageTitle = 'Student Management';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="crud-container">
        <div class="crud-header">
            <div>
                <h2>Student Records Management</h2>
                <span class="count-badge"><?= $totalRecords ?> Record<?= $totalRecords === 1 ? '' : 's' ?></span>
            </div>
            <div class="crud-actions">
                <button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add New Student</button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $messageType ?>" role="alert">
                <span><?= ($messageType === 'success') ? '✓' : '⚠' ?></span>
                <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Bar -->
        <form method="GET" action="manage_students.php" class="filter-bar">
            <div class="filter-group">
                <input type="text" name="q" placeholder="Search by name, ID or email..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="input-search">
            </div>

            <div class="filter-group">
                <select name="course" onchange="this.form.submit()" class="select-filter">
                    <option value="">All Departments</option>
                    <option value="Computer Engineering" <?= ($filterCourse === 'Computer Engineering') ? 'selected' : '' ?>>Computer Engineering</option>
                    <option value="Information Technology" <?= ($filterCourse === 'Information Technology') ? 'selected' : '' ?>>Information Technology</option>
                    <option value="Computer Science" <?= ($filterCourse === 'Computer Science') ? 'selected' : '' ?>>Computer Science</option>
                    <option value="Electronics Engineering" <?= ($filterCourse === 'Electronics Engineering') ? 'selected' : '' ?>>Electronics Engineering</option>
                </select>
            </div>

            <div class="filter-group">
                <select name="semester" onchange="this.form.submit()" class="select-filter">
                    <option value="">All Semesters</option>
                    <?php for ($s = 1; $s <= 8; $s++): ?>
                        <option value="<?= $s ?>" <?= ($filterSem === (string)$s) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if (!empty($searchQuery) || !empty($filterCourse) || !empty($filterSem)): ?>
                <a href="manage_students.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Students Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Contact Email</th>
                        <th>Mobile</th>
                        <th>Department</th>
                        <th>Sem</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <p>No matching student records found.</p>
                                <a href="manage_students.php" class="btn btn-secondary">Clear Filters</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['student_id'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($s['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8') ?>" class="email-link"><?= htmlspecialchars($s['email'], ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><?= htmlspecialchars($s['mobile'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($s['course'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>Sem <?= htmlspecialchars((string)$s['semester'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="role-pill role-<?= strtolower($s['role']) ?>">
                                        <?= ucfirst(htmlspecialchars($s['role'], ENT_QUOTES, 'UTF-8')) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-pill status-<?= strtolower($s['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($s['status'], ENT_QUOTES, 'UTF-8')) ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action-edit" onclick='openEditModal(<?= json_encode($s) ?>)'>Edit</button>
                                    <?php if ($isAdmin && $s['student_id'] !== $user['id']): ?>
                                        <button type="button" class="btn-action-delete" onclick="confirmDelete('<?= htmlspecialchars($s['student_id'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($s['full_name'], ENT_QUOTES, 'UTF-8') ?>')">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Add Student -->
    <div id="addModal" class="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Add New Student Record</h3>
                <button type="button" class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form action="manage_students.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="modal-body">
                    <div class="modal-grid">
                        <div class="form-group">
                            <label for="add_id">Student ID <span class="required">*</span></label>
                            <input type="text" id="add_id" name="student_id" placeholder="e.g. 25CE080" required>
                        </div>
                        <div class="form-group">
                            <label for="add_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="add_name" name="full_name" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label for="add_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="add_email" name="email" placeholder="name@studenthub.edu" required>
                        </div>
                        <div class="form-group">
                            <label for="add_mobile">Mobile Number <span class="required">*</span></label>
                            <input type="tel" id="add_mobile" name="mobile" maxlength="10" placeholder="10-digit number" required>
                        </div>
                        <div class="form-group">
                            <label for="add_gender">Gender</label>
                            <select id="add_gender" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_role">Role</label>
                            <select id="add_role" name="role">
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_course">Department</label>
                            <select id="add_course" name="course">
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electronics Engineering">Electronics Engineering</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_sem">Semester</label>
                            <select id="add_sem" name="semester">
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ($s === 3) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Edit Student -->
    <div id="editModal" class="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Edit Student Details</h3>
                <button type="button" class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form action="manage_students.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="edit_id" name="student_id">

                <div class="modal-body">
                    <div class="modal-grid">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" id="edit_id_display" disabled class="input-disabled">
                        </div>
                        <div class="form-group">
                            <label for="edit_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="edit_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_mobile">Mobile Number <span class="required">*</span></label>
                            <input type="tel" id="edit_mobile" name="mobile" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_gender">Gender</label>
                            <select id="edit_gender" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Account Status</label>
                            <select id="edit_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_course">Department</label>
                            <select id="edit_course" name="course">
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electronics Engineering">Electronics Engineering</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_sem">Semester</label>
                            <select id="edit_sem" name="semester">
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>">Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Confirm Delete Form -->
    <form id="deleteForm" action="manage_students.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" id="delete_id" name="student_id">
    </form>
</main>

<style>
.crud-container {
    max-width: 1180px;
    margin: 20px auto;
    padding: 28px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    text-align: left;
}
.crud-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #55b7cf;
}
.crud-header h2 {
    margin: 0;
    color: #1e3a8a;
    display: inline-block;
    vertical-align: middle;
}
.count-badge {
    background-color: #e0f2fe;
    color: #0369a1;
    font-size: 13px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    margin-left: 10px;
    vertical-align: middle;
}
.filter-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 20px;
    background: #f8fafc;
    padding: 14px 18px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.input-search {
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    min-width: 250px;
}
.select-filter {
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    background: #ffffff;
}
.table-responsive {
    overflow-x: auto;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.data-table th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 2px solid #cbd5e1;
}
.data-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
}
.data-table tr:hover {
    background-color: #f8fafc;
}
.email-link {
    color: #2563eb;
    text-decoration: none;
}
.email-link:hover { text-decoration: underline; }
.role-pill, .status-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
}
.role-student { background: #dbeafe; color: #1e40af; }
.role-faculty { background: #fef3c7; color: #92400e; }
.role-admin { background: #fee2e2; color: #991b1b; }
.status-active { background: #dcfce7; color: #15803d; }
.status-inactive { background: #f1f5f9; color: #64748b; }
.actions-cell {
    white-space: nowrap;
}
.btn-action-edit, .btn-action-delete {
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    margin-right: 4px;
}
.btn-action-edit {
    background: #e0f2fe;
    color: #0369a1;
}
.btn-action-edit:hover { background: #0284c7; color: #ffffff; }
.btn-action-delete {
    background: #fee2e2;
    color: #991b1b;
}
.btn-action-delete:hover { background: #dc2626; color: #ffffff; }
.empty-state {
    text-align: center;
    padding: 40px !important;
    color: #64748b;
}
.btn {
    display: inline-block;
    padding: 9px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
}
.btn-primary { background: #2563eb; color: #ffffff; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #1e293b; }
.btn-secondary:hover { background: #cbd5e1; }
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.btn-outline:hover { background: #f1f5f9; }
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

/* Modal Styling */
.crud-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.crud-modal.show {
    display: flex;
}
.modal-dialog {
    background: #ffffff;
    width: 90%;
    max-width: 600px;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    overflow: hidden;
    animation: modalSlide 0.25s ease;
}
@keyframes modalSlide {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.modal-header {
    background: #f8fafc;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}
.modal-header h3 { margin: 0; color: #1e3a8a; font-size: 17px; }
.close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
.modal-body { padding: 20px; }
.modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.modal-footer {
    background: #f8fafc;
    padding: 14px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #e2e8f0;
}
.form-group {
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 4px;
}
.required { color: #dc2626; }
.form-group input, .form-group select {
    padding: 8px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    font-size: 13px;
    width: 100%;
    box-sizing: border-box;
}
.input-disabled { background: #f1f5f9; color: #64748b; }
</style>

<script>
function openAddModal() {
    document.getElementById("addModal").classList.add("show");
}
function closeAddModal() {
    document.getElementById("addModal").classList.remove("show");
}
function openEditModal(student) {
    document.getElementById("edit_id").value = student.student_id;
    document.getElementById("edit_id_display").value = student.student_id;
    document.getElementById("edit_name").value = student.full_name;
    document.getElementById("edit_email").value = student.email;
    document.getElementById("edit_mobile").value = student.mobile;
    document.getElementById("edit_gender").value = student.gender;
    document.getElementById("edit_status").value = student.status;
    document.getElementById("edit_course").value = student.course;
    document.getElementById("edit_sem").value = student.semester;
    document.getElementById("editModal").classList.add("show");
}
function closeEditModal() {
    document.getElementById("editModal").classList.remove("show");
}
function confirmDelete(studentId, fullName) {
    if (confirm("Are you sure you want to permanently delete the student record for '" + fullName + "' (" + studentId + ")? This action will cascade to any registered events and cannot be undone.")) {
        document.getElementById("delete_id").value = studentId;
        document.getElementById("deleteForm").submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
