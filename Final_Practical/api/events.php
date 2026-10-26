<?php
/**
 * StudentHub Portal - Events REST API Endpoint
 * Practical 13: Asynchronous REST Operations for Campus Events
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

try {
    // -------------------------------------------------------------------------
    // GET: List or Single Event
    // -------------------------------------------------------------------------
    if ($method === 'GET') {
        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare(
                "SELECT e.*, COUNT(r.registration_id) AS attendee_count
                 FROM events e
                 LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status != 'cancelled'
                 WHERE e.event_id = ?
                 GROUP BY e.event_id"
            );
            $stmt->execute([$id]);
            $event = $stmt->fetch();

            if ($event) {
                json_response(true, $event, 'Event details retrieved.');
            } else {
                json_response(false, null, 'Event not found.', 404);
            }
        }

        $category = trim($_GET['category'] ?? '');
        $status   = trim($_GET['status'] ?? 'upcoming');
        $q        = trim($_GET['q'] ?? '');

        $sql = "SELECT e.*, COUNT(r.registration_id) AS attendee_count
                FROM events e
                LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status != 'cancelled'
                WHERE 1=1";
        $params = [];

        if ($status !== 'all') {
            $sql .= " AND e.status = ?";
            $params[] = $status;
        }

        if ($category !== '') {
            $sql .= " AND e.category = ?";
            $params[] = $category;
        }

        if ($q !== '') {
            $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
            $like = "%{$q}%";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= " GROUP BY e.event_id ORDER BY e.event_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll();

        json_response(true, [
            'events' => $events,
            'total'  => count($events)
        ], 'Events list retrieved.');
    }

    // -------------------------------------------------------------------------
    // POST: Create Event
    // -------------------------------------------------------------------------
    elseif ($method === 'POST') {
        if (!has_role(['admin', 'faculty'])) {
            json_response(false, null, 'Administrative privileges required.', 403);
        }
        verify_api_csrf();

        $input       = get_json_input();
        $title       = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $category    = trim($input['category'] ?? 'Workshop');
        $eventDate   = trim($input['event_date'] ?? '');
        $venue       = trim($input['venue'] ?? '');
        $capacity    = (int)($input['capacity'] ?? 100);

        if ($title === "" || $description === "" || $eventDate === "" || $venue === "") {
            json_response(false, null, 'Required fields missing.', 422);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO events (title, description, category, event_date, venue, capacity, status, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'upcoming', ?, NOW())"
        );
        $stmt->execute([$title, $description, $category, $eventDate, $venue, $capacity, $_SESSION['user_id'] ?? 'API']);
        $newId = $pdo->lastInsertId();

        log_audit($_SESSION['user_id'] ?? 'API', $_SESSION['role'] ?? 'faculty', 'API_EVENT_CREATED', 'events', (string)$newId, [
            'title' => $title
        ]);

        json_response(true, ['event_id' => $newId], 'Event created successfully.', 201);
    }

    // -------------------------------------------------------------------------
    // DELETE: Delete Event
    // -------------------------------------------------------------------------
    elseif ($method === 'DELETE') {
        if (!has_role(['admin', 'faculty'])) {
            json_response(false, null, 'Access denied.', 403);
        }
        verify_api_csrf();

        $input   = get_json_input();
        $eventId = (int)($_GET['id'] ?? $input['event_id'] ?? 0);

        if ($eventId <= 0) {
            json_response(false, null, 'Invalid event_id.', 400);
        }

        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->execute([$eventId]);

        log_audit($_SESSION['user_id'] ?? 'API', $_SESSION['role'] ?? 'faculty', 'API_EVENT_DELETED', 'events', (string)$eventId, 'Deleted event via API');

        json_response(true, ['event_id' => $eventId], 'Event removed successfully.');
    }

    else {
        json_response(false, null, "Method {$method} not allowed.", 405);
    }

} catch (PDOException $e) {
    error_log('[StudentHub Events API Error] ' . $e->getMessage());
    json_response(false, null, 'Database query failed.', 500);
}
