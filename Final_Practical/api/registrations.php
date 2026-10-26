<?php
/**
 * StudentHub Portal - Event Registrations REST API Endpoint
 * Practical 13: Asynchronous Event Registration & Cancellation
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    json_response(false, null, 'Authentication required.', 401);
}

$method    = $_SERVER['REQUEST_METHOD'];
$pdo       = getDB();
$studentId = $_SESSION['user_id'];
$userRole  = $_SESSION['role'];

try {
    // -------------------------------------------------------------------------
    // GET: List user's registrations
    // -------------------------------------------------------------------------
    if ($method === 'GET') {
        $stmt = $pdo->prepare(
            "SELECT r.registration_id, r.registered_at, r.status, e.event_id, e.title, e.category, e.event_date, e.venue
             FROM registrations r
             JOIN events e ON r.event_id = e.event_id
             WHERE r.student_id = ?
             ORDER BY e.event_date ASC"
        );
        $stmt->execute([$studentId]);
        $registrations = $stmt->fetchAll();

        json_response(true, $registrations, 'User registrations retrieved.');
    }

    // -------------------------------------------------------------------------
    // POST: Register for Event
    // -------------------------------------------------------------------------
    elseif ($method === 'POST') {
        verify_api_csrf();
        $input   = get_json_input();
        $eventId = (int)($input['event_id'] ?? 0);

        if ($eventId <= 0) {
            json_response(false, null, 'Invalid event_id provided.', 400);
        }

        // Check event existence and capacity
        $evtStmt = $pdo->prepare(
            "SELECT e.*, COUNT(r.registration_id) AS attendee_count
             FROM events e
             LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status != 'cancelled'
             WHERE e.event_id = ?
             GROUP BY e.event_id"
        );
        $evtStmt->execute([$eventId]);
        $event = $evtStmt->fetch();

        if (!$event) {
            json_response(false, null, 'Event does not exist.', 404);
        }

        if ($event['status'] !== 'upcoming') {
            json_response(false, null, 'This event is not open for registration.', 400);
        }

        if ($event['attendee_count'] >= $event['capacity']) {
            json_response(false, null, 'This event has reached maximum capacity.', 409);
        }

        // Check duplicate
        $chk = $pdo->prepare("SELECT registration_id FROM registrations WHERE student_id = ? AND event_id = ?");
        $chk->execute([$studentId, $eventId]);
        if ($chk->fetch()) {
            json_response(false, null, 'You are already registered for this event.', 409);
        }

        $ins = $pdo->prepare("INSERT INTO registrations (student_id, event_id, status, registered_at) VALUES (?, ?, 'confirmed', NOW())");
        $ins->execute([$studentId, $eventId]);
        $regId = $pdo->lastInsertId();

        log_audit($studentId, $userRole, 'API_EVENT_REGISTERED', 'registrations', (string)$eventId, [
            'event' => $event['title']
        ]);

        json_response(true, ['registration_id' => $regId], "Registered for '{$event['title']}' successfully!", 201);
    }

    // -------------------------------------------------------------------------
    // DELETE: Cancel Registration
    // -------------------------------------------------------------------------
    elseif ($method === 'DELETE') {
        verify_api_csrf();
        $input   = get_json_input();
        $eventId = (int)($_GET['event_id'] ?? $input['event_id'] ?? 0);

        if ($eventId <= 0) {
            json_response(false, null, 'Invalid event_id.', 400);
        }

        $del = $pdo->prepare("DELETE FROM registrations WHERE student_id = ? AND event_id = ?");
        $del->execute([$studentId, $eventId]);

        log_audit($studentId, $userRole, 'API_EVENT_CANCELLED', 'registrations', (string)$eventId, 'Cancelled registration via API');

        json_response(true, ['event_id' => $eventId], 'Registration cancelled.');
    }

    else {
        json_response(false, null, "Method {$method} not allowed.", 405);
    }

} catch (PDOException $e) {
    error_log('[StudentHub Reg API Error] ' . $e->getMessage());
    json_response(false, null, 'Database query failed.', 500);
}
