# 🎓 StudentHub Portal — Complete Web Development Frameworks (ITUE203) Project

**Course:** ITUE203 — Web Development Frameworks (WDF)  
**Student Name:** Dwij Patel  
**Student ID:** 25CE077  
**Academic Year:** 2026–27 (Semester 3)  
**Institution:** CSPIT, CHARUSAT  
**Program:** B.Tech Computer Engineering  
**Local Path:** `C:\xampp\htdocs\PRC_WDF`  
**GitHub Repository:** `https://github.com/PatelDwij/WDF_PRC`  

---

## 📖 Project Overview

**StudentHub Portal** is a centralized, enterprise-grade academic management and student services portal built for university students, faculty members, and administrators. It integrates responsive UI design, client-side interactivity, server-side PHP processing, dual JSON/CSV file persistence, relational MySQL database management with PDO, bcrypt authentication, role-based access control (RBAC), secure multipart file uploads, RESTful JSON APIs, interactive Fetch API CRUD operations, and an immutable security audit trail.

---

## 🎯 Practical Implementation Status Matrix (Practicals 1–15)

All 15 official curriculum practicals are fully implemented, verified, and integrated into the portal:

| Practical | Title / Topic | Status | Key Deliverables & Implemented Features |
|---|---|:---:|---|
| **Practical 1** | Project Initiation, Wireframing & GitHub | **Complete** | 11 wireframes, sitemap, problem scope, Git version control under `Practical_1/`. |
| **Practical 2** | Semantic HTML5 Pages & Accessibility | **Complete** | Accessible HTML5 semantic structure across 12 portal pages with valid ARIA attributes. |
| **Practical 3** | Responsive UI (Flexbox, CSS Grid, Mobile-First) | **Complete** | Fluid layouts, media queries (768px, 640px), responsive data table scrolling wrappers. |
| **Practical 4** | JavaScript DOM Interactivity | **Complete** | Carousel slider, mobile hamburger navigation, collapsible FAQ accordion, modal dialogs, dark/light theme persistence. |
| **Practical 5** | Form Validation (Client-Side) | **Complete** | Real-time regex validation, password strength entropy meter, instant mismatch error feedback in `Register.js`. |
| **Practical 6** | External JSON Datasets & Fetch API | **Complete** | `data/students.json`, `data/events.json`, `data/faqs.json`, debounced search, category filtering, pagination in `Directory.html`. |
| **Practical 7** | PHP Form Processing & File Storage | **Complete** | POST form processing, server validation, sanitization, exclusive file locking (`LOCK_EX`), dual persistence in `registrations.json` & `students.csv`. |
| **Practical 8** | Database Schema, ER Model & PDO | **Complete** | 3NF MySQL database (`studenthub`), foreign keys, `database/studenthub.sql`, ER diagram in `database/ER_DIAGRAM.md`, singleton PDO connection in `config/db.php`. |
| **Practical 9** | Secure User Registration | **Complete** | Database registration via prepared statements, duplicate email and ID validation, `password_hash($pass, PASSWORD_DEFAULT)` in `Register.php`. |
| **Practical 10** | Authentication, Sessions, RBAC & Timeout | **Complete** | `password_verify()`, `session_regenerate_id(true)`, 15-minute inactivity timeout, 30-day remember-me tokens, role redirects, server-side route guards in `includes/auth.php`. |
| **Practical 11** | Student Management CRUD | **Complete** | Create, Read, Update, Delete students in `manage_students.php`, search, department/semester filter, confirmation modals, 26 seeded student records. |
| **Practical 12** | Event Management CRUD & Poster Upload | **Complete** | Event CRUD in `manage_events.php`, secure multipart file upload (`finfo` MIME validation, 2MB limit, safe hashing), storage in `uploads/event_posters/` with script-blocking `.htaccess`, one-click student registration in `events.php`. |
| **Practical 13** | PHP REST JSON API & Asynchronous CRUD | **Complete** | RESTful JSON endpoints (`api/students.php`, `api/events.php`, `api/registrations.php`), `X-CSRF-Token` validation, interactive Fetch API single-page CRUD in `ajax_crud.php` & `js/api.js`. |
| **Practical 14** | Admin Dashboard, Dynamic Menus & Audit Log | **Complete** | Executive dashboard in `Admin.php` with real-time KPI metrics, department charts, dynamic role-based navigation in `includes/nav.php`, audit logger in `includes/audit.php`, audit log viewer in `audit_logs.php`. |
| **Practical 15** | Integration, Testing, Docs & Deployment | **Complete** | Automated integration test suite (`tests/run_tests.php` - 24/24 passing), root entry redirect `index.php`, `SETUP.md`, `TEST_REPORT.md`, `SCREENSHOTS_CHECKLIST.md`. |

---

## 🛠 Technology Stack

- **Frontend:** HTML5, Vanilla CSS3 (Custom design system, sky-blue/teal theme: `#99f9f7`, `#30bfe3`, `#1e3a8a`), JavaScript (ES6+), Fetch API.
- **Backend:** PHP 8.2+ (Procedural + Object-Oriented PDO connection).
- **Database:** MariaDB 10.4 / MySQL 8.0 with InnoDB engine, utf8mb4 charset, foreign keys, clustered primary indexes, composite unique keys.
- **File Storage:** JSON (`LOCK_EX`), CSV (mirrored archive), secure image upload storage.
- **Security:**
  - Password Hashing: Bcrypt via `password_hash()` and `password_verify()`.
  - SQL Injection Prevention: Prepared statements with bound parameters (`PDO::ATTR_EMULATE_PREPARES => false`).
  - Cross-Site Scripting (XSS): Sanitization via `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
  - Cross-Site Request Forgery (CSRF): Synchronizer tokens verified on state-changing requests (`X-CSRF-Token` header / POST field).
  - Session Security: `HttpOnly` and `SameSite=Lax` cookie flags, ID regeneration on login, 15-minute inactivity timeout.
  - File Upload Security: MIME type sniffing via PHP `finfo`, size checks (<= 2MB), cryptographic filename randomization, `.htaccess` execution denial.

---

## 📁 Repository Directory Structure

```
PRC_WDF/
├── .env.example                       <- Environment configuration template
├── index.php                          <- Root entry redirect to portal
├── README.md                          <- Main project documentation
├── SETUP.md                           <- Comprehensive setup guide
├── TEST_REPORT.md                     <- Automated test suite results
├── SCREENSHOTS_CHECKLIST.md           <- Submission screenshots checklist
├── PROJECT_AUDIT.md                   <- Codebase audit & requirements baseline
│
├── Final_Practical/                   <- StudentHub Application Root
│   ├── index.php                      <- Dynamic portal homepage
│   ├── index.html                     <- Static homepage (Practicals 1–4)
│   ├── Login.php                      <- Secure authentication handler
│   ├── Login.html                     <- Login form
│   ├── logout.php                     <- Session termination & cookie cleanup
│   ├── Register.php                   <- Dual file & database registration
│   ├── Register.html                  <- Registration form
│   ├── Register.js                    <- Client-side validation & password strength
│   ├── Student_dashboard.php          <- Dynamic student dashboard
│   ├── Student_dashboard.html         <- Static student dashboard
│   ├── Faculty_dashboard.php          <- Dynamic faculty dashboard
│   ├── Faculty_dashboard.html         <- Static faculty dashboard
│   ├── Admin.php                      <- Executive admin dashboard with KPI metrics
│   ├── manage_students.php            <- Complete Student CRUD (Search & Filter)
│   ├── manage_events.php              <- Event CRUD with poster file upload
│   ├── events.php                     <- Student event browsing & registration
│   ├── events.html                    <- Static events redirector
│   ├── ajax_crud.php                  <- Interactive Fetch API async CRUD
│   ├── audit_logs.php                 <- System security audit trail viewer
│   ├── Profile.php                    <- User profile & password management
│   ├── Profile.html                   <- Static profile page
│   ├── Contact.php                    <- Helpdesk inquiry form handler
│   ├── Contact.html                   <- Contact page
│   ├── view_students.php              <- Stored student records registry
│   ├── Directory.html                 <- External JSON directory & search (Prc 6)
│   ├── directory.js                   <- Directory Fetch API & filter logic
│   ├── Timetable.html                 <- Class timetable schedule
│   ├── Attendance.html                <- Course attendance records
│   ├── courses.html                   <- Enrolled subjects list
│   ├── Notices.html                   <- Campus notice board
│   ├── Style.css                      <- Main portal design system
│   ├── script.js                      <- Global interactivity (carousel, theme, modal)
│   │
│   ├── api/                           <- RESTful JSON API Endpoints (Practical 13)
│   │   ├── config.php                 <- API headers, JSON envelope & CSRF validation
│   │   ├── students.php               <- REST CRUD for students (GET, POST, PUT, DELETE)
│   │   ├── events.php                 <- REST CRUD for events (GET, POST, DELETE)
│   │   └── registrations.php          <- REST CRUD for registrations (GET, POST, DELETE)
│   │
│   ├── config/                        <- Database & System Configuration
│   │   ├── config.php                 <- Constants, session lifetime, environment parsing
│   │   └── db.php                     <- Reusable PDO connection singleton
│   │
│   ├── database/                      <- Relational Database Artifacts (Practical 8)
│   │   ├── studenthub.sql             <- Complete schema, DDL & 25+ seed records
│   │   └── ER_DIAGRAM.md              <- ER model specification & Mermaid diagrams
│   │
│   ├── data/                          <- Persistent File Storage Datasets (Practicals 6–7)
│   │   ├── .htaccess                  <- Direct download access protection
│   │   ├── registrations.json         <- Registrations stored with LOCK_EX
│   │   ├── students.csv               <- Mirrored CSV export archive
│   │   ├── students.json              <- Static student dataset (Prc 6)
│   │   ├── events.json                <- Static campus events dataset (Prc 6)
│   │   ├── faqs.json                  <- FAQ accordion dataset (Prc 6)
│   │   └── contact_messages.json      <- Stored helpdesk inquiries
│   │
│   ├── includes/                      <- Shared Reusable Modular Components
│   │   ├── header.php                 <- Unified HTML head & branding
│   │   ├── footer.php                 <- Shared university footer & script loader
│   │   ├── nav.php                    <- Dynamic role-based navigation bar (RBAC)
│   │   ├── auth.php                   <- Authentication guards & timeout middleware
│   │   └── audit.php                  <- Immutable security audit logging utility
│   │
│   ├── js/
│   │   └── api.js                     <- Client-side Fetch API wrapper with CSRF headers
│   │
│   ├── tests/
│   │   └── run_tests.php              <- Automated CLI test runner (24/24 passing)
│   │
│   └── uploads/                       <- Secure File Uploads (Practical 12)
│       └── event_posters/
│           └── .htaccess              <- Disables PHP script execution
│
└── Practical_1/                       <- Practical 1 Submission Artifacts
    ├── Prc_1_Wireframe.pdf            <- Consolidated wireframe report
    └── *.drawio.png                   <- Individual page wireframe designs
```

---

## 🔑 Default Test Credentials

All demo accounts are configured in `Final_Practical/database/studenthub.sql` with hashed bcrypt passwords:

| Role | Name | Email / Student ID | Default Password | Dashboard URL |
|---|---|---|---|---|
| **Administrator** | Dr. Amit Patel | `admin@studenthub.edu` | `Password@123` | `http://localhost/PRC_WDF/Final_Practical/Admin.php` |
| **Faculty** | Prof. Rajesh Sharma | `rajesh.sharma@charusat.edu.in` / `FAC001` | `Password@123` | `http://localhost/PRC_WDF/Final_Practical/Faculty_dashboard.php` |
| **Faculty** | Dr. Neha Verma | `neha.verma@charusat.edu.in` / `FAC002` | `Password@123` | `http://localhost/PRC_WDF/Final_Practical/Faculty_dashboard.php` |
| **Student** | Dwij Patel | `dwij.patel@studenthub.edu` / `25CE077` | `Password@123` | `http://localhost/PRC_WDF/Final_Practical/Student_dashboard.php` |
| **Student** | Aarav Mehta | `aarav.mehta@studenthub.edu` / `25CE001` | `Password@123` | `http://localhost/PRC_WDF/Final_Practical/Student_dashboard.php` |

---

## 🚀 Quick Local Setup (XAMPP)

1. **Start Services:** Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. **Verify Database:** Import `Final_Practical/database/studenthub.sql` into phpMyAdmin or run:
   ```powershell
   Get-Content "Final_Practical\database\studenthub.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root
   ```
3. **Open Portal:** Open your browser and navigate to:
   ```
   http://localhost/PRC_WDF/
   ```
4. **Run Automated Test Suite:**
   ```powershell
   & "C:\xampp\php\php.exe" "Final_Practical\tests\run_tests.php"
   ```

---

## 👨‍💻 Developer & Academic Information

- **Developer:** Dwij Patel
- **Student ID:** 25CE077
- **Class / Division:** B.Tech Computer Engineering, Semester 3 (Div 2)
- **Institution:** Chandubhai S. Patel Institute of Technology (CSPIT)
- **University:** Charotar University of Science and Technology (CHARUSAT)
- **Course Coordinator:** Department of Computer Engineering
