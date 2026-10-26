<?php
/**
 * StudentHub Portal - Audit Logging System
 * Practical 14: Security Audit Trail & Activity Monitoring
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Record a security, administrative, or CRUD action in the immutable audit log.
 *
 * @param string|null $userId Identifier of user performing action (or 'GUEST'/'SYSTEM')
 * @param string|null $role Role at time of action (student, faculty, admin, guest, system)
 * @param string $action Standard action verb (e.g., USER_REGISTERED, USER_LOGIN, STUDENT_CREATED)
 * @param string $entity Target entity name (e.g., students, events, registrations)
 * @param string|null $entityId Target primary key (e.g., student_id or event_id)
 * @param array|string|null $details Contextual details (will be JSON-encoded; secrets stripped)
 * @return bool True if logged successfully, false otherwise
 */
function log_audit(?string $userId, ?string $role, string $action, string $entity, ?string $entityId = null, $details = null): bool {
    try {
        $pdo = getDB();

        // Sanitize details: strip password fields if passed by accident
        if (is_array($details)) {
            unset($details['password'], $details['confirmPassword'], $details['password_hash'], $details['token']);
            $detailsStr = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $detailsStr = (string)$details;
        }

        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        // If comma-separated in proxy, get first IP
        if (strpos($clientIp, ',') !== false) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (user_id, role, action, entity, entity_id, details, ip_address, created_at)
             VALUES (:user_id, :role, :action, :entity, :entity_id, :details, :ip_address, NOW())"
        );

        return $stmt->execute([
            ':user_id'    => $userId ?: 'GUEST',
            ':role'       => $role ?: 'guest',
            ':action'     => strtoupper($action),
            ':entity'     => strtolower($entity),
            ':entity_id'  => $entityId,
            ':details'    => $detailsStr,
            ':ip_address' => substr($clientIp, 0, 45)
        ]);
    } catch (Exception $e) {
        // Never allow audit log write failure to interrupt user workflow; log server-side
        error_log('[StudentHub Audit Error] ' . $e->getMessage());
        return false;
    }
}
