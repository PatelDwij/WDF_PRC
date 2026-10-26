# StudentHub Portal — Practical Submission Screenshots Checklist

**Course:** ITUE203 — Web Development Frameworks (WDF)  
**Student Name:** Dwij Patel (Student ID: 25CE077)  
**University:** CHARUSAT — B.Tech Computer Engineering  

---

## Instructions for Taking Screenshots for Practical Journal / Report

Capture clean full-browser screenshots (or active window using `Alt + PrintScreen`) at standard 100% zoom.

### Practical 7 — PHP Form Processing & File Storage
- [ ] **7.1 Registration Form:** `http://localhost/PRC_WDF/Final_Practical/Register.php` (clean form filled with valid sample data).
- [ ] **7.2 Successful Submission Banner:** Registration success notice: *"Your registration was submitted successfully. You can now sign in."*
- [ ] **7.3 JSON & CSV File Storage:** Open `Final_Practical/data/registrations.json` and `Final_Practical/data/students.csv` in VS Code or text editor showing persisted records.
- [ ] **7.4 Contact Form:** `http://localhost/PRC_WDF/Final_Practical/Contact.php` showing successful helpdesk submission.

### Practical 8 — Database Schema, ER Model & PDO
- [ ] **8.1 phpMyAdmin Schema:** phpMyAdmin showing `studenthub` database with all 5 tables (`students`, `events`, `registrations`, `audit_logs`, `contact_messages`).
- [ ] **8.2 ER Diagram:** Diagram in `Final_Practical/database/ER_DIAGRAM.md`.
- [ ] **8.3 PDO Connection Test:** JSON output from `http://localhost/PRC_WDF/Final_Practical/test_connection.php` displaying `"status": "connected"`.

### Practical 9 — Secure User Registration
- [ ] **9.1 Frontend Validation:** `Register.php` showing password strength meter ("Strength: Strong password").
- [ ] **9.2 Database Insertion:** phpMyAdmin table view showing new student record with hashed password (`$2y$10$...`).
- [ ] **9.3 Duplicate Error:** Attempting duplicate registration showing *"This email is already registered."*

### Practical 10 — Login, Sessions & Role-Based Access Control
- [ ] **10.1 Login Page:** `http://localhost/PRC_WDF/Final_Practical/Login.php` with demo credentials shortcuts.
- [ ] **10.2 Student Dashboard (Student View):** `Student_dashboard.php` after signing in as `25CE077` showing student details and registered events.
- [ ] **10.3 Faculty Dashboard (Faculty View):** `Faculty_dashboard.php` after signing in as `FAC001` showing faculty quick services.
- [ ] **10.4 Logout Notification:** `Login.php?msg=logged_out` displaying *"You have been signed out successfully."*

### Practical 11 — Student Management CRUD
- [ ] **11.1 Students Management Table:** `http://localhost/PRC_WDF/Final_Practical/manage_students.php` showing searchable student table with 25+ records.
- [ ] **11.2 Department / Semester Filter:** Search bar filtered by *"Computer Engineering"*.
- [ ] **11.3 Add Student Modal:** Pop-up modal with fields for adding a student.
- [ ] **11.4 Edit Student Modal:** Pop-up modal modifying an existing student.
- [ ] **11.5 Delete Confirmation:** Confirmation alert box before deletion.

### Practical 12 — Event Management CRUD & Poster Upload
- [ ] **12.1 Events Management Table:** `http://localhost/PRC_WDF/Final_Practical/manage_events.php` showing event thumbnails, categories, and capacity bars.
- [ ] **12.2 Create Event with Poster:** Create modal showing file upload input (`image/jpeg, image/png, image/webp`).
- [ ] **12.3 Campus Events Portal (Student View):** `http://localhost/PRC_WDF/Final_Practical/events.php` showing cards with posters and *"Register for Event"* button.
- [ ] **12.4 One-Click Registration Confirmed:** Green *"✓ Registered"* badge and *"Cancel"* button on registered event card.

### Practical 13 — PHP JSON API & Interactive Asynchronous CRUD
- [ ] **13.1 REST API Output:** Browser / Postman view of `http://localhost/PRC_WDF/Final_Practical/api/students.php` showing structured JSON envelope.
- [ ] **13.2 Interactive Async Dashboard:** `http://localhost/PRC_WDF/Final_Practical/ajax_crud.php` showing single-page Fetch API student management.
- [ ] **13.3 Live Search & Toast:** Typing in search box with instant table update and toast alert.

### Practical 14 — Admin Dashboard & Security Audit Trail
- [ ] **14.1 Admin Executive Dashboard:** `http://localhost/PRC_WDF/Final_Practical/Admin.php` showing 4 metric cards (Students, Faculty, Events, Registrations) and department distribution chart.
- [ ] **14.2 Role-Based Navigation:** Navigation bar showing logged-in user pill: *"Dr. Amit Patel [ADMINISTRATOR]"*.
- [ ] **14.3 Security Audit Trail:** `http://localhost/PRC_WDF/Final_Practical/audit_logs.php` showing action history with IP addresses and timestamps.

### Practical 15 — Integration & Automated Testing
- [ ] **15.1 Automated Test Suite:** CLI terminal running `Final_Practical/tests/run_tests.php` displaying **24 Passed, 0 Failed (100% Pass Rate)**.
- [ ] **15.2 Root Entry Redirection:** Navigating to `http://localhost/PRC_WDF/` redirecting seamlessly to `Final_Practical/index.php`.
