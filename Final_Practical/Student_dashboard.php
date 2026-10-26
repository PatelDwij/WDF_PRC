<?php
/**
 * StudentHub Portal - Student Dashboard
 * Practicals 10, 11 & 14: Dynamic Authenticated Student Dashboard
 * Author: Dwij Patel (Student ID: 25CE077)
 * University: CHARUSAT - B.Tech Computer Engineering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Route Guard: Require login
require_login();

// Retrieve full student record from database
$userId = $_SESSION['user_id'];
$student = null;
$registeredEvents = [];

try {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $student = $stmt->fetch();

    if (!$student) {
        // Fallback to session details
        $student = [
            'student_id' => $_SESSION['user_id'],
            'full_name'  => $_SESSION['user_name'] ?? 'Student',
            'email'      => $_SESSION['user_email'] ?? '',
            'course'     => $_SESSION['course'] ?? 'Computer Engineering',
            'semester'   => $_SESSION['semester'] ?? 3,
            'year'       => 'Second Year'
        ];
    }

    // Fetch registered campus events
    $regStmt = $pdo->prepare(
        "SELECT e.event_id, e.title, e.category, e.event_date, e.venue, r.registered_at, r.status AS reg_status
         FROM registrations r
         JOIN events e ON r.event_id = e.event_id
         WHERE r.student_id = :id
         ORDER BY e.event_date ASC"
    );
    $regStmt->execute([':id' => $userId]);
    $registeredEvents = $regStmt->fetchAll();

} catch (PDOException $e) {
    error_log('[StudentHub Student Dashboard DB Error] ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | StudentHub</title>
    <link rel="stylesheet" href="Student_dashboard.css">
    <link rel="stylesheet" href="Style.css">
    <script src="script.js" defer></script>
    <style>
        .event-reg-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .event-reg-info h4 {
            margin: 0 0 6px 0;
            color: #1e3a8a;
            font-size: 15px;
        }
        .event-meta {
            font-size: 13px;
            color: #64748b;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .badge-cat {
            background: #e0f2fe;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 11px;
        }
        .badge-confirmed {
            background: #dcfce7;
            color: #15803d;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 12px;
        }
        .btn-cancel-reg {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-cancel-reg:hover {
            background: #dc2626;
            color: #ffffff;
        }
    </style>
</head>

<body>
    <div id="notification-banner" class="banner">
        <span>📢 <strong>Campus Notice:</strong> Semester examinations schedule and project evaluation dates announced.</span>
        <button id="close-banner">✖</button>
    </div>

    <div class="dashboard">
        <aside class="sidebar">
            <div class="profile">
                <div class="photo">👤</div>
                <h3><?= htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                <span style="font-size: 12px; color: #e0f2fe;"><?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <nav>
                <a href="index.html">Home</a>
                <a href="Student_dashboard.php" class="active">Dashboard</a>
                <a href="events.php">Campus Events</a>
                <a href="Directory.html">Student Directory</a>
                <a href="Profile.php">My Profile</a>
                <a href="Timetable.html">Timetable</a>
                <a href="Attendance.html">Attendance</a>
                <a href="courses.html">Subjects</a>
                <a href="Notices.html">Notices</a>
                <a href="Contact.php">Helpdesk</a>
                <a href="logout.php" style="color: #fee2e2; font-weight: bold; margin-top: 15px;">Sign Out</a>
            </nav>
        </aside>

        <main class="content">
            <header class="topbar">
                <h1>Academic Portal Dashboard</h1>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button id="theme-toggle" class="logout" style="background-color: #333;">🌙 Theme</button>
                    <a href="logout.php" class="logout" style="text-decoration: none; display: inline-block;">Sign Out</a>
                </div>
            </header>

            <section class="card">
                <h2>Welcome back, <?= htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <div class="details">
                    <p><strong>Student ID:</strong> <?= htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Institute:</strong> CSPIT, CHARUSAT</p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($student['course'] ?? 'Computer Engineering', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Semester:</strong> <?= htmlspecialchars($student['semester'] ?? '3', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($student['year'] ?? 'Second Year', ENT_QUOTES, 'UTF-8') ?>)</p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Account Status:</strong> <span style="color: #10b981; font-weight: bold;">Active</span></p>
                </div>
            </section>

            <section class="card">
                <h2>Academic Standing Overview</h2>
                <div class="overview">
                    <div class="box">
                        <h3>92%</h3>
                        <p>Semester Attendance</p>
                    </div>
                    <div class="box">
                        <h3>8.85</h3>
                        <p>Latest SGPA</p>
                    </div>
                    <div class="box">
                        <h3>8.60</h3>
                        <p>Cumulative CGPA</p>
                    </div>
                    <div class="box">
                        <h3><?= count($registeredEvents) ?></h3>
                        <p>Registered Events</p>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>My Campus Event Registrations</h2>
                <?php if (empty($registeredEvents)): ?>
                    <p style="color: #64748b; margin-bottom: 15px;">You have not registered for any campus events yet.</p>
                    <a href="events.php" class="btn btn-primary" style="display: inline-block; padding: 8px 16px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 6px;">Browse Available Events &rarr;</a>
                <?php else: ?>
                    <?php foreach ($registeredEvents as $reg): ?>
                        <div class="event-reg-card">
                            <div class="event-reg-info">
                                <h4><?= htmlspecialchars($reg['title'], ENT_QUOTES, 'UTF-8') ?> <span class="badge-cat"><?= htmlspecialchars($reg['category'], ENT_QUOTES, 'UTF-8') ?></span></h4>
                                <div class="event-meta">
                                    <span>📅 <?= date('M d, Y h:i A', strtotime($reg['event_date'])) ?></span>
                                    <span>📍 <?= htmlspecialchars($reg['venue'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span>Registered on <?= date('M d, Y', strtotime($reg['registered_at'])) ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="badge-confirmed">✓ Confirmed</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 15px;">
                        <a href="events.php" style="color: #2563eb; font-weight: 600; text-decoration: none;">+ Register for More Events &rarr;</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Quick Academic Services</h2>
                <div class="services">
                    <button id="open-modal-btn">System Status</button>
                    <button onclick="location.href='events.php'">Campus Events</button>
                    <button onclick="location.href='Timetable.html'">Class Timetable</button>
                    <button onclick="location.href='Attendance.html'">Attendance Records</button>
                    <button onclick="location.href='courses.html'">Enrolled Subjects</button>
                    <button onclick="location.href='Notices.html'">Academic Notices</button>
                    <button onclick="location.href='Profile.php'">Account Settings</button>
                </div>
            </section>
        </main>
    </div>

    <div id="welcome-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Portal System Status</h2>
            <p>All academic database and authentication services are operational. Your student session is active.</p>
        </div>
    </div>
</body>
</html>
