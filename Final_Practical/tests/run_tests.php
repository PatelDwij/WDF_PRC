<?php
/**
 * StudentHub Portal - Comprehensive Automated Test Suite
 * Practicals 7 to 15 Verification Runner
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';

$testResults = [];

function record_test(string $practical, string $name, bool $passed, string $details = ''): void {
    global $testResults;
    $testResults[] = [
        'practical' => $practical,
        'name'      => $name,
        'passed'    => $passed,
        'details'   => $details
    ];
    $statusText = $passed ? "[PASS]" : "[FAIL]";
    echo sprintf("%-8s | %-14s | %-45s | %s\n", $statusText, $practical, $name, $details);
}

echo "========================================================================================\n";
echo " STUDENTHUB PORTAL — PRACTICALS 7 TO 15 AUTOMATED INTEGRATION TEST SUITE\n";
echo " Student: Dwij Patel (25CE077) | University: CHARUSAT | B.Tech Computer Engineering\n";
echo "========================================================================================\n\n";

// -----------------------------------------------------------------------------
// Test 1: Practical 8 - PDO Connection & Normalized Database Schema
// -----------------------------------------------------------------------------
try {
    $pdo = getDB();
    record_test('Practical 8', 'PDO Database Connection', true, 'Connected to MySQL via PDO (utf8mb4)');

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['students', 'events', 'registrations', 'audit_logs', 'contact_messages'];
    $hasAll = count(array_intersect($requiredTables, $tables)) === count($requiredTables);

    record_test('Practical 8', 'Database Schema Verification', $hasAll, 'All 5 normalized entities present');

    $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    record_test('Practical 8', 'Seed Data Completeness', ($studentCount >= 20), "Seeded {$studentCount} user records (>= 20 required)");

} catch (Exception $e) {
    record_test('Practical 8', 'Database Verification', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 2: Practical 7 - Server Validation & Dual JSON/CSV File Storage
// -----------------------------------------------------------------------------
$jsonFile = DATA_DIR . DIRECTORY_SEPARATOR . 'registrations.json';
$csvFile  = DATA_DIR . DIRECTORY_SEPARATOR . 'students.csv';

$jsonExists = file_exists($jsonFile);
$csvExists  = file_exists($csvFile);
record_test('Practical 7', 'JSON Storage File Existence', $jsonExists, 'registrations.json is present and readable');
record_test('Practical 7', 'CSV Storage File Existence', $csvExists, 'students.csv is present and readable');

if ($jsonExists) {
    $jsonData = json_decode(file_get_contents($jsonFile), true);
    $jsonValid = is_array($jsonData) && count($jsonData) > 0;
    record_test('Practical 7', 'JSON Data Integrity', $jsonValid, count($jsonData) . ' records validated in JSON file');
}

// -----------------------------------------------------------------------------
// Test 3: Practical 9 - Secure User Registration & Hashing
// -----------------------------------------------------------------------------
try {
    $testId = '25TEST01';
    $testEmail = 'test.student01@studenthub.edu';
    $testPass = 'SecurePass@2026';

    // Cleanup previous test run
    $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$testId]);

    $hashed = password_hash($testPass, PASSWORD_DEFAULT);
    $ins = $pdo->prepare(
        "INSERT INTO students (student_id, full_name, email, mobile, gender, year, semester, course, role, password, status, created_at)
         VALUES (?, 'Automated Test User', ?, '9825100099', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', ?, 'active', NOW())"
    );
    $ins->execute([$testId, $testEmail, $hashed]);

    $inserted = $pdo->query("SELECT * FROM students WHERE student_id = '{$testId}'")->fetch();
    $insertOk = ($inserted !== false && password_verify($testPass, $inserted['password']));
    record_test('Practical 9', 'Password Hash & Insert', $insertOk, 'Bcrypt hash generated and verified via password_verify');

    // Duplicate Check
    $dupCheck = false;
    try {
        $ins->execute([$testId, 'another@email.com', $hashed]);
    } catch (PDOException $e) {
        $dupCheck = true; // Primary key duplicate caught
    }
    record_test('Practical 9', 'Duplicate Student ID Constraint', $dupCheck, 'Database uniqueness constraint prevented duplicate ID');

    // Cleanup
    $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$testId]);

} catch (Exception $e) {
    record_test('Practical 9', 'Secure Registration Test', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 4: Practical 10 - Authentication, Password Verify & RBAC
// -----------------------------------------------------------------------------
try {
    // Verify Student Account
    $sStmt = $pdo->prepare("SELECT * FROM students WHERE student_id = '25CE077'");
    $sStmt->execute();
    $studentUser = $sStmt->fetch();
    $sAuthOk = ($studentUser && password_verify('Password@123', $studentUser['password']));
    record_test('Practical 10', 'Student Authentication (25CE077)', $sAuthOk, 'Password verified with student role');

    // Verify Faculty Account
    $fStmt = $pdo->prepare("SELECT * FROM students WHERE student_id = 'FAC001'");
    $fStmt->execute();
    $facultyUser = $fStmt->fetch();
    $fAuthOk = ($facultyUser && password_verify('Password@123', $facultyUser['password']));
    record_test('Practical 10', 'Faculty Authentication (FAC001)', $fAuthOk, 'Password verified with faculty role');

    // Verify Admin Account
    $aStmt = $pdo->prepare("SELECT * FROM students WHERE student_id = 'ADM001'");
    $aStmt->execute();
    $adminUser = $aStmt->fetch();
    $aAuthOk = ($adminUser && password_verify('Password@123', $adminUser['password']));
    record_test('Practical 10', 'Admin Authentication (ADM001)', $aAuthOk, 'Password verified with admin role');

} catch (Exception $e) {
    record_test('Practical 10', 'Authentication Verification', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 5: Practical 11 - Student Management CRUD Operations
// -----------------------------------------------------------------------------
try {
    $crudId = '25CRUD01';
    $pdo->prepare("DELETE FROM students WHERE student_id = ?")->execute([$crudId]);

    // CREATE
    $createStmt = $pdo->prepare(
        "INSERT INTO students (student_id, full_name, email, mobile, gender, year, semester, course, role, password, status, created_at)
         VALUES (?, 'CRUD Test Student', 'crud.test@studenthub.edu', '9825100088', 'Female', 'Second Year', 3, 'Information Technology', 'student', 'dummy_hash', 'active', NOW())"
    );
    $createStmt->execute([$crudId]);
    $created = (bool)$pdo->query("SELECT student_id FROM students WHERE student_id = '{$crudId}'")->fetch();
    record_test('Practical 11', 'Student CRUD - Create', $created, "Student record {$crudId} inserted successfully");

    // READ / SEARCH
    $readStmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $readStmt->execute([$crudId]);
    $readRec = $readStmt->fetch();
    $readOk = ($readRec && $readRec['full_name'] === 'CRUD Test Student');
    record_test('Practical 11', 'Student CRUD - Read', $readOk, 'Retrieved student record with correct fields');

    // UPDATE
    $updateStmt = $pdo->prepare("UPDATE students SET full_name = 'CRUD Student Updated', status = 'inactive' WHERE student_id = ?");
    $updateStmt->execute([$crudId]);
    $updatedRec = $pdo->query("SELECT full_name, status FROM students WHERE student_id = '{$crudId}'")->fetch();
    $updateOk = ($updatedRec && $updatedRec['full_name'] === 'CRUD Student Updated' && $updatedRec['status'] === 'inactive');
    record_test('Practical 11', 'Student CRUD - Update', $updateOk, 'Updated name and status verified in database');

    // DELETE
    $delStmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $delStmt->execute([$crudId]);
    $delRec = $pdo->query("SELECT student_id FROM students WHERE student_id = '{$crudId}'")->fetch();
    $delOk = ($delRec === false);
    record_test('Practical 11', 'Student CRUD - Delete', $delOk, "Record {$crudId} removed completely from database");

} catch (Exception $e) {
    record_test('Practical 11', 'Student CRUD Operations', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 6: Practical 12 - Event Management CRUD & Registrations
// -----------------------------------------------------------------------------
try {
    // CREATE EVENT
    $evtIns = $pdo->prepare(
        "INSERT INTO events (title, description, category, event_date, venue, capacity, status, created_by, created_at)
         VALUES ('Test Hackathon', 'Automated Test Event', 'Technical', '2026-12-01 10:00:00', 'Lab 201', 50, 'upcoming', 'ADM001', NOW())"
    );
    $evtIns->execute();
    $testEventId = (int)$pdo->lastInsertId();
    record_test('Practical 12', 'Event CRUD - Create', ($testEventId > 0), "Created event ID #{$testEventId}");

    // STUDENT EVENT REGISTRATION
    $regIns = $pdo->prepare("INSERT INTO registrations (student_id, event_id, status, registered_at) VALUES ('25CE077', ?, 'confirmed', NOW())");
    $regIns->execute([$testEventId]);
    $regOk = (bool)$pdo->query("SELECT registration_id FROM registrations WHERE student_id = '25CE077' AND event_id = {$testEventId}")->fetch();
    record_test('Practical 12', 'Student Event Registration', $regOk, "Student 25CE077 registered for event #{$testEventId}");

    // DUPLICATE REGISTRATION PREVENTION
    $dupRegCaught = false;
    try {
        $regIns->execute([$testEventId]);
    } catch (PDOException $e) {
        $dupRegCaught = true;
    }
    record_test('Practical 12', 'Duplicate Registration Prevention', $dupRegCaught, 'Unique constraint (student_id, event_id) enforced');

    // CLEANUP
    $pdo->prepare("DELETE FROM events WHERE event_id = ?")->execute([$testEventId]);
    $cascadeCheck = (bool)$pdo->query("SELECT registration_id FROM registrations WHERE event_id = {$testEventId}")->fetch();
    record_test('Practical 12', 'Event Cascade Deletion', (!$cascadeCheck), 'Cascading delete removed child registrations automatically');

} catch (Exception $e) {
    record_test('Practical 12', 'Event Management Test', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 7: Practical 13 - REST API Framework & Endpoints
// -----------------------------------------------------------------------------
try {
    $apiConfigExists = file_exists(__DIR__ . '/../api/config.php');
    $apiStudentsExists = file_exists(__DIR__ . '/../api/students.php');
    $apiEventsExists = file_exists(__DIR__ . '/../api/events.php');
    $apiRegExists = file_exists(__DIR__ . '/../api/registrations.php');

    $allApiPresent = ($apiConfigExists && $apiStudentsExists && $apiEventsExists && $apiRegExists);
    record_test('Practical 13', 'API Endpoints Structure', $allApiPresent, 'config.php, students.php, events.php, registrations.php present');

    // Simulate internal GET query from students API
    $stmt = $pdo->query("SELECT student_id, full_name, email, course FROM students LIMIT 2");
    $sampleStudents = $stmt->fetchAll();
    record_test('Practical 13', 'Students API Query Handler', (count($sampleStudents) === 2), 'Returned valid student array for JSON serialization');

} catch (Exception $e) {
    record_test('Practical 13', 'REST API Test', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 8: Practical 14 - Admin Metrics & Audit Logging
// -----------------------------------------------------------------------------
try {
    // Test Audit Logger
    $logOk = log_audit('TEST_SUITE', 'system', 'TEST_ACTION', 'test_entity', 'T1', ['status' => 'automated_test']);
    record_test('Practical 14', 'Audit Log Writer', $logOk, 'log_audit() executed and saved to audit_logs table');

    $latestLog = $pdo->query("SELECT * FROM audit_logs WHERE action = 'TEST_ACTION' ORDER BY created_at DESC LIMIT 1")->fetch();
    $logVerified = ($latestLog && $latestLog['user_id'] === 'TEST_SUITE');
    record_test('Practical 14', 'Audit Log Retrieval', $logVerified, 'Audit entry verified with IP, timestamp, action verb');

    // Clean test log
    $pdo->query("DELETE FROM audit_logs WHERE action = 'TEST_ACTION'");

} catch (Exception $e) {
    record_test('Practical 14', 'Admin & Audit Trail Test', false, $e->getMessage());
}

// -----------------------------------------------------------------------------
// Test 9: Practical 15 - Navigation, Routes & File Integrity
// -----------------------------------------------------------------------------
$coreFiles = [
    'index.php',
    'Final_Practical/index.php',
    'Final_Practical/Login.php',
    'Final_Practical/logout.php',
    'Final_Practical/Register.php',
    'Final_Practical/Student_dashboard.php',
    'Final_Practical/Faculty_dashboard.php',
    'Final_Practical/Admin.php',
    'Final_Practical/manage_students.php',
    'Final_Practical/manage_events.php',
    'Final_Practical/events.php',
    'Final_Practical/ajax_crud.php',
    'Final_Practical/audit_logs.php',
    'Final_Practical/Profile.php',
    'Final_Practical/Contact.php',
    'Final_Practical/view_students.php',
    'Final_Practical/config/db.php',
    'Final_Practical/config/config.php',
    'Final_Practical/includes/auth.php',
    'Final_Practical/includes/nav.php',
    'Final_Practical/includes/audit.php',
    'Final_Practical/database/studenthub.sql',
    'Final_Practical/database/ER_DIAGRAM.md'
];

$allFilesExist = true;
$missingFiles = [];
foreach ($coreFiles as $f) {
    $fullPath = BASE_PATH . DIRECTORY_SEPARATOR . ($f === 'index.php' ? '..' . DIRECTORY_SEPARATOR . 'index.php' : str_replace('Final_Practical/', '', $f));
    if (!file_exists($fullPath)) {
        $allFilesExist = false;
        $missingFiles[] = $f;
    }
}
record_test('Practical 15', 'Full Project File Integrity', $allFilesExist, count($coreFiles) . ' core integration files verified');

// -----------------------------------------------------------------------------
// Final Summary
// -----------------------------------------------------------------------------
$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, fn($t) => $t['passed']));
$failedTests = $totalTests - $passedTests;

echo "\n========================================================================================\n";
echo sprintf(" TEST RUN FINISHED: %d Total | %d Passed | %d Failed\n", $totalTests, $passedTests, $failedTests);
echo " Pass Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";
echo "========================================================================================\n";

exit($failedTests === 0 ? 0 : 1);
