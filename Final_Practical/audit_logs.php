<?php
/**
 * StudentHub Portal - Security & Activity Audit Log Viewer
 * Practical 14: System Audit Trail & Security Event Monitoring
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Route Guard: Admin only
require_role('admin');

$pdo = getDB();

$searchQuery  = trim($_GET['q'] ?? '');
$filterAction = trim($_GET['action'] ?? '');
$filterRole   = trim($_GET['role'] ?? '');

$sql = "SELECT log_id, user_id, role, action, entity, entity_id, details, ip_address, created_at
        FROM audit_logs WHERE 1=1";
$params = [];

if ($searchQuery !== "") {
    $sql .= " AND (user_id LIKE ? OR entity_id LIKE ? OR details LIKE ?)";
    $like = "%{$searchQuery}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

if ($filterAction !== "") {
    $sql .= " AND action = ?";
    $params[] = $filterAction;
}

if ($filterRole !== "") {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}

$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$totalLogs = count($logs);

// Fetch distinct actions for filter dropdown
$distinctActions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Security Audit Trail';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="audit-container">
        <div class="audit-header">
            <div>
                <h2>System Security &amp; Activity Audit Trail</h2>
                <p>Immutable log of administrative actions, user authentication events, and data modifications.</p>
            </div>
            <div>
                <a href="Admin.php" class="btn btn-secondary">&larr; Return to Dashboard</a>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="audit_logs.php" class="audit-filter-bar">
            <input type="text" name="q" placeholder="Search by user, entity ID, or keyword..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" class="input-search">

            <select name="action" onchange="this.form.submit()" class="select-filter">
                <option value="">All Logged Actions</option>
                <?php foreach ($distinctActions as $act): ?>
                    <option value="<?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?>" <?= ($filterAction === $act) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($act, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="role" onchange="this.form.submit()" class="select-filter">
                <option value="">All Roles</option>
                <option value="admin" <?= ($filterRole === 'admin') ? 'selected' : '' ?>>Admin</option>
                <option value="faculty" <?= ($filterRole === 'faculty') ? 'selected' : '' ?>>Faculty</option>
                <option value="student" <?= ($filterRole === 'student') ? 'selected' : '' ?>>Student</option>
                <option value="guest" <?= ($filterRole === 'guest') ? 'selected' : '' ?>>Guest</option>
                <option value="system" <?= ($filterRole === 'system') ? 'selected' : '' ?>>System</option>
            </select>

            <button type="submit" class="btn btn-secondary">Filter</button>
            <?php if (!empty($searchQuery) || !empty($filterAction) || !empty($filterRole)): ?>
                <a href="audit_logs.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-responsive">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Timestamp</th>
                        <th>User Identifier</th>
                        <th>Role</th>
                        <th>Action Verb</th>
                        <th>Target Entity</th>
                        <th>Context Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="empty-state">No audit records found matching your filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td>#<?= $l['log_id'] ?></td>
                                <td><small><?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?></small></td>
                                <td><strong><?= htmlspecialchars($l['user_id'] ?? 'SYSTEM', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><span class="role-pill role-<?= strtolower($l['role'] ?? 'guest') ?>"><?= ucfirst(htmlspecialchars($l['role'] ?? 'Guest', ENT_QUOTES, 'UTF-8')) ?></span></td>
                                <td><code class="action-tag"><?= htmlspecialchars($l['action'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars($l['entity'], ENT_QUOTES, 'UTF-8') ?> <?= !empty($l['entity_id']) ? '(' . htmlspecialchars($l['entity_id'], ENT_QUOTES, 'UTF-8') . ')' : '' ?></td>
                                <td class="details-cell"><?= htmlspecialchars($l['details'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><small class="ip-tag"><?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
.audit-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 28px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    text-align: left;
}
.audit-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #55b7cf;
}
.audit-header h2 { margin: 0 0 5px 0; color: #1e3a8a; }
.audit-header p { margin: 0; color: #64748b; font-size: 13px; }
.audit-filter-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
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
.table-responsive { overflow-x: auto; }
.audit-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.audit-table th { background: #f1f5f9; color: #334155; font-weight: 700; text-align: left; padding: 10px 12px; border-bottom: 2px solid #cbd5e1; }
.audit-table td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; color: #1e293b; vertical-align: top; }
.audit-table tr:hover { background-color: #f8fafc; }
.details-cell { max-width: 320px; word-break: break-word; color: #475569; font-size: 11px; }
.action-tag { background: #eef2ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-weight: 600; }
.ip-tag { color: #64748b; font-family: monospace; }
.role-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 700; }
.role-student { background: #dbeafe; color: #1e40af; }
.role-faculty { background: #fef3c7; color: #92400e; }
.role-admin { background: #fee2e2; color: #991b1b; }
.role-guest { background: #f1f5f9; color: #64748b; }
.role-system { background: #e0f2fe; color: #0369a1; }
.btn {
    display: inline-block; padding: 9px 16px; border-radius: 6px; font-size: 13px;
    font-weight: 600; text-decoration: none; cursor: pointer; border: none;
}
.btn-secondary { background: #e2e8f0; color: #1e293b; }
.btn-secondary:hover { background: #cbd5e1; }
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.empty-state { text-align: center; padding: 30px !important; color: #64748b; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
