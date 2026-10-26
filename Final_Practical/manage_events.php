<?php
/**
 * StudentHub Portal - Event Management CRUD & Poster Upload
 * Practical 12: Event CRUD with Validated File Uploads & Secure Storage
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

$csrfToken = generate_csrf_token();
$uploadDir = UPLOADS_DIR;
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// -----------------------------------------------------------------------------
// POST Handling: Create, Update, Delete
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $message = "Invalid security token. Please refresh and try again.";
        $messageType = "error";
    } else {
        // --- HELPER: Validate & Upload Poster Image ---
        function handle_poster_upload(?array $file, ?string &$posterPath, array &$errors): bool {
            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                return true; // Optional file
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['poster'] = "File upload error code: " . $file['error'];
                return false;
            }

            // Check size (max 2MB)
            if ($file['size'] > MAX_POSTER_SIZE) {
                $errors['poster'] = "Poster image size exceeds the maximum limit of 2 MB.";
                return false;
            }

            // Secure MIME type inspection using finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);

            if (!in_array($mimeType, ALLOWED_POSTER_TYPES, true)) {
                $errors['poster'] = "Invalid file type. Only JPEG, PNG, and WebP images are allowed.";
                return false;
            }

            // Safe extension determination
            $extMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];
            $ext = $extMap[$mimeType] ?? 'jpg';

            // Generate safe, unpredictable filename
            $safeName = 'poster_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
            $destination = UPLOADS_DIR . DIRECTORY_SEPARATOR . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors['poster'] = "Failed to store uploaded poster on server.";
                return false;
            }

            $posterPath = 'uploads/event_posters/' . $safeName;
            return true;
        }

        // --- CREATE EVENT ---
        if ($action === 'create') {
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category    = trim($_POST['category'] ?? 'Workshop');
            $eventDate   = trim($_POST['event_date'] ?? '');
            $venue       = trim($_POST['venue'] ?? '');
            $capacity    = (int)($_POST['capacity'] ?? 100);
            $status      = trim($_POST['status'] ?? 'upcoming');
            $posterPath  = null;

            if ($title === "") $errors['title'] = "Event title is required.";
            if ($description === "") $errors['description'] = "Event description is required.";
            if ($eventDate === "") $errors['event_date'] = "Event date and time are required.";
            if ($venue === "") $errors['venue'] = "Venue is required.";
            if ($capacity <= 0) $errors['capacity'] = "Capacity must be at least 1 attendee.";

            handle_poster_upload($_FILES['poster'] ?? null, $posterPath, $errors);

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO events (title, description, category, event_date, venue, poster_path, capacity, status, created_by, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $stmt->execute([$title, $description, $category, $eventDate, $venue, $posterPath, $capacity, $status, $user['id']]);
                    $newEventId = $pdo->lastInsertId();

                    log_audit($user['id'], $user['role'], 'EVENT_CREATED', 'events', (string)$newEventId, [
                        'title' => $title, 'category' => $category, 'date' => $eventDate
                    ]);

                    $message = "Campus event '{$title}' published successfully.";
                    $messageType = "success";
                } catch (PDOException $e) {
                    error_log('[StudentHub Event Create Error] ' . $e->getMessage());
                    $message = "Database error while creating event.";
                    $messageType = "error";
                }
            } else {
                $message = "Please check the highlighted errors and try again.";
                $messageType = "error";
            }
        }

        // --- UPDATE EVENT ---
        elseif ($action === 'update') {
            $eventId     = (int)($_POST['event_id'] ?? 0);
            $title       = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category    = trim($_POST['category'] ?? 'Workshop');
            $eventDate   = trim($_POST['event_date'] ?? '');
            $venue       = trim($_POST['venue'] ?? '');
            $capacity    = (int)($_POST['capacity'] ?? 100);
            $status      = trim($_POST['status'] ?? 'upcoming');
            $newPoster   = null;

            if ($title === "") $errors['title'] = "Event title is required.";
            if ($description === "") $errors['description'] = "Event description is required.";
            if ($eventDate === "") $errors['event_date'] = "Event date is required.";
            if ($venue === "") $errors['venue'] = "Venue is required.";

            // Fetch existing poster
            $curStmt = $pdo->prepare("SELECT poster_path FROM events WHERE event_id = ?");
            $curStmt->execute([$eventId]);
            $existingEvent = $curStmt->fetch();
            $posterPath = $existingEvent['poster_path'] ?? null;

            if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
                if (handle_poster_upload($_FILES['poster'], $newPoster, $errors)) {
                    // Delete old poster file if existed
                    if ($posterPath && file_exists(BASE_PATH . DIRECTORY_SEPARATOR . $posterPath)) {
                        @unlink(BASE_PATH . DIRECTORY_SEPARATOR . $posterPath);
                    }
                    $posterPath = $newPoster;
                }
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE events SET title = ?, description = ?, category = ?, event_date = ?, venue = ?, poster_path = ?, capacity = ?, status = ?
                         WHERE event_id = ?"
                    );
                    $stmt->execute([$title, $description, $category, $eventDate, $venue, $posterPath, $capacity, $status, $eventId]);

                    log_audit($user['id'], $user['role'], 'EVENT_UPDATED', 'events', (string)$eventId, [
                        'title' => $title, 'status' => $status
                    ]);

                    $message = "Event '{$title}' updated successfully.";
                    $messageType = "success";
                } catch (PDOException $e) {
                    error_log('[StudentHub Event Update Error] ' . $e->getMessage());
                    $message = "Database error while updating event.";
                    $messageType = "error";
                }
            } else {
                $message = "Please check the highlighted errors and try again.";
                $messageType = "error";
            }
        }

        // --- DELETE EVENT ---
        elseif ($action === 'delete') {
            $eventId = (int)($_POST['event_id'] ?? 0);

            try {
                // Fetch poster path to clean up storage
                $curStmt = $pdo->prepare("SELECT title, poster_path FROM events WHERE event_id = ?");
                $curStmt->execute([$eventId]);
                $eventToDelete = $curStmt->fetch();

                if ($eventToDelete) {
                    if (!empty($eventToDelete['poster_path'])) {
                        $fullPosterPath = BASE_PATH . DIRECTORY_SEPARATOR . $eventToDelete['poster_path'];
                        if (file_exists($fullPosterPath)) {
                            @unlink($fullPosterPath);
                        }
                    }

                    $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = ?");
                    $stmt->execute([$eventId]);

                    log_audit($user['id'], $user['role'], 'EVENT_DELETED', 'events', (string)$eventId, [
                        'title' => $eventToDelete['title']
                    ]);

                    $message = "Event '{$eventToDelete['title']}' was removed successfully.";
                    $messageType = "success";
                }
            } catch (PDOException $e) {
                error_log('[StudentHub Event Delete Error] ' . $e->getMessage());
                $message = "Database error while removing event.";
                $messageType = "error";
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Fetch Events with Attendee Counts
// -----------------------------------------------------------------------------
$searchQuery    = trim($_GET['q'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');

$sql = "SELECT e.*, COUNT(r.registration_id) AS attendee_count
        FROM events e
        LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status != 'cancelled'
        WHERE 1=1";
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
$eventsList = $stmt->fetchAll();
$totalEvents = count($eventsList);

$pageTitle = 'Event Management';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="crud-container">
        <div class="crud-header">
            <div>
                <h2>Campus Events &amp; Workshops Management</h2>
                <span class="count-badge"><?= $totalEvents ?> Event<?= $totalEvents === 1 ? '' : 's' ?></span>
            </div>
            <div class="crud-actions">
                <button type="button" class="btn btn-primary" onclick="openAddEventModal()">+ Create New Event</button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $messageType ?>" role="alert">
                <span><?= ($messageType === 'success') ? '✓' : '⚠' ?></span>
                <div><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <form method="GET" action="manage_events.php" class="filter-bar">
            <div class="filter-group">
                <input type="text" name="q" placeholder="Search events by title or venue..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="input-search">
            </div>

            <div class="filter-group">
                <select name="category" onchange="this.form.submit()" class="select-filter">
                    <option value="">All Categories</option>
                    <option value="Technical" <?= ($filterCategory === 'Technical') ? 'selected' : '' ?>>Technical</option>
                    <option value="Workshop" <?= ($filterCategory === 'Workshop') ? 'selected' : '' ?>>Workshop</option>
                    <option value="Seminar" <?= ($filterCategory === 'Seminar') ? 'selected' : '' ?>>Seminar</option>
                    <option value="Cultural" <?= ($filterCategory === 'Cultural') ? 'selected' : '' ?>>Cultural</option>
                    <option value="Sports" <?= ($filterCategory === 'Sports') ? 'selected' : '' ?>>Sports</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if (!empty($searchQuery) || !empty($filterCategory)): ?>
                <a href="manage_events.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>

        <!-- Events Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Poster</th>
                        <th>Event Title</th>
                        <th>Category</th>
                        <th>Date &amp; Time</th>
                        <th>Venue</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventsList)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <p>No events found matching your criteria.</p>
                                <a href="manage_events.php" class="btn btn-secondary">Clear Filters</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eventsList as $evt): ?>
                            <tr>
                                <td style="width: 70px;">
                                    <?php if (!empty($evt['poster_path']) && file_exists(BASE_PATH . DIRECTORY_SEPARATOR . $evt['poster_path'])): ?>
                                        <img src="<?= htmlspecialchars($evt['poster_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Poster" class="table-poster-thumb">
                                    <?php else: ?>
                                        <div class="poster-placeholder">📅</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($evt['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <div class="event-desc-snippet"><?= htmlspecialchars(mb_substr($evt['description'], 0, 75), ENT_QUOTES, 'UTF-8') ?>...</div>
                                </td>
                                <td>
                                    <span class="badge-cat"><?= htmlspecialchars($evt['category'], ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td><?= date('M d, Y h:i A', strtotime($evt['event_date'])) ?></td>
                                <td><?= htmlspecialchars($evt['venue'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <strong><?= (int)$evt['attendee_count'] ?></strong> / <?= (int)$evt['capacity'] ?>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width: <?= min(100, round(($evt['attendee_count'] / max(1, $evt['capacity'])) * 100)) ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-pill status-<?= strtolower($evt['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($evt['status'], ENT_QUOTES, 'UTF-8')) ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <button type="button" class="btn-action-edit" onclick='openEditEventModal(<?= json_encode($evt) ?>)'>Edit</button>
                                    <button type="button" class="btn-action-delete" onclick="confirmDeleteEvent(<?= $evt['event_id'] ?>, '<?= htmlspecialchars(addslashes($evt['title']), ENT_QUOTES, 'UTF-8') ?>')">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Create Event -->
    <div id="addEventModal" class="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Create New Campus Event</h3>
                <button type="button" class="close-btn" onclick="closeAddEventModal()">&times;</button>
            </div>
            <form action="manage_events.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="create_title">Event Title <span class="required">*</span></label>
                        <input type="text" id="create_title" name="title" placeholder="e.g. AI & Robotics Symposium 2026" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="create_desc">Event Description <span class="required">*</span></label>
                        <textarea id="create_desc" name="description" rows="3" placeholder="Provide event schedule, goals, and details..." required></textarea>
                    </div>

                    <div class="modal-grid">
                        <div class="form-group">
                            <label for="create_cat">Category</label>
                            <select id="create_cat" name="category">
                                <option value="Workshop">Workshop</option>
                                <option value="Technical">Technical</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Sports">Sports</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="create_date">Date &amp; Time <span class="required">*</span></label>
                            <input type="datetime-local" id="create_date" name="event_date" required>
                        </div>

                        <div class="form-group">
                            <label for="create_venue">Venue <span class="required">*</span></label>
                            <input type="text" id="create_venue" name="venue" placeholder="e.g. Seminar Hall B" required>
                        </div>

                        <div class="form-group">
                            <label for="create_cap">Seat Capacity</label>
                            <input type="number" id="create_cap" name="capacity" value="100" min="1" max="1000" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 14px;">
                        <label for="create_poster">Poster Image Upload (JPG, PNG, WebP &bull; Max 2MB)</label>
                        <input type="file" id="create_poster" name="poster" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Publish Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Edit Event -->
    <div id="editEventModal" class="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Edit Event Details</h3>
                <button type="button" class="close-btn" onclick="closeEditEventModal()">&times;</button>
            </div>
            <form action="manage_events.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="edit_event_id" name="event_id">

                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="edit_title">Event Title <span class="required">*</span></label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label for="edit_desc">Description <span class="required">*</span></label>
                        <textarea id="edit_desc" name="description" rows="3" required></textarea>
                    </div>

                    <div class="modal-grid">
                        <div class="form-group">
                            <label for="edit_cat">Category</label>
                            <select id="edit_cat" name="category">
                                <option value="Workshop">Workshop</option>
                                <option value="Technical">Technical</option>
                                <option value="Seminar">Seminar</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Sports">Sports</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_date">Date &amp; Time</label>
                            <input type="datetime-local" id="edit_date" name="event_date" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_venue">Venue</label>
                            <input type="text" id="edit_venue" name="venue" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_cap">Capacity</label>
                            <input type="number" id="edit_cap" name="capacity" min="1" max="1000" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_status">Event Status</label>
                            <select id="edit_status" name="status">
                                <option value="upcoming">Upcoming</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 14px;">
                        <label for="edit_poster">Replace Poster Image (Optional &bull; Max 2MB)</label>
                        <input type="file" id="edit_poster" name="poster" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditEventModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Event Form -->
    <form id="deleteEventForm" action="manage_events.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="hidden" id="delete_event_id" name="event_id">
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
.crud-header h2 { margin: 0; color: #1e3a8a; display: inline-block; vertical-align: middle; }
.count-badge {
    background-color: #e0f2fe; color: #0369a1; font-size: 13px; font-weight: 700;
    padding: 4px 10px; border-radius: 12px; margin-left: 10px; vertical-align: middle;
}
.table-poster-thumb {
    width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;
}
.poster-placeholder {
    width: 60px; height: 60px; background: #f1f5f9; border-radius: 6px; display: flex;
    align-items: center; justify-content: center; font-size: 24px; border: 1px solid #e2e8f0;
}
.event-desc-snippet { font-size: 11px; color: #64748b; margin-top: 3px; font-weight: normal; }
.progress-bar-wrap { width: 100%; height: 5px; background: #e2e8f0; border-radius: 3px; margin-top: 4px; overflow: hidden; }
.progress-bar-fill { height: 100%; background: #2563eb; }
.badge-cat { background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.status-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.status-upcoming { background: #dcfce7; color: #15803d; }
.status-completed { background: #f1f5f9; color: #64748b; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
.actions-cell { white-space: nowrap; }
.btn-action-edit, .btn-action-delete {
    padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 4px; cursor: pointer; border: none; margin-right: 4px;
}
.btn-action-edit { background: #e0f2fe; color: #0369a1; }
.btn-action-edit:hover { background: #0284c7; color: #ffffff; }
.btn-action-delete { background: #fee2e2; color: #991b1b; }
.btn-action-delete:hover { background: #dc2626; color: #ffffff; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th { background: #f1f5f9; color: #334155; font-weight: 700; text-align: left; padding: 12px 14px; border-bottom: 2px solid #cbd5e1; }
.data-table td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #1e293b; vertical-align: middle; }
.data-table tr:hover { background-color: #f8fafc; }
.filter-bar { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; background: #f8fafc; padding: 14px 18px; border-radius: 8px; border: 1px solid #e2e8f0; }
.input-search { padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; min-width: 250px; }
.select-filter { padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; background: #ffffff; }
.btn { display: inline-block; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
.btn-primary { background-color: #2563eb; color: #ffffff; }
.btn-primary:hover { background-color: #1d4ed8; }
.btn-secondary { background-color: #e2e8f0; color: #1e293b; }
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.alert-box { padding: 12px 18px; border-radius: 6px; font-size: 14px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; }
.alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.crud-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.crud-modal.show { display: flex; }
.modal-dialog { background: #ffffff; width: 90%; max-width: 650px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; animation: modalSlide 0.25s ease; }
@keyframes modalSlide { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { background: #f8fafc; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; color: #1e3a8a; font-size: 17px; }
.close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
.modal-body { padding: 20px; }
.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.modal-footer { background: #f8fafc; padding: 14px 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; }
.form-group { display: flex; flex-direction: column; }
.form-group label { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
.required { color: #dc2626; }
.form-group input, .form-group select, .form-group textarea { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }
</style>

<script>
function openAddEventModal() {
    document.getElementById("addEventModal").classList.add("show");
}
function closeAddEventModal() {
    document.getElementById("addEventModal").classList.remove("show");
}
function openEditEventModal(evt) {
    document.getElementById("edit_event_id").value = evt.event_id;
    document.getElementById("edit_title").value = evt.title;
    document.getElementById("edit_desc").value = evt.description;
    document.getElementById("edit_cat").value = evt.category;
    // Format date string for datetime-local input
    if (evt.event_date) {
        var d = new Date(evt.event_date);
        var formatted = d.toISOString().slice(0, 16);
        document.getElementById("edit_date").value = formatted;
    }
    document.getElementById("edit_venue").value = evt.venue;
    document.getElementById("edit_cap").value = evt.capacity;
    document.getElementById("edit_status").value = evt.status;
    document.getElementById("editEventModal").classList.add("show");
}
function closeEditEventModal() {
    document.getElementById("editEventModal").classList.remove("show");
}
function confirmDeleteEvent(eventId, title) {
    if (confirm("Are you sure you want to permanently delete event '" + title + "'? All registered student signups will be cancelled.")) {
        document.getElementById("delete_event_id").value = eventId;
        document.getElementById("deleteEventForm").submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
