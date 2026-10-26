<?php
/**
 * StudentHub Portal - Stored Student Registry
 * Practical 7: Stored Record Inspection Interface
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$dataDir  = DATA_DIR;
$jsonFile = $dataDir . DIRECTORY_SEPARATOR . 'registrations.json';
$csvFile  = $dataDir . DIRECTORY_SEPARATOR . 'students.csv';

$jsonRecords = [];
$csvRecords  = [];

// 1. Read JSON Storage Records
if (file_exists($jsonFile)) {
    $rawJson = @file_get_contents($jsonFile);
    if ($rawJson !== false && trim($rawJson) !== "") {
        $decoded = json_decode($rawJson, true);
        if (is_array($decoded)) {
            $jsonRecords = $decoded;
        }
    }
}

// 2. Read CSV Storage Records
if (file_exists($csvFile)) {
    $fp = @fopen($csvFile, "r");
    if ($fp !== false) {
        $header = fgetcsv($fp); // Read headers
        while (($row = fgetcsv($fp)) !== false) {
            if (!empty($row[0])) {
                $csvRecords[] = [
                    "student_id"    => $row[0] ?? "",
                    "full_name"     => $row[1] ?? "",
                    "email"         => $row[2] ?? "",
                    "mobile"        => $row[3] ?? "",
                    "gender"        => $row[4] ?? "",
                    "year"          => $row[5] ?? "",
                    "semester"      => $row[6] ?? "",
                    "course"        => $row[7] ?? "",
                    "role"          => $row[8] ?? "",
                    "password_hash" => $row[9] ?? "",
                    "registered_at" => $row[10] ?? "N/A",
                ];
            }
        }
        fclose($fp);
    }
}

$activeTab = $_GET['format'] ?? 'all';
$displayRecords = ($activeTab === 'csv') ? $csvRecords : $jsonRecords;
$totalCount = count($displayRecords);

$pageTitle = 'Student Registry';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="registry-container">
        <div class="registry-header">
            <div>
                <h2>Student Registration Records</h2>
                <span class="count-badge"><?= $totalCount ?> Registered Record<?= $totalCount === 1 ? '' : 's' ?></span>
            </div>
            <div class="registry-controls">
                <input type="text" id="filterInput" class="search-input" placeholder="Search by name, ID or department..." onkeyup="filterRegistryTable()">
                <a href="Register.php" class="btn btn-primary">+ Register Student</a>
            </div>
        </div>

        <div class="view-tabs">
            <a href="view_students.php?format=json" class="tab-item <?= ($activeTab !== 'csv') ? 'active' : '' ?>">Primary Registry (<?= count($jsonRecords) ?>)</a>
            <a href="view_students.php?format=csv" class="tab-item <?= ($activeTab === 'csv') ? 'active' : '' ?>">Export Archive (<?= count($csvRecords) ?>)</a>
        </div>

        <div class="table-responsive">
            <table class="registry-table" id="registryTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Mobile</th>
                        <th>Department</th>
                        <th>Year &amp; Semester</th>
                        <th>Account Role</th>
                        <th>Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($displayRecords)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <p>No registration records found in this view.</p>
                                <a href="Register.php" class="btn btn-secondary">Register First Student</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($displayRecords as $index => $rec): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= htmlspecialchars($rec['student_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= htmlspecialchars($rec['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($rec['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="email-link"><?= htmlspecialchars($rec['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></a></td>
                                <td><?= htmlspecialchars($rec['mobile'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($rec['course'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($rec['year'] ?? '', ENT_QUOTES, 'UTF-8') ?> &bull; Sem <?= htmlspecialchars($rec['semester'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="role-pill role-<?= strtolower($rec['role'] ?? 'student') ?>">
                                        <?= htmlspecialchars(ucfirst($rec['role'] ?? 'Student'), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($rec['registered_at'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<style>
.registry-container {
    max-width: 1100px;
    margin: 20px auto;
    padding: 28px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    text-align: left;
}
.registry-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #55b7cf;
}
.registry-header h2 {
    margin: 0;
    color: #1e3a8a;
    display: inline-block;
    vertical-align: middle;
}
.count-badge {
    background-color: #e0f2fe;
    color: #0369a1;
    font-size: 13px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    margin-left: 10px;
    vertical-align: middle;
}
.registry-controls {
    display: flex;
    gap: 12px;
    align-items: center;
}
.search-input {
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    width: 240px;
}
.search-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
}
.view-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
}
.tab-item {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #cbd5e1;
    color: #475569;
    background: #f8fafc;
    transition: all 0.2s ease;
}
.tab-item.active {
    background-color: #30bfe3;
    color: #0f172a;
    border-color: #30bfe3;
}
.table-responsive {
    overflow-x: auto;
}
.registry-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #ffffff;
}
.registry-table th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    text-align: left;
    padding: 12px 14px;
    border-bottom: 2px solid #cbd5e1;
}
.registry-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
}
.registry-table tr:hover {
    background-color: #f8fafc;
}
.email-link {
    color: #2563eb;
    text-decoration: none;
}
.email-link:hover {
    text-decoration: underline;
}
.role-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
}
.role-student {
    background: #dbeafe;
    color: #1e40af;
}
.role-faculty {
    background: #fef3c7;
    color: #92400e;
}
.empty-state {
    text-align: center;
    padding: 40px !important;
    color: #64748b;
}
.btn {
    display: inline-block;
    padding: 9px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
}
.btn-primary {
    background-color: #2563eb;
    color: #ffffff;
}
.btn-primary:hover {
    background-color: #1d4ed8;
}
.btn-secondary {
    background-color: #e2e8f0;
    color: #1e293b;
}
</style>

<script>
function filterRegistryTable() {
    var input = document.getElementById("filterInput");
    var filter = input.value.toLowerCase();
    var table = document.getElementById("registryTable");
    var tr = table.getElementsByTagName("tr");

    for (var i = 1; i < tr.length; i++) {
        var rowText = tr[i].innerText.toLowerCase();
        tr[i].style.display = rowText.indexOf(filter) > -1 ? "" : "none";
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
