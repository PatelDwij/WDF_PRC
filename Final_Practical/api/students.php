<?php
/**
 * StudentHub Portal - Students REST API Endpoint
 * Practical 13: Asynchronous REST Operations for Students
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

try {
    // -------------------------------------------------------------------------
    // GET: List or Single Student
    // -------------------------------------------------------------------------
    if ($method === 'GET') {
        $id = trim($_GET['id'] ?? '');

        if ($id !== '') {
            $stmt = $pdo->prepare(
                "SELECT student_id, full_name, email, mobile, gender, year, semester, course, role, status, created_at, updated_at
                 FROM students WHERE student_id = ? LIMIT 1"
            );
            $stmt->execute([$id]);
            $student = $stmt->fetch();

            if ($student) {
                json_response(true, $student, 'Student retrieved successfully.');
            } else {
                json_response(false, null, 'Student not found.', 404);
            }
        }

        // List with filters
        $q      = trim($_GET['q'] ?? '');
        $course = trim($_GET['course'] ?? '');
        $sem    = trim($_GET['semester'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $sql = "SELECT student_id, full_name, email, mobile, gender, year, semester, course, role, status, created_at
                FROM students WHERE 1=1";
        $params = [];

        if ($q !== '') {
            $sql .= " AND (student_id LIKE ? OR full_name LIKE ? OR email LIKE ?)";
            $like = "%{$q}%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        if ($course !== '') {
            $sql .= " AND course = ?";
            $params[] = $course;
        }

        if ($sem !== '') {
            $sql .= " AND semester = ?";
            $params[] = (int)$sem;
        }

        // Total count query
        $countSql = preg_replace('/SELECT .* FROM/i', 'SELECT COUNT(*) FROM', $sql);
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $sql .= " ORDER BY role DESC, student_id ASC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        json_response(true, [
            'students'     => $records,
            'total'        => $totalRecords,
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => ceil($totalRecords / $limit)
        ], 'Student records retrieved successfully.');
    }

    // -------------------------------------------------------------------------
    // POST: Create Student
    // -------------------------------------------------------------------------
    elseif ($method === 'POST') {
        if (!has_role(['admin', 'faculty'])) {
            json_response(false, null, 'Access denied: Administrative privileges required.', 403);
        }
        verify_api_csrf();

        $input     = get_json_input();
        $studentId = strtoupper(trim($input['student_id'] ?? ''));
        $fullName  = trim($input['full_name'] ?? '');
        $email     = strtolower(trim($input['email'] ?? ''));
        $mobile    = trim($input['mobile'] ?? '');
        $gender    = trim($input['gender'] ?? 'Male');
        $year      = trim($input['year'] ?? 'Second Year');
        $semester  = (int)($input['semester'] ?? 3);
        $course    = trim($input['course'] ?? 'Computer Engineering');
        $role      = strtolower(trim($input['role'] ?? 'student'));
        $password  = $input['password'] ?? 'Password@123';

        $errors = [];
        if ($studentId === "") $errors['student_id'] = "Student ID is required.";
        if ($fullName === "") $errors['full_name'] = "Full name is required.";
        if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Valid email is required.";
        if ($mobile === "" || !preg_match('/^[6-9][0-9]{9}$/', $mobile)) $errors['mobile'] = "Valid 10-digit mobile required.";

        if (!empty($errors)) {
            json_response(false, null, 'Validation failed.', 422, $errors);
        }

        // Duplicate check
        $chk = $pdo->prepare("SELECT student_id FROM students WHERE student_id = ? OR email = ?");
        $chk->execute([$studentId, $email]);
        if ($chk->fetch()) {
            json_response(false, null, 'Student ID or email is already registered.', 409);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO students (student_id, full_name, email, mobile, gender, year, semester, course, role, password, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
        );
        $stmt->execute([$studentId, $fullName, $email, $mobile, $gender, $year, $semester, $course, $role, $hash]);

        log_audit($_SESSION['user_id'] ?? 'API', $_SESSION['role'] ?? 'admin', 'API_STUDENT_CREATED', 'students', $studentId, [
            'name' => $fullName
        ]);

        json_response(true, ['student_id' => $studentId], 'Student created successfully.', 201);
    }

    // -------------------------------------------------------------------------
    // PUT: Update Student
    // -------------------------------------------------------------------------
    elseif ($method === 'PUT') {
        if (!has_role(['admin', 'faculty'])) {
            json_response(false, null, 'Access denied.', 403);
        }
        verify_api_csrf();

        $input     = get_json_input();
        $studentId = strtoupper(trim($input['student_id'] ?? ''));
        $fullName  = trim($input['full_name'] ?? '');
        $email     = strtolower(trim($input['email'] ?? ''));
        $mobile    = trim($input['mobile'] ?? '');
        $gender    = trim($input['gender'] ?? 'Male');
        $year      = trim($input['year'] ?? 'Second Year');
        $semester  = (int)($input['semester'] ?? 3);
        $course    = trim($input['course'] ?? 'Computer Engineering');
        $role      = strtolower(trim($input['role'] ?? 'student'));
        $status    = trim($input['status'] ?? 'active');

        if ($studentId === "") {
            json_response(false, null, 'Missing student_id for update.', 400);
        }

        // Check duplicate email
        $chk = $pdo->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
        $chk->execute([$email, $studentId]);
        if ($chk->fetch()) {
            json_response(false, null, 'This email address is in use by another record.', 409);
        }

        $stmt = $pdo->prepare(
            "UPDATE students SET full_name = ?, email = ?, mobile = ?, gender = ?, year = ?, semester = ?, course = ?, role = ?, status = ?, updated_at = NOW()
             WHERE student_id = ?"
        );
        $stmt->execute([$fullName, $email, $mobile, $gender, $year, $semester, $course, $role, $status, $studentId]);

        log_audit($_SESSION['user_id'] ?? 'API', $_SESSION['role'] ?? 'admin', 'API_STUDENT_UPDATED', 'students', $studentId, [
            'status' => $status
        ]);

        json_response(true, ['student_id' => $studentId], 'Student updated successfully.');
    }

    // -------------------------------------------------------------------------
    // DELETE: Delete Student
    // -------------------------------------------------------------------------
    elseif ($method === 'DELETE') {
        if (!has_role('admin')) {
            json_response(false, null, 'Administrator role required to delete student records.', 403);
        }
        verify_api_csrf();

        $input     = get_json_input();
        $studentId = strtoupper(trim($_GET['id'] ?? $input['student_id'] ?? ''));

        if ($studentId === '') {
            json_response(false, null, 'Missing student_id for deletion.', 400);
        }

        if ($studentId === ($_SESSION['user_id'] ?? '')) {
            json_response(false, null, 'Cannot delete active logged-in account.', 400);
        }

        $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->execute([$studentId]);

        log_audit($_SESSION['user_id'] ?? 'API', 'admin', 'API_STUDENT_DELETED', 'students', $studentId, "Deleted {$studentId}");

        json_response(true, ['student_id' => $studentId], 'Student deleted successfully.');
    }

    else {
        json_response(false, null, "Method {$method} not allowed.", 405);
    }

} catch (PDOException $e) {
    error_log('[StudentHub Students API Error] ' . $e->getMessage());
    json_response(false, null, 'Database query failed.', 500);
}
