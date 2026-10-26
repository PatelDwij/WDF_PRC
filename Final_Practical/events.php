<?php
/**
 * StudentHub Portal - Campus Events & Student Registration
 * Practicals 12 & 13: Event Discovery & Real-Time Student Event Registration
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/audit.php';

$pdo = getDB();
$isLoggedIn = is_logged_in();
$user       = get_logged_user();
$csrfToken  = generate_csrf_token();

$message     = "";
$messageType = "";

// -----------------------------------------------------------------------------
// POST Request Handling: Event Registration & Cancellation
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) {
        header('Location: Login.php?msg=login_required&redirect=' . urlencode('events.php'));
        exit;
    }

    $action  = $_POST['action'] ?? '';
    $eventId = (int)($_POST['event_id'] ?? 0);
    $token   = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $message = "Invalid security token. Please refresh the page.";
        $messageType = "error";
    } elseif ($eventId <= 0) {
        $message = "Invalid event selected.";
        $messageType = "error";
    } else {
        $studentId = $user['id'];

        // --- REGISTER FOR EVENT ---
        if ($action === 'register') {
            try {
                // Fetch event details and capacity
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
                    $message = "Event not found.";
                    $messageType = "error";
                } elseif ($event['status'] !== 'upcoming') {
                    $message = "This event is no longer accepting new registrations.";
                    $messageType = "error";
                } elseif ($event['attendee_count'] >= $event['capacity']) {
                    $message = "Registration Closed: This event has reached full capacity.";
                    $messageType = "error";
                } else {
                    // Check if already registered
                    $chkStmt = $pdo->prepare("SELECT registration_id FROM registrations WHERE student_id = ? AND event_id = ?");
                    $chkStmt->execute([$studentId, $eventId]);
                    if ($chkStmt->fetch()) {
                        $message = "You are already registered for this event.";
                        $messageType = "error";
                    } else {
                        $insertStmt = $pdo->prepare(
                            "INSERT INTO registrations (student_id, event_id, status, registered_at)
                             VALUES (?, ?, 'confirmed', NOW())"
                        );
                        $insertStmt->execute([$studentId, $eventId]);

                        log_audit($studentId, $user['role'], 'EVENT_REGISTERED', 'registrations', (string)$eventId, [
                            'event_title' => $event['title']
                        ]);

                        $message = "You have registered for '{$event['title']}' successfully!";
                        $messageType = "success";
                    }
                }
            } catch (PDOException $e) {
                error_log('[StudentHub Event Reg Error] ' . $e->getMessage());
                $message = "Unable to complete registration. Please try again.";
                $messageType = "error";
            }
        }

        // --- CANCEL REGISTRATION ---
        elseif ($action === 'cancel') {
            try {
                $delStmt = $pdo->prepare("DELETE FROM registrations WHERE student_id = ? AND event_id = ?");
                $delStmt->execute([$studentId, $eventId]);

                log_audit($studentId, $user['role'], 'EVENT_CANCELLED', 'registrations', (string)$eventId, 'Cancelled event registration');

                $message = "Your registration for this event has been cancelled.";
                $messageType = "success";
            } catch (PDOException $e) {
                error_log('[StudentHub Event Cancel Error] ' . $e->getMessage());
                $message = "Unable to cancel registration. Please try again.";
                $messageType = "error";
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Fetch Events & Current Student Registrations
// -----------------------------------------------------------------------------
$searchQuery    = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');

$sql = "SELECT e.*, COUNT(r.registration_id) AS attendee_count
        FROM events e
        LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status != 'cancelled'
        WHERE e.status = 'upcoming'";
$params = [];

if ($searchQuery !== "") {
    $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $like = "%{$searchQuery}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

if ($filterCategory !== "") {
    $sql .= " AND e.category = ?";
    $params[] = $filterCategory;
}

$sql .= " GROUP BY e.event_id ORDER BY e.event_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Track student's registered event IDs
$myRegisteredEventIds = [];
if ($isLoggedIn) {
    $myStmt = $pdo->prepare("SELECT event_id FROM registrations WHERE student_id = ? AND status != 'cancelled'");
    $myStmt->execute([$user['id']]);
    $myRegisteredEventIds = $myStmt->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = 'Campus Events';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="events-container">
        <div class="events-header">
            <div>
                <h2>Campus Events &amp; Technical Workshops</h2>
                <p>Explore institutional hackathons, technical symposiums, hands-on workshops, and cultural fests.</p>
            </div>
            <?php if (has_role(['admin', 'faculty'])): ?>
                <div>
                    <a href="manage_events.php" class="btn btn-primary">Manage Events CRUD &rarr;</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $messageType ?>" role="alert">
                <span><?= ($messageType === 'success') ? '✓' : '⚠' ?></span>
                <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <form method="GET" action="events.php" class="events-filter-bar">
            <input type="text" name="q" placeholder="Search events by title, topic, or venue..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="input-search">

            <select name="category" onchange="this.form.submit()" class="select-filter">
                <option value="">All Categories</option>
                <option value="Technical" <?= ($filterCategory === 'Technical') ? 'selected' : '' ?>>Technical</option>
                <option value="Workshop" <?= ($filterCategory === 'Workshop') ? 'selected' : '' ?>>Workshop</option>
                <option value="Seminar" <?= ($filterCategory === 'Seminar') ? 'selected' : '' ?>>Seminar</option>
                <option value="Cultural" <?= ($filterCategory === 'Cultural') ? 'selected' : '' ?>>Cultural</option>
                <option value="Sports" <?= ($filterCategory === 'Sports') ? 'selected' : '' ?>>Sports</option>
            </select>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if (!empty($searchQuery) || !empty($filterCategory)): ?>
                <a href="events.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Events Cards Grid -->
        <?php if (empty($events)): ?>
            <div class="no-events-box">
                <h3>No upcoming events found</h3>
                <p>Try clearing your search filters or check back later for new announcements.</p>
                <a href="events.php" class="btn btn-secondary">View All Events</a>
            </div>
        <?php else: ?>
            <div class="events-grid">
                <?php foreach ($events as $evt):
                    $isRegistered = in_array((int)$evt['event_id'], $myRegisteredEventIds, true);
                    $isFull       = ((int)$evt['attendee_count'] >= (int)$evt['capacity']);
                    $spotsLeft    = max(0, (int)$evt['capacity'] - (int)$evt['attendee_count']);
                ?>
                    <div class="event-card">
                        <div class="event-card-media">
                            <?php if (!empty($evt['poster_path']) && file_exists(BASE_PATH . DIRECTORY_SEPARATOR . $evt['poster_path'])): ?>
                                <img src="<?= htmlspecialchars($evt['poster_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($evt['title'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="event-media-fallback">
                                    <span>📅</span>
                                    <span><?= htmlspecialchars($evt['category'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>
                            <span class="category-tag"><?= htmlspecialchars($evt['category'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="event-card-body">
                            <h3><?= htmlspecialchars($evt['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="event-desc"><?= htmlspecialchars($evt['description'], ENT_QUOTES, 'UTF-8') ?></p>

                            <div class="event-details-list">
                                <div class="detail-item">
                                    <span class="detail-icon">📅</span>
                                    <span><?= date('l, F j, Y &bull; g:i A', strtotime($evt['event_date'])) ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">📍</span>
                                    <span><?= htmlspecialchars($evt['venue'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-icon">👥</span>
                                    <span><strong><?= (int)$evt['attendee_count'] ?></strong> registered &bull; <?= $spotsLeft ?> seats remaining</span>
                                </div>
                            </div>

                            <div class="capacity-bar">
                                <div class="capacity-fill" style="width: <?= min(100, round(($evt['attendee_count'] / max(1, $evt['capacity'])) * 100)) ?>%;"></div>
                            </div>
                        </div>

                        <div class="event-card-footer">
                            <?php if (!$isLoggedIn): ?>
                                <a href="Login.php?redirect=events.php" class="btn btn-secondary btn-block">Sign In to Register</a>
                            <?php elseif ($isRegistered): ?>
                                <div class="reg-status-wrap">
                                    <span class="badge-registered">✓ Registered</span>
                                    <form action="events.php" method="POST" style="margin: 0;" onsubmit="return confirm('Cancel registration for this event?');">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="event_id" value="<?= $evt['event_id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                        <button type="submit" class="btn-cancel">Cancel</button>
                                    </form>
                                </div>
                            <?php elseif ($isFull): ?>
                                <button type="button" class="btn btn-disabled btn-block" disabled>Fully Booked</button>
                            <?php else: ?>
                                <form action="events.php" method="POST" style="margin: 0; width: 100%;">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="event_id" value="<?= $evt['event_id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" class="btn btn-primary btn-block">Register for Event</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<style>
.events-container {
    max-width: 1140px;
    margin: 20px auto;
    text-align: left;
}
.events-header {
    background: #ffffff;
    padding: 24px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.events-header h2 { margin: 0 0 6px 0; color: #1e3a8a; }
.events-header p { margin: 0; color: #64748b; font-size: 14px; }
.events-filter-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 25px;
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
    flex-grow: 1;
    min-width: 250px;
}
.select-filter {
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    background: #ffffff;
}
.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 25px;
}
.event-card {
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.event-card-media {
    position: relative;
    height: 170px;
    background: #f1f5f9;
}
.event-card-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.event-media-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 32px;
    gap: 6px;
}
.event-media-fallback span:last-child {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}
.category-tag {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(30, 58, 138, 0.9);
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: uppercase;
}
.event-card-body {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
.event-card-body h3 {
    margin: 0 0 10px 0;
    color: #1e3a8a;
    font-size: 18px;
    line-height: 1.4;
}
.event-desc {
    color: #475569;
    font-size: 13px;
    line-height: 1.5;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.event-details-list {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
    color: #334155;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}
.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.detail-icon { font-size: 14px; }
.capacity-bar {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    margin-top: 10px;
    overflow: hidden;
}
.capacity-fill {
    height: 100%;
    background: #10b981;
}
.event-card-footer {
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}
.btn-block {
    width: 100%;
    box-sizing: border-box;
    text-align: center;
}
.reg-status-wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}
.badge-registered {
    background: #dcfce7;
    color: #15803d;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
}
.btn-cancel {
    background: none;
    border: none;
    color: #dc2626;
    font-size: 13px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: underline;
}
.btn-cancel:hover { color: #991b1b; }
.btn-disabled {
    background: #cbd5e1;
    color: #64748b;
    cursor: not-allowed;
}
.btn {
    display: inline-block;
    padding: 10px 18px;
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
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.alert-box {
    padding: 12px 18px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px;
    display: flex; align-items: center; gap: 12px;
}
.alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.no-events-box {
    background: #ffffff; padding: 50px 30px; text-align: center; border-radius: 10px;
    border: 1px solid #e2e8f0; color: #64748b;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
