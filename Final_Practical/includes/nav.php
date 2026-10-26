<?php
/**
 * StudentHub Portal - Dynamic Role-Based Navigation
 * Practical 14: Dynamic Menus & Role-Based Navigation
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/auth.php';

$isLoggedIn = is_logged_in();
$user       = get_logged_user();
$currentUrl = basename($_SERVER['PHP_SELF'] ?? '');
?>
<nav class="portal-nav" aria-label="Main Navigation">
    <button class="hamburger-btn" id="hamburger-btn" aria-label="Toggle navigation menu" aria-expanded="false">
        ☰
    </button>
    <ul class="nav-links" id="nav-links">
        <li><a href="index.html" class="<?= ($currentUrl === 'index.html' || $currentUrl === 'index.php') ? 'active' : '' ?>">Home</a></li>

        <?php if (!$isLoggedIn): ?>
            <li><a href="Directory.html" class="<?= ($currentUrl === 'Directory.html') ? 'active' : '' ?>">Student Directory</a></li>
            <li><a href="events.php" class="<?= ($currentUrl === 'events.php') ? 'active' : '' ?>">Campus Events</a></li>
            <li><a href="Timetable.html" class="<?= ($currentUrl === 'Timetable.html') ? 'active' : '' ?>">Timetable</a></li>
            <li><a href="Notices.html" class="<?= ($currentUrl === 'Notices.html') ? 'active' : '' ?>">Notices</a></li>
            <li><a href="Contact.php" class="<?= ($currentUrl === 'Contact.php' || $currentUrl === 'Contact.html') ? 'active' : '' ?>">Contact Support</a></li>
            <li><a href="Login.php" class="btn-nav-primary <?= ($currentUrl === 'Login.php' || $currentUrl === 'Login.html') ? 'active' : '' ?>">Sign In</a></li>
            <li><a href="Register.php" class="btn-nav-secondary <?= ($currentUrl === 'Register.php' || $currentUrl === 'Register.html') ? 'active' : '' ?>">Create Account</a></li>

        <?php elseif ($user['role'] === 'student'): ?>
            <li><a href="Student_dashboard.php" class="<?= ($currentUrl === 'Student_dashboard.php' || $currentUrl === 'Student_dashboard.html') ? 'active' : '' ?>">My Dashboard</a></li>
            <li><a href="events.php" class="<?= ($currentUrl === 'events.php') ? 'active' : '' ?>">Campus Events</a></li>
            <li><a href="courses.html" class="<?= ($currentUrl === 'courses.html') ? 'active' : '' ?>">Courses</a></li>
            <li><a href="Timetable.html" class="<?= ($currentUrl === 'Timetable.html') ? 'active' : '' ?>">Timetable</a></li>
            <li><a href="Attendance.html" class="<?= ($currentUrl === 'Attendance.html') ? 'active' : '' ?>">Attendance</a></li>
            <li><a href="Notices.html" class="<?= ($currentUrl === 'Notices.html') ? 'active' : '' ?>">Notices</a></li>
            <li><a href="Profile.php" class="<?= ($currentUrl === 'Profile.php' || $currentUrl === 'Profile.html') ? 'active' : '' ?>">My Profile</a></li>
            <li><a href="Contact.php" class="<?= ($currentUrl === 'Contact.php') ? 'active' : '' ?>">Helpdesk</a></li>
            <li class="nav-user-item">
                <span class="user-pill">
                    <span class="user-dot"></span>
                    <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge-role badge-student">Student</span>
                </span>
            </li>
            <li><a href="logout.php" class="btn-nav-logout">Sign Out</a></li>

        <?php elseif ($user['role'] === 'faculty'): ?>
            <li><a href="Faculty_dashboard.php" class="<?= ($currentUrl === 'Faculty_dashboard.php' || $currentUrl === 'Faculty_dashboard.html') ? 'active' : '' ?>">Faculty Dashboard</a></li>
            <li><a href="manage_students.php" class="<?= ($currentUrl === 'manage_students.php') ? 'active' : '' ?>">Student Management</a></li>
            <li><a href="manage_events.php" class="<?= ($currentUrl === 'manage_events.php') ? 'active' : '' ?>">Event Management</a></li>
            <li><a href="events.php" class="<?= ($currentUrl === 'events.php') ? 'active' : '' ?>">View Events</a></li>
            <li><a href="Notices.html" class="<?= ($currentUrl === 'Notices.html') ? 'active' : '' ?>">Notices</a></li>
            <li class="nav-user-item">
                <span class="user-pill">
                    <span class="user-dot"></span>
                    <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge-role badge-faculty">Faculty</span>
                </span>
            </li>
            <li><a href="logout.php" class="btn-nav-logout">Sign Out</a></li>

        <?php elseif ($user['role'] === 'admin'): ?>
            <li><a href="Admin.php" class="<?= ($currentUrl === 'Admin.php') ? 'active' : '' ?>">Admin Dashboard</a></li>
            <li><a href="manage_students.php" class="<?= ($currentUrl === 'manage_students.php') ? 'active' : '' ?>">Students CRUD</a></li>
            <li><a href="manage_events.php" class="<?= ($currentUrl === 'manage_events.php') ? 'active' : '' ?>">Events CRUD</a></li>
            <li><a href="ajax_crud.php" class="<?= ($currentUrl === 'ajax_crud.php') ? 'active' : '' ?>">Interactive CRUD</a></li>
            <li><a href="audit_logs.php" class="<?= ($currentUrl === 'audit_logs.php') ? 'active' : '' ?>">Audit Logs</a></li>
            <li class="nav-user-item">
                <span class="user-pill">
                    <span class="user-dot"></span>
                    <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge-role badge-admin">Administrator</span>
                </span>
            </li>
            <li><a href="logout.php" class="btn-nav-logout">Sign Out</a></li>
        <?php endif; ?>
    </ul>
</nav>

<style>
/* Dynamic Navigation Styling */
.portal-nav {
    background-color: #30bfe3;
    padding: 10px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.portal-nav ul {
    list-style: none;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 18px;
    margin: 0;
    padding: 0;
    flex-wrap: wrap;
}
.portal-nav a {
    color: #1e3a8a;
    font-weight: 700;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 4px;
    transition: all 0.2s ease;
    font-size: 14px;
}
.portal-nav a:hover, .portal-nav a.active {
    background-color: #1e3a8a;
    color: #ffffff;
}
.btn-nav-primary {
    background-color: #2563eb !important;
    color: #ffffff !important;
}
.btn-nav-primary:hover {
    background-color: #1d4ed8 !important;
}
.btn-nav-secondary {
    background-color: #059669 !important;
    color: #ffffff !important;
}
.btn-nav-secondary:hover {
    background-color: #047857 !important;
}
.btn-nav-logout {
    background-color: #dc2626 !important;
    color: #ffffff !important;
}
.btn-nav-logout:hover {
    background-color: #b91c1c !important;
}
.user-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
.user-dot {
    width: 8px;
    height: 8px;
    background-color: #10b981;
    border-radius: 50%;
    display: inline-block;
}
.badge-role {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    padding: 2px 6px;
    border-radius: 10px;
}
.badge-student {
    background-color: #dbeafe;
    color: #1e40af;
}
.badge-faculty {
    background-color: #fef3c7;
    color: #92400e;
}
.badge-admin {
    background-color: #fee2e2;
    color: #991b1b;
}
@media (max-width: 768px) {
    .hamburger-btn {
        display: block !important;
        margin: 0 auto;
        font-size: 24px;
        background: none;
        border: none;
        color: #1e3a8a;
        cursor: pointer;
    }
    .portal-nav ul {
        display: none;
        flex-direction: column;
        gap: 10px;
        padding-top: 10px;
    }
    .portal-nav ul.show-mobile {
        display: flex;
    }
}
</style>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var hb = document.getElementById("hamburger-btn");
    var links = document.getElementById("nav-links");
    if (hb && links) {
        hb.addEventListener("click", function() {
            var expanded = hb.getAttribute("aria-expanded") === "true";
            hb.setAttribute("aria-expanded", !expanded);
            links.classList.toggle("show-mobile");
        });
    }
});
</script>
