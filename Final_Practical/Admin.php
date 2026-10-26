<?php
/**
 * StudentHub Portal - Central Administrative Dashboard
 * Practical 14: Admin Dashboard, Live Metrics, Dynamic Navigation & Activity Stream
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Route Guard: Admin only
require_role('admin');

$pdo  = getDB();
$user = get_logged_user();

// Retrieve KPI Metrics from Database
$totalStudents      = 0;
$totalFaculty       = 0;
$totalEvents        = 0;
$totalRegistrations = 0;
$totalAuditLogs     = 0;
$deptDistribution   = [];
$eventRegistrations = [];
$recentLogs         = [];

try {
    // 1. Total counts
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student'")->fetchColumn();
    $totalFaculty  = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE role = 'faculty'")->fetchColumn();
    $totalEvents   = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE status != 'cancelled'")->fetchColumn();
    $totalAuditLogs = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

    // 2. Department enrollment distribution
    $deptStmt = $pdo->query(
        "SELECT course, COUNT(*) AS count
         FROM students
         WHERE role = 'student'
         GROUP BY course"
    );
    $deptDistribution = $deptStmt->fetchAll();

    // 3. Event registration distribution
    $evtStmt = $pdo->query(
        "SELECT e.title, e.capacity, COUNT(r.registration_id) AS registered
         FROM events e
         LEFT JOIN registrations r ON e.event_id = r.event_id AND r.status != 'cancelled'
         GROUP BY e.event_id
         ORDER BY e.event_date ASC
         LIMIT 6"
    );
    $eventRegistrations = $evtStmt->fetchAll();

    // 4. Recent audit activity stream
    $logStmt = $pdo->query(
        "SELECT log_id, user_id, role, action, entity, details, ip_address, created_at
         FROM audit_logs
         ORDER BY created_at DESC
         LIMIT 8"
    );
    $recentLogs = $logStmt->fetchAll();

} catch (PDOException $e) {
    error_log('[StudentHub Admin Dashboard Error] ' . $e->getMessage());
}

$pageTitle = 'Admin Executive Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="admin-container">
        <!-- Top Executive Welcome Banner -->
        <div class="admin-welcome">
            <div>
                <h2>University Portal Administration</h2>
                <p>Academic Year 2026-27 &bull; CSPIT &bull; CHARUSAT Campus Central Management</p>
            </div>
            <div class="admin-quick-links">
                <a href="manage_students.php" class="btn btn-primary">+ Manage Students</a>
                <a href="manage_events.php" class="btn btn-secondary">+ Manage Events</a>
                <a href="audit_logs.php" class="btn btn-outline">Audit Trail &rarr;</a>
            </div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon icon-students">🎓</div>
                <div class="kpi-data">
                    <span class="kpi-number"><?= $totalStudents ?></span>
                    <span class="kpi-title">Enrolled Students</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon icon-faculty">👨‍🏫</div>
                <div class="kpi-data">
                    <span class="kpi-number"><?= $totalFaculty ?></span>
                    <span class="kpi-title">Faculty Members</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon icon-events">🎪</div>
                <div class="kpi-data">
                    <span class="kpi-number"><?= $totalEvents ?></span>
                    <span class="kpi-title">Active Events</span>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon icon-registrations">🎟️</div>
                <div class="kpi-data">
                    <span class="kpi-number"><?= $totalRegistrations ?></span>
                    <span class="kpi-title">Event Registrations</span>
                </div>
            </div>
        </div>

        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <!-- Department Enrollments Distribution -->
            <div class="analytics-card">
                <h3>Department Enrollment Breakdown</h3>
                <div class="bar-chart-list">
                    <?php
                    $maxDept = 1;
                    foreach ($deptDistribution as $d) {
                        if ($d['count'] > $maxDept) $maxDept = $d['count'];
                    }
                    foreach ($deptDistribution as $d):
                        $pct = round(($d['count'] / max(1, $maxDept)) * 100);
                    ?>
                        <div class="bar-item">
                            <div class="bar-labels">
                                <span class="bar-name"><?= htmlspecialchars($d['course'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="bar-val"><strong><?= (int)$d['count'] ?></strong> students</span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill fill-blue" style="width: <?= $pct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Campus Event Registrations Progress -->
            <div class="analytics-card">
                <h3>Campus Events Capacity Quotas</h3>
                <div class="bar-chart-list">
                    <?php foreach ($eventRegistrations as $er):
                        $capPct = min(100, round(($er['registered'] / max(1, $er['capacity'])) * 100));
                    ?>
                        <div class="bar-item">
                            <div class="bar-labels">
                                <span class="bar-name"><?= htmlspecialchars($er['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="bar-val"><strong><?= (int)$er['registered'] ?></strong> / <?= (int)$er['capacity'] ?></span>
                            </div>
                            <div class="bar-track">
                                <div class="bar-fill fill-teal" style="width: <?= $capPct ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent System Activity Stream -->
        <div class="activity-section">
            <div class="activity-header">
                <h3>Security &amp; CRUD Activity Trail</h3>
                <a href="audit_logs.php" style="color: #2563eb; font-size: 13px; font-weight: 600; text-decoration: none;">View All <?= $totalAuditLogs ?> Audit Logs &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User Identifier</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentLogs)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #64748b; padding: 25px;">No activity logged yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <tr>
                                    <td><small><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></small></td>
                                    <td><strong><?= htmlspecialchars($log['user_id'] ?? 'SYSTEM', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="role-pill role-<?= strtolower($log['role'] ?? 'guest') ?>"><?= ucfirst(htmlspecialchars($log['role'] ?? 'Guest', ENT_QUOTES, 'UTF-8')) ?></span></td>
                                    <td><code><?= htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td><?= htmlspecialchars($log['entity'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="details-snippet"><?= htmlspecialchars(mb_substr($log['details'] ?? '', 0, 70), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><small><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<style>
.admin-container {
    max-width: 1180px;
    margin: 20px auto;
    text-align: left;
}
.admin-welcome {
    background: #ffffff;
    padding: 24px 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    border-left: 5px solid #2563eb;
}
.admin-welcome h2 { margin: 0 0 5px 0; color: #1e3a8a; }
.admin-welcome p { margin: 0; color: #64748b; font-size: 14px; }
.admin-quick-links { display: flex; gap: 10px; flex-wrap: wrap; }
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}
@media (max-width: 900px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 500px) {
    .kpi-grid { grid-template-columns: 1fr; }
}
.kpi-card {
    background: #ffffff;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 16px;
}
.kpi-icon {
    width: 52px; height: 52px; border-radius: 10px; display: flex;
    align-items: center; justify-content: center; font-size: 26px;
}
.icon-students { background: #dbeafe; }
.icon-faculty { background: #fef3c7; }
.icon-events { background: #dcfce7; }
.icon-registrations { background: #e0f2fe; }
.kpi-data { display: flex; flex-direction: column; }
.kpi-number { font-size: 28px; font-weight: 700; color: #1e293b; line-height: 1.1; }
.kpi-title { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 25px;
}
@media (max-width: 800px) {
    .analytics-grid { grid-template-columns: 1fr; }
}
.analytics-card {
    background: #ffffff;
    padding: 24px 28px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}
.analytics-card h3 {
    margin: 0 0 18px 0;
    color: #1e3a8a;
    font-size: 16px;
    border-bottom: 2px solid #55b7cf;
    padding-bottom: 10px;
}
.bar-chart-list { display: flex; flex-direction: column; gap: 14px; }
.bar-labels { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 5px; }
.bar-name { color: #334155; font-weight: 500; }
.bar-val { color: #0f172a; }
.bar-track { width: 100%; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 4px; }
.fill-blue { background: #2563eb; }
.fill-teal { background: #0d9488; }
.activity-section {
    background: #ffffff;
    padding: 24px 28px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
}
.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    border-bottom: 2px solid #55b7cf;
    padding-bottom: 10px;
}
.activity-header h3 { margin: 0; color: #1e3a8a; font-size: 16px; }
.table-responsive { overflow-x: auto; }
.activity-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.activity-table th { background: #f8fafc; color: #475569; font-weight: 700; text-align: left; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
.activity-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.activity-table tr:hover { background-color: #f8fafc; }
.details-snippet { color: #64748b; font-size: 11px; max-width: 250px; }
.role-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; }
.role-student { background: #dbeafe; color: #1e40af; }
.role-faculty { background: #fef3c7; color: #92400e; }
.role-admin { background: #fee2e2; color: #991b1b; }
.role-system { background: #f1f5f9; color: #475569; }
.btn {
    display: inline-block; padding: 9px 16px; border-radius: 6px; font-size: 13px;
    font-weight: 600; text-decoration: none; cursor: pointer; border: none;
}
.btn-primary { background: #2563eb; color: #ffffff; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #1e293b; }
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.btn-outline:hover { background: #f1f5f9; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
