<?php
/**
 * StudentHub Portal - Interactive Asynchronous CRUD Workspace
 * Practical 13: Single-Page Dynamic CRUD with Fetch API & JSON Backend
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Route Guard: Admin & Faculty only
require_role(['admin', 'faculty']);

$user      = get_logged_user();
$csrfToken = generate_csrf_token();
$isAdmin   = ($user['role'] === 'admin');

$pageTitle = 'Interactive Asynchronous Management';
$extraCss = '<meta name="csrf-token" content="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="ajax-container">
        <div class="ajax-header">
            <div>
                <h2>Interactive Student Management</h2>
                <p>Real-time CRUD operations powered by asynchronous Fetch API without page reload.</p>
            </div>
            <div>
                <button type="button" class="btn btn-primary" onclick="openAsyncAddModal()">+ Add Student (Async)</button>
            </div>
        </div>

        <!-- Toast Notifications -->
        <div id="toastNotification" class="toast" role="status" aria-live="polite">
            <span id="toastIcon">✓</span>
            <span id="toastText">Operation complete</span>
        </div>

        <!-- Filter & Search Controls -->
        <div class="controls-bar">
            <div class="search-input-wrap">
                <input type="text" id="liveSearchInput" placeholder="Live search by name, ID or email..." class="search-field" autocomplete="off">
                <span id="searchSpinner" class="spinner" style="display: none;"></span>
            </div>

            <select id="courseFilter" class="filter-select">
                <option value="">All Departments</option>
                <option value="Computer Engineering">Computer Engineering</option>
                <option value="Information Technology">Information Technology</option>
                <option value="Computer Science">Computer Science</option>
                <option value="Electronics Engineering">Electronics Engineering</option>
            </select>

            <select id="semFilter" class="filter-select">
                <option value="">All Semesters</option>
                <?php for ($s = 1; $s <= 8; $s++): ?>
                    <option value="<?= $s ?>">Semester <?= $s ?></option>
                <?php endfor; ?>
            </select>

            <button type="button" class="btn btn-secondary" onclick="loadStudents()">Refresh</button>
        </div>

        <!-- Loading & Empty States -->
        <div id="tableLoading" class="loading-state" style="display: none;">
            <div class="big-spinner"></div>
            <p>Loading records...</p>
        </div>

        <!-- Dynamic Records Table -->
        <div class="table-responsive">
            <table class="async-table" id="asyncStudentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email Address</th>
                        <th>Mobile</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <!-- Dynamic Rows Rendered by JavaScript -->
                </tbody>
            </table>
        </div>

        <div class="pagination-bar">
            <span id="recordCountLabel">Showing records</span>
            <div class="pagination-btns">
                <button type="button" id="prevPageBtn" class="btn btn-outline" onclick="changePage(-1)" disabled>&larr; Previous</button>
                <span id="pageIndicator" style="font-weight: 600; font-size: 13px;">Page 1</span>
                <button type="button" id="nextPageBtn" class="btn btn-outline" onclick="changePage(1)">Next &rarr;</button>
            </div>
        </div>
    </div>

    <!-- Modal: Add Student Asynchronously -->
    <div id="asyncAddModal" class="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Add New Student</h3>
                <button type="button" class="close-btn" onclick="closeAsyncAddModal()">&times;</button>
            </div>
            <form id="asyncAddForm" onsubmit="handleCreateStudent(event)">
                <div class="modal-body">
                    <div id="addModalError" class="modal-error-box" style="display: none;"></div>

                    <div class="modal-grid">
                        <div class="form-group">
                            <label for="add_student_id">Student ID <span class="required">*</span></label>
                            <input type="text" id="add_student_id" placeholder="e.g. 25CE085" required>
                        </div>
                        <div class="form-group">
                            <label for="add_full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="add_full_name" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label for="add_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="add_email" placeholder="name@studenthub.edu" required>
                        </div>
                        <div class="form-group">
                            <label for="add_mobile">Mobile Number <span class="required">*</span></label>
                            <input type="tel" id="add_mobile" maxlength="10" placeholder="10-digit mobile" required>
                        </div>
                        <div class="form-group">
                            <label for="add_gender">Gender</label>
                            <select id="add_gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_course">Department</label>
                            <select id="add_course">
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electronics Engineering">Electronics Engineering</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_year">Year</label>
                            <select id="add_year">
                                <option value="First Year">First Year</option>
                                <option value="Second Year" selected>Second Year</option>
                                <option value="Third Year">Third Year</option>
                                <option value="Fourth Year">Fourth Year</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="add_sem">Semester</label>
                            <select id="add_sem">
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>" <?= ($s === 3) ? 'selected' : '' ?>>Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAsyncAddModal()">Cancel</button>
                    <button type="submit" id="saveAddBtn" class="btn btn-primary">Create Record</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit Student Asynchronously -->
    <div id="asyncEditModal" class="crud-modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Edit Student Record</h3>
                <button type="button" class="close-btn" onclick="closeAsyncEditModal()">&times;</button>
            </div>
            <form id="asyncEditForm" onsubmit="handleUpdateStudent(event)">
                <input type="hidden" id="edit_student_id">

                <div class="modal-body">
                    <div id="editModalError" class="modal-error-box" style="display: none;"></div>

                    <div class="modal-grid">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text" id="edit_id_display" disabled class="input-disabled">
                        </div>
                        <div class="form-group">
                            <label for="edit_full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="edit_full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_mobile">Mobile Number <span class="required">*</span></label>
                            <input type="tel" id="edit_mobile" maxlength="10" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_gender">Gender</label>
                            <select id="edit_gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_status">Account Status</label>
                            <select id="edit_status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_course">Department</label>
                            <select id="edit_course">
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electronics Engineering">Electronics Engineering</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_sem">Semester</label>
                            <select id="edit_sem">
                                <?php for ($s = 1; $s <= 8; $s++): ?>
                                    <option value="<?= $s ?>">Semester <?= $s ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAsyncEditModal()">Cancel</button>
                    <button type="submit" id="saveEditBtn" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.ajax-container {
    max-width: 1180px;
    margin: 20px auto;
    padding: 28px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    text-align: left;
}
.ajax-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #55b7cf;
}
.ajax-header h2 { margin: 0 0 4px 0; color: #1e3a8a; }
.ajax-header p { margin: 0; color: #64748b; font-size: 13px; }
.controls-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 20px;
    background: #f8fafc;
    padding: 14px 18px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.search-input-wrap {
    position: relative;
    flex-grow: 1;
    min-width: 260px;
}
.search-field {
    width: 100%;
    padding: 9px 34px 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    box-sizing: border-box;
}
.spinner {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 14px;
    height: 14px;
    border: 2px solid #cbd5e1;
    border-top-color: #2563eb;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }
.filter-select {
    padding: 9px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    background: #ffffff;
}
.table-responsive { overflow-x: auto; min-height: 250px; }
.async-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.async-table th { background: #f1f5f9; color: #334155; font-weight: 700; text-align: left; padding: 12px 14px; border-bottom: 2px solid #cbd5e1; }
.async-table td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; color: #1e293b; vertical-align: middle; }
.async-table tr:hover { background-color: #f8fafc; }
.pagination-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e2e8f0;
}
.pagination-btns { display: flex; gap: 10px; align-items: center; }
.btn {
    display: inline-block; padding: 9px 16px; border-radius: 6px; font-size: 13px;
    font-weight: 600; text-decoration: none; cursor: pointer; border: none;
}
.btn-primary { background: #2563eb; color: #ffffff; }
.btn-primary:hover { background: #1d4ed8; }
.btn-secondary { background: #e2e8f0; color: #1e293b; }
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }
.loading-state {
    text-align: center;
    padding: 50px 20px;
    color: #64748b;
}
.big-spinner {
    width: 32px; height: 32px; border: 3px solid #e2e8f0; border-top-color: #2563eb;
    border-radius: 50%; margin: 0 auto 12px auto; animation: spin 0.8s linear infinite;
}
.role-pill, .status-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.role-student { background: #dbeafe; color: #1e40af; }
.role-faculty { background: #fef3c7; color: #92400e; }
.role-admin { background: #fee2e2; color: #991b1b; }
.status-active { background: #dcfce7; color: #15803d; }
.status-inactive { background: #f1f5f9; color: #64748b; }
.btn-action-edit, .btn-action-delete {
    padding: 5px 10px; font-size: 12px; font-weight: 600; border-radius: 4px; cursor: pointer; border: none; margin-right: 4px;
}
.btn-action-edit { background: #e0f2fe; color: #0369a1; }
.btn-action-edit:hover { background: #0284c7; color: #ffffff; }
.btn-action-delete { background: #fee2e2; color: #991b1b; }
.btn-action-delete:hover { background: #dc2626; color: #ffffff; }

/* Toast */
.toast {
    position: fixed;
    bottom: 25px;
    right: 25px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    display: none;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    z-index: 2000;
}
.toast.show { display: flex; animation: toastIn 0.3s ease; }
@keyframes toastIn { from { transform: translateY(15px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.toast.success { background: #065f46; border: 1px solid #10b981; }
.toast.error { background: #991b1b; border: 1px solid #ef4444; }

/* Modals */
.crud-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.crud-modal.show { display: flex; }
.modal-dialog { background: #ffffff; width: 90%; max-width: 600px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
.modal-header { background: #f8fafc; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; color: #1e3a8a; font-size: 17px; }
.close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b; }
.modal-body { padding: 20px; }
.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.modal-footer { background: #f8fafc; padding: 14px 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; }
.modal-error-box { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 10px 14px; border-radius: 6px; font-size: 13px; margin-bottom: 14px; }
.form-group { display: flex; flex-direction: column; }
.form-group label { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; }
.required { color: #dc2626; }
.form-group input, .form-group select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 13px; width: 100%; box-sizing: border-box; }
.input-disabled { background: #f1f5f9; color: #64748b; }
</style>

<script src="js/api.js"></script>
<script>
let currentPage = 1;
const pageSize = 15;
let currentStudents = [];
const isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;

document.addEventListener("DOMContentLoaded", function () {
    StudentHubAPI.init();
    loadStudents();

    // Debounced Live Search
    const searchInput = document.getElementById("liveSearchInput");
    searchInput.addEventListener("input", debounce(function () {
        currentPage = 1;
        loadStudents();
    }, 300));

    // Department & Semester Filter changes
    document.getElementById("courseFilter").addEventListener("change", function () {
        currentPage = 1;
        loadStudents();
    });
    document.getElementById("semFilter").addEventListener("change", function () {
        currentPage = 1;
        loadStudents();
    });
});

function showToast(text, type = "success") {
    const toast = document.getElementById("toastNotification");
    const icon = document.getElementById("toastIcon");
    const textEl = document.getElementById("toastText");

    icon.textContent = (type === "success") ? "✓" : "⚠";
    textEl.textContent = text;
    toast.className = `toast show ${type}`;

    setTimeout(() => {
        toast.className = "toast";
    }, 3500);
}

async function loadStudents() {
    const tableBody = document.getElementById("studentsTableBody");
    const loading = document.getElementById("tableLoading");
    const spinner = document.getElementById("searchSpinner");

    spinner.style.display = "block";
    loading.style.display = (currentStudents.length === 0) ? "block" : "none";

    const q = document.getElementById("liveSearchInput").value.trim();
    const course = document.getElementById("courseFilter").value;
    const semester = document.getElementById("semFilter").value;

    try {
        const response = await StudentHubAPI.getStudents({
            q: q,
            course: course,
            semester: semester,
            page: currentPage,
            limit: pageSize
        });

        currentStudents = response.data.students || [];
        renderStudentsTable(currentStudents);

        // Update Pagination Controls
        const total = response.data.total || 0;
        const totalPages = response.data.total_pages || 1;
        document.getElementById("recordCountLabel").textContent = `Showing ${currentStudents.length} of ${total} record(s)`;
        document.getElementById("pageIndicator").textContent = `Page ${currentPage} of ${totalPages}`;
        document.getElementById("prevPageBtn").disabled = (currentPage <= 1);
        document.getElementById("nextPageBtn").disabled = (currentPage >= totalPages);

    } catch (err) {
        showToast(err.message || "Failed to load student records.", "error");
    } finally {
        spinner.style.display = "none";
        loading.style.display = "none";
    }
}

function renderStudentsTable(students) {
    const tbody = document.getElementById("studentsTableBody");
    tbody.innerHTML = "";

    if (students.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                    No student records match your query.
                </td>
            </tr>
        `;
        return;
    }

    students.forEach(s => {
        const tr = document.createElement("tr");
        tr.id = `row-${s.student_id}`;

        const roleClass = (s.role || "student").toLowerCase();
        const statusClass = (s.status || "active").toLowerCase();

        tr.innerHTML = `
            <td><strong>${escapeHtml(s.student_id)}</strong></td>
            <td>${escapeHtml(s.full_name)}</td>
            <td><a href="mailto:${escapeHtml(s.email)}" style="color: #2563eb; text-decoration: none;">${escapeHtml(s.email)}</a></td>
            <td>${escapeHtml(s.mobile || "")}</td>
            <td>${escapeHtml(s.course || "")}</td>
            <td>Sem ${escapeHtml(String(s.semester || ""))}</td>
            <td><span class="role-pill role-${roleClass}">${capitalize(s.role || "student")}</span></td>
            <td><span class="status-pill status-${statusClass}">${capitalize(s.status || "active")}</span></td>
            <td style="white-space: nowrap;">
                <button type="button" class="btn-action-edit" onclick='openAsyncEditModal("${escapeHtml(s.student_id)}")'>Edit</button>
                ${isAdmin ? `<button type="button" class="btn-action-delete" onclick='deleteStudentAsync("${escapeHtml(s.student_id)}", "${escapeHtml(s.full_name)}")'>Delete</button>` : ""}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function changePage(delta) {
    currentPage += delta;
    loadStudents();
}

// Create Student Modal Handlers
function openAsyncAddModal() {
    document.getElementById("addModalError").style.display = "none";
    document.getElementById("asyncAddForm").reset();
    document.getElementById("asyncAddModal").classList.add("show");
}
function closeAsyncAddModal() {
    document.getElementById("asyncAddModal").classList.remove("show");
}

async function handleCreateStudent(event) {
    event.preventDefault();
    const btn = document.getElementById("saveAddBtn");
    const errBox = document.getElementById("addModalError");

    btn.disabled = true;
    btn.textContent = "Saving...";
    errBox.style.display = "none";

    const payload = {
        student_id: document.getElementById("add_student_id").value.trim(),
        full_name: document.getElementById("add_full_name").value.trim(),
        email: document.getElementById("add_email").value.trim(),
        mobile: document.getElementById("add_mobile").value.trim(),
        gender: document.getElementById("add_gender").value,
        course: document.getElementById("add_course").value,
        year: document.getElementById("add_year").value,
        semester: document.getElementById("add_sem").value,
        role: "student"
    };

    try {
        const res = await StudentHubAPI.createStudent(payload);
        closeAsyncAddModal();
        showToast(res.message || "Student created successfully!", "success");
        loadStudents();
    } catch (err) {
        errBox.textContent = err.data?.message || err.message || "Failed to create student.";
        errBox.style.display = "block";
    } finally {
        btn.disabled = false;
        btn.textContent = "Create Record";
    }
}

// Edit Student Modal Handlers
function openAsyncEditModal(id) {
    const student = currentStudents.find(s => s.student_id === id);
    if (!student) return;

    document.getElementById("editModalError").style.display = "none";
    document.getElementById("edit_student_id").value = student.student_id;
    document.getElementById("edit_id_display").value = student.student_id;
    document.getElementById("edit_full_name").value = student.full_name;
    document.getElementById("edit_email").value = student.email;
    document.getElementById("edit_mobile").value = student.mobile;
    document.getElementById("edit_gender").value = student.gender || "Male";
    document.getElementById("edit_status").value = student.status || "active";
    document.getElementById("edit_course").value = student.course || "Computer Engineering";
    document.getElementById("edit_sem").value = student.semester || "3";

    document.getElementById("asyncEditModal").classList.add("show");
}
function closeAsyncEditModal() {
    document.getElementById("asyncEditModal").classList.remove("show");
}

async function handleUpdateStudent(event) {
    event.preventDefault();
    const btn = document.getElementById("saveEditBtn");
    const errBox = document.getElementById("editModalError");

    btn.disabled = true;
    btn.textContent = "Updating...";
    errBox.style.display = "none";

    const payload = {
        student_id: document.getElementById("edit_student_id").value,
        full_name: document.getElementById("edit_full_name").value.trim(),
        email: document.getElementById("edit_email").value.trim(),
        mobile: document.getElementById("edit_mobile").value.trim(),
        gender: document.getElementById("edit_gender").value,
        status: document.getElementById("edit_status").value,
        course: document.getElementById("edit_course").value,
        semester: document.getElementById("edit_sem").value,
        year: "Second Year",
        role: "student"
    };

    try {
        const res = await StudentHubAPI.updateStudent(payload);
        closeAsyncEditModal();
        showToast(res.message || "Student record updated!", "success");
        loadStudents();
    } catch (err) {
        errBox.textContent = err.data?.message || err.message || "Failed to update student.";
        errBox.style.display = "block";
    } finally {
        btn.disabled = false;
        btn.textContent = "Update Record";
    }
}

// Delete Student Asynchronously
async function deleteStudentAsync(id, name) {
    if (!confirm(`Are you sure you want to delete '${name}' (${id})?`)) return;

    try {
        const res = await StudentHubAPI.deleteStudent(id);
        const row = document.getElementById(`row-${id}`);
        if (row) {
            row.style.transition = "opacity 0.3s ease";
            row.style.opacity = "0";
            setTimeout(() => row.remove(), 300);
        }
        showToast(res.message || `Student record '${id}' removed.`, "success");
    } catch (err) {
        showToast(err.message || "Failed to delete student record.", "error");
    }
}

function escapeHtml(str) {
    if (!str) return "";
    return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}
function capitalize(str) {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
