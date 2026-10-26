<?php
/**
 * StudentHub Portal - Faculty Dashboard
 * Practical 10 & 14: Authenticated Faculty Administrative Workspace
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Route Guard: Require faculty or admin role
require_role(['faculty', 'admin']);

$pdo = getDB();
$user = get_logged_user();

// Retrieve metrics
$totalStudents = 0;
$totalEvents   = 0;
$recentRegistrations = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student'");
    $totalStudents = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'");
    $totalEvents = (int)$stmt->fetchColumn();

    // Recent event registrations
    $recentStmt = $pdo->query(
        "SELECT r.registration_id, s.full_name, s.student_id, s.course, e.title AS event_title, r.registered_at
         FROM registrations r
         JOIN students s ON r.student_id = s.student_id
         JOIN events e ON r.event_id = e.event_id
         ORDER BY r.registered_at DESC
         LIMIT 5"
    );
    $recentRegistrations = $recentStmt->fetchAll();

} catch (PDOException $e) {
    error_log('[StudentHub Faculty Dashboard DB Error] ' . $e->getMessage());
}

$pageTitle = 'Faculty Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="dashboard-container">
        <div class="welcome-banner">
            <h2>Welcome, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p>Faculty Administration &bull; Department of <?= htmlspecialchars($user['course'] ?? 'Computer Engineering', ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-content">
                    <h3><?= $totalStudents ?></h3>
                    <p>Enrolled Students</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?= $totalEvents ?></h3>
                    <p>Upcoming Campus Events</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3>Active</h3>
                    <p>Semester 3 Session</p>
                </div>
            </div>
        </div>

        <div class="action-grid">
            <div class="action-card">
                <h3>Faculty Quick Services</h3>
                <div class="button-list">
                    <a href="manage_students.php" class="btn btn-action">👥 Student Directory &amp; Records</a>
                    <a href="manage_events.php" class="btn btn-action">🎪 Organize &amp; Manage Events</a>
                    <a href="Attendance.html" class="btn btn-action">📊 Course Attendance Tracker</a>
                    <a href="Timetable.html" class="btn btn-action">🕒 Department Timetable</a>
                    <a href="Notices.html" class="btn btn-action">📢 Campus Notice Board</a>
                </div>
            </div>

            <div class="action-card">
                <h3>Recent Event Registrations</h3>
                <?php if (empty($recentRegistrations)): ?>
                    <p style="color: #64748b; font-size: 13px;">No recent student signups recorded.</p>
                <?php else: ?>
                    <table class="simple-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Event</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRegistrations as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small><?= htmlspecialchars($item['student_id'], ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($item['event_title'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small><?= date('M d, H:i', strtotime($item['registered_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.dashboard-container {
    max-width: 1040px;
    margin: 20px auto;
    text-align: left;
}
.welcome-banner {
    background: #ffffff;
    padding: 24px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 24px;
    border-left: 5px solid #2563eb;
}
.welcome-banner h2 {
    margin: 0 0 5px 0;
    color: #1e3a8a;
}
.welcome-banner p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}
@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
}
.stat-card {
    background: #ffffff;
    padding: 20px 24px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 18px;
}
.stat-icon {
    font-size: 32px;
}
.stat-content h3 {
    margin: 0 0 4px 0;
    font-size: 26px;
    color: #1e293b;
}
.stat-content p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
}
.action-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 24px;
}
@media (max-width: 820px) {
    .action-grid {
        grid-template-columns: 1fr;
    }
}
.action-card {
    background: #ffffff;
    padding: 25px 28px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}
.action-card h3 {
    margin-top: 0;
    margin-bottom: 18px;
    color: #1e3a8a;
    font-size: 17px;
    border-bottom: 2px solid #55b7cf;
    padding-bottom: 10px;
}
.button-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.btn-action {
    display: block;
    background: #f8fafc;
    color: #1e293b;
    border: 1px solid #cbd5e1;
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}
.btn-action:hover {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
.simple-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.simple-table th {
    text-align: left;
    padding: 8px 10px;
    border-bottom: 2px solid #e2e8f0;
    color: #475569;
}
.simple-table td {
    padding: 10px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
