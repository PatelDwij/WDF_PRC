<?php
/**
 * StudentHub Portal - REST API Configuration & Helpers
 * Practical 13: PHP JSON API Framework
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';

/**
 * Output structured JSON response and terminate.
 *
 * @param bool $success Whether the operation succeeded
 * @param mixed $data Payload data
 * @param string $message User-friendly message
 * @param int $httpCode HTTP status code (default 200)
 * @param array $errors Validation error map if any
 */
function json_response(bool $success, $data = null, string $message = '', int $httpCode = 200, array $errors = []): void {
    http_response_code($httpCode);

    $response = [
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => $data
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Verify CSRF token from header for write operations.
 */
function verify_api_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        json_response(false, null, 'Invalid or missing CSRF token in request.', 403);
    }
}

/**
 * Retrieve JSON request body as associative array.
 *
 * @return array
 */
function get_json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? array_merge($_POST, $decoded) : $_POST;
}
