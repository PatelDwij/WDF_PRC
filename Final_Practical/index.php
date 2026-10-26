<?php
/**
 * StudentHub Portal - Central Home Entry
 * Practicals 1-15: Unified Portal Homepage with Dynamic Session Header
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Notification Banner -->
<div id="notification-banner" class="banner">
    <span>📢 <strong>Notice:</strong> Welcome to CHARUSAT StudentHub Portal &bull; Academic Year 2026-27 &bull; Semester 3 Session Active.</span>
    <button id="close-banner" aria-label="Dismiss Notification">✖</button>
</div>

<main>
    <!-- 2. Hero Content / Carousel Slider -->
    <div class="carousel-container" aria-label="Featured Highlights">
        <div class="carousel-slide active">
            <h3>🎓 Centralized Academic Hub</h3>
            <p>Track your academic progress, view course materials, and manage student services with ease.</p>
        </div>
        <div class="carousel-slide">
            <h3>📅 Live Timetables &amp; Schedules</h3>
            <p>Never miss a class! View your daily lectures, practical laboratory timings, and room allocations.</p>
        </div>
        <div class="carousel-slide">
            <h3>🎪 Campus Events &amp; Workshops</h3>
            <p>Participate in technical symposiums, annual hackathons, and certified engineering workshops.</p>
        </div>

        <button class="carousel-prev" aria-label="Previous Slide">❮</button>
        <button class="carousel-next" aria-label="Next Slide">❯</button>

        <div class="carousel-dots">
            <span class="carousel-dot active" aria-label="Slide 1"></span>
            <span class="carousel-dot" aria-label="Slide 2"></span>
            <span class="carousel-dot" aria-label="Slide 3"></span>
        </div>
    </div>

    <!-- 3. Welcome Section -->
    <section>
        <h2>Welcome to Student Hub Portal</h2>
        <p><i>Manage your courses, track attendance, and stay updated with the latest campus notices all in one place.</i></p>
        <hr>

        <?php if (!is_logged_in()): ?>
            <a href="Login.php"><button type="button">Sign In to Portal</button></a>
            <a href="Register.php"><button type="button">Create Account</button></a>
        <?php else:
            $role = $_SESSION['role'] ?? 'student';
            $dashUrl = ($role === 'admin') ? 'Admin.php' : (($role === 'faculty') ? 'Faculty_dashboard.php' : 'Student_dashboard.php');
        ?>
            <a href="<?= $dashUrl ?>"><button type="button" style="background-color: #2563eb; color: #fff;">Open My Dashboard &rarr;</button></a>
            <a href="events.php"><button type="button">Campus Events</button></a>
        <?php endif; ?>
        <button id="open-modal-btn" type="button">System Status</button>
    </section>

    <!-- 4. Collapsible FAQ Section -->
    <section class="faq-section">
        <h3>Frequently Asked Questions (FAQ)</h3>

        <div class="faq-item">
            <button class="faq-question" type="button">How do I access my enrolled subjects and courses?</button>
            <div class="faq-answer">
                <p>Navigate to the <strong>Subjects</strong> page from your dashboard menu to see your enrolled course codes, titles, and credit allocations.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button">Where can I check my attendance records?</button>
            <div class="faq-answer">
                <p>Visit the <strong>Attendance</strong> tab to view your overall attendance percentage as well as course-by-course breakdown with visual progress meters.</p>
            </div>
        </div>

        <div class="faq-item">
            <button class="faq-question" type="button">How can I register a new account on the portal?</button>
            <div class="faq-answer">
                <p>Click on <strong>Create Account</strong> in the top navigation bar, fill in your institutional details, and submit the registration form.</p>
            </div>
        </div>
    </section>
</main>

<!-- Welcome / Status Modal -->
<div id="welcome-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2>Portal System Status</h2>
        <p>All core academic database, authentication, and event scheduling services are operating nominally.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
