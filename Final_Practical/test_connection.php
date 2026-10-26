<?php
/**
 * StudentHub Portal - Database Connection & Integrity Test
 * Practical 8: PDO Verification Utility
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/db.php';

$response = [
    'status'    => 'unknown',
    'timestamp' => date('Y-m-d H:i:s'),
    'driver'    => null,
    'database'  => DB_NAME,
    'tables'    => [],
    'summary'   => []
];

try {
    $pdo = getDB();
    $response['status'] = 'connected';
    $response['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ' v' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    // Verify required tables
    $expectedTables = ['students', 'events', 'registrations', 'audit_logs', 'contact_messages'];
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $missing = array_diff($expectedTables, $existingTables);
    $response['tables'] = $existingTables;
    $response['verified_all_tables'] = empty($missing);

    if (!empty($missing)) {
        $response['missing_tables'] = array_values($missing);
    }

    // Record count summaries
    foreach ($expectedTables as $table) {
        if (in_array($table, $existingTables, true)) {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $response['summary'][$table] = (int)$countStmt->fetchColumn();
        }
    }

    $response['success'] = true;
    $response['message'] = 'Database connection established and schema verified successfully.';
    http_response_code(200);

} catch (PDOException $e) {
    $response['status']  = 'error';
    $response['success'] = false;
    $response['message'] = 'Database connectivity failed. Please ensure MySQL service is active.';
    // Note: Never expose raw credentials or internal trace
    http_response_code(500);
}

echo json_encode($response, JSON_PRETTY_PRINT);
