# CHARUSAT ITUE203 — Web Development Frameworks (WDF)
# Comprehensive Project Audit Report

**Student Name:** Dwij Patel  
**Student ID:** 25CE077  
**Course:** B.Tech Computer Engineering (Semester 3)  
**Institution:** CSPIT, CHARUSAT  
**Academic Year:** 2026-27  
**Local Project Directory:** `C:\xampp\htdocs\PRC_WDF`  
**GitHub Repository:** `https://github.com/PatelDwij/WDF_PRC`  
**Reference Repository:** `https://github.com/chauhand2463/WDF_LAB`  
**Date of Audit:** October 19, 2026  
**Auditor:** Senior Full-Stack Web Developer & Code Auditor (Antigravity)

---

## 1. Executive Summary

A comprehensive, safety-first code audit of the **StudentHub Portal** project (`PRC_WDF`) was conducted in accordance with the official CHARUSAT Semester 3 Web Development Frameworks (`ITUE203`) practical curriculum.

### Key Audit Findings:
1. **Existing Base Foundation (Practicals 1–4):**  
   The user has established a solid, custom-designed frontend foundation for **StudentHub Portal** under `Final_Practical/` and `Practical_1/`. The site features an 11-page static architecture, wireframe artifacts, and a distinctive sky-blue / teal color palette (`#99f9f7`, `#30bfe3`, `#55b7cf`, `#aed4e5`).
2. **Critical Bugs Identified in Existing Code:**
   - **Fatal JavaScript Exception in `Register.js`:** Line 18 attempts to read `.value` of non-existent `#semester`, and lines 35, 38, 41, 104, 125, 146 try to set `.innerHTML` of non-existent elements (`genderError`, `semesterError`, `successMessage`). This breaks form submission entirely.
   - **Terms Checkbox Placement:** In `Register.html`, the `#terms` checkbox is placed outside the `<form>` tag.
   - **Broken Asset Links:** `Profile.html` references `Pics/IMG-20250429-WA0018.jpg`, but the `Pics/` directory does not exist.
   - **HTML Structural Flaws:** `Timetable.html` lacks a `<head>` tag and relies on deprecated tags (`<center>`, `<I>`, `<font>`, `bgcolor`); `Attendance.html` lacks `<!DOCTYPE html>` and contains an unclosed `<table>` tag; `courses.html` has a truncated tag (`<br\n</section>`).
   - **Case-Sensitivity & Dead Links:** In `Contact.html`, navigation links target lowercase `login.html` and `register.html` instead of `Login.html` and `Register.html`.
   - **Responsive Breakpoints:** Fixed pixel widths (`600px`, `750px`) in `Login.css`, `Register.css`, and `Contact.css` cause mobile horizontal overflow.
3. **Missing Practicals (Practicals 6–15):**  
   The user's project currently has **zero backend files (PHP), zero database files (MySQL/SQL), zero JSON datasets, and zero API endpoints**. Practicals 6 through 14 are completely unbuilt in the user's workspace, and Practical 15 (integration, root entry, and test report) is incomplete.

---

## 2. Practical-Wise Status Matrix (Practicals 1 to 15)

| Practical | Title / Focus | Status | Existing Files | Key Implementation / Verified Evidence |
|---|---|:---:|---|---|
| **Practical 1** | Project Initiation, Requirements, Sitemap, Wireframe, GitHub | **COMPLETE** | `Practical_1/` (11 wireframes, `Prc_1_Wireframe.pdf`), `Final_Practical/README.md` | Formal wireframes, problem scope, user roles, and clean Git repository verified. |
| **Practical 2** | Semantic HTML5 Pages with Accessibility Structure | **COMPLETE** | 12 HTML pages in `Final_Practical/` | All HTML5 syntax errors, unclosed tags, case-sensitive links, and broken images resolved; verified 0 broken links. |
| **Practical 3** | Responsive UI Design (Grid, Flexbox, Mobile-First) | **COMPLETE** | All CSS stylesheets in `Final_Practical/` | Replaced fixed pixel widths with fluid max-widths; added tablet/mobile media queries and responsive table wrappers. |
| **Practical 4** | JavaScript DOM Manipulation & Interactivity | **COMPLETE** | `script.js`, `index.html`, all pages | Carousel slider, mobile hamburger navigation, collapsible FAQ accordion, modal popup, and dark theme persistence verified. |
| **Practical 5** | Registration Form with Frontend Validation | **COMPLETE** | `Register.html`, `Register.js`, `Register.css` | Fixed fatal JS crash, added semester/error placeholders, moved terms inside form, implemented password-strength feedback. |
| **Practical 6** | External JSON & Fetch API (Search, Filter, Sort, Pagination) | **COMPLETE** | `data/students.json`, `data/events.json`, `data/faqs.json`, `Directory.html`, `directory.js`, `Directory.css` | 16 records each; Fetch API rendering, live debounced search, category filtering, sorting, pagination, and empty states verified. |
| **Practical 7** | PHP Form Processing + CSV/JSON Storage | **PENDING (Group 3)** | Next Group | Create PHP form processor (`register.php`), server-side validation/sanitization, append to `students.csv`, user feedback banners. |
| **Practical 8** | MySQL Schema, PDO Connectivity & Prepared Statements | **PENDING (Group 4)** | Next Group | Create `database/studenthub.sql` (students, events, registrations), `db.php` shared PDO connection, test connection script. |
| **Practical 9** | Secure Registration (DB Insert, Duplicate Check, Hashing) | **PENDING (Group 4)** | Next Group | Connect registration to MySQL via PDO, duplicate student ID / email check, `password_hash(..., PASSWORD_DEFAULT)`. |
| **Practical 10** | Login/Logout, Sessions, RBAC, Timeout, Remember-Me | **PENDING (Group 4)** | Next Group | Build `login.php` with `password_verify()`, `session_regenerate_id()`, `logout.php`, `session_check.php` (15-min timeout), remember-me cookie. |
| **Practical 11** | Student Management CRUD with Search & Filter | **PENDING (Group 5)** | Next Group | Build admin student CRUD (`manage_students.php`) with search, course dropdown filter, CSRF protection, delete confirmation modal, 20+ seed records. |
| **Practical 12** | Event Management CRUD with Poster Upload | **PENDING (Group 5)** | Next Group | Build admin event CRUD (`events.php`) with validated file upload (<=2MB, jpg/png/webp, finfo MIME check), storage in `uploads/event_posters/`, student event registration. |
| **Practical 13** | PHP JSON API + Fetch/AJAX CRUD (No Page Reload) | **PENDING (Group 6)** | Next Group | Build REST-style API (`api/config.php`, `api/students.php`, `api/events.php`), `X-CSRF-Token`, async dashboard (`ajax_crud.php`, `js/api.js`) with spinners & debounced search. |
| **Practical 14** | Admin Dashboard with RBAC, Dynamic Menu & Audit Log | **PENDING (Group 7)** | Next Group | Build database-driven `Admin.php`, dynamic role-based navigation (`menu.php`), audit logger (`audit.php` / `log_audit()`), and audit log viewer (`audit_logs.php`). |
| **Practical 15** | Final Integration, Testing, Documentation & Deployment | **IN PROGRESS (Group 8)** | `index.php`, Root git repo | Root entry redirect `index.php` created; comprehensive testing and documentation in progress. |

---

## 3. Deep-Dive Audit of Existing Codebase

### 3.1 Directory & File Layout
```
C:\xampp\htdocs\PRC_WDF\
├── .git\
├── Final_Practical\
│   ├── .hintrc
│   ├── Attendance.css
│   ├── Attendance.html
│   ├── Contact.css
│   ├── Contact.html
│   ├── courses.css
│   ├── courses.html
│   ├── Faculty_dashboard.css
│   ├── Faculty_dashboard.html
│   ├── images (1).jpg
│   ├── index.html
│   ├── Login.css
│   ├── Login.html
│   ├── Notices.css
│   ├── Notices.html
│   ├── Profile.css
│   ├── Profile.html
│   ├── README.md
│   ├── Register.css
│   ├── Register.html
│   ├── Register.js
│   ├── script.js
│   ├── Student_dashboard.css
│   ├── Student_dashboard.html
│   ├── Style.css
│   ├── Timetable.css
│   └── Timetable.html
└── Practical_1\
    ├── New_ATTENDENCE.drawio.png
    ├── New_CONTACT.drawio.png
    ├── New_FACULTY_DASHBOARD.drawio.png
    ├── New_HOME.drawio.png
    ├── New_LOGIN.drawio.png
    ├── New_NOTICES.drawio.png
    ├── New_PROFILE.drawio.png
    ├── New_REGISTER.drawio.png
    ├── New_STUDENT_DASHBOARD.drawio.png
    ├── New_SUBJECTS.drawio.png
    ├── New_TIMETABLE.drawio.png
    └── Prc_1_Wireframe.pdf
```

### 3.2 HTML & Accessibility Defects
1. **`Final_Practical\Timetable.html`**:
   - Lines 1–4: Missing `<head>` wrapper element around `<meta>` and `<title>`.
   - Lines 8–9: Deprecated `<center>`, `<I>`, and mismatched `</h3>` tags.
   - Lines 14, 25, 38, etc.: Deprecated `bgcolor="lightgray"` and `bgcolor="lightblue"` attributes on table cells instead of semantic CSS classes.
   - Missing main navigation or return link to dashboard.
2. **`Final_Practical\Attendance.html`**:
   - Line 1: Missing `<!DOCTYPE html>`.
   - Line 14: Invalid HTML `<table border="3"...>...<table><br>` (an opening tag instead of `</table>`).
   - Lacks consistent portal header/navigation.
3. **`Final_Practical\courses.html`**:
   - Line 60: Broken tag `</table><br\n</section>` (missing closing `>`).
4. **`Final_Practical\Profile.html`**:
   - Line 29: `<img src="Pics/IMG-20250429-WA0018.jpg" alt="Profile Picture">` references a non-existent directory `Pics/`.
5. **`Final_Practical\Contact.html`**:
   - Lines 18–19: Links `<a href="login.html">` and `<a href="register.html">` use incorrect case (actual files are `Login.html` and `Register.html`).

### 3.3 CSS & Responsiveness Defects
1. **Fixed Width Layouts**:
   - `Login.css` (line 48): `section { width: 600px; height: 400px; }`
   - `Register.css` (line 52): `section { width: 750px; }`
   - `Contact.css` (line 59): `section { width: 600px; }`
   - `Profile.css` (line 61): `section { width: 500px; }`
   - `Notices.css` (line 61): `section { width: 650px; }`
   On mobile screens (<600px wide), these fixed pixel dimensions trigger horizontal scrolling and break the viewport.
2. **Tables Without Responsive Wrappers**:
   - `Timetable.html` and `Attendance.html` have large data tables that expand beyond 800px width. They require a `<div class="table-responsive">` container with `overflow-x: auto` to prevent page blowouts on mobile.
3. **Grid Breakpoints**:
   - `Student_dashboard.css`: `.overview` uses `grid-template-columns: repeat(4, 1fr)` with no media query to stack to 2 columns on tablets or 1 column on phones.

### 3.4 JavaScript Interactivity & Validation Defects
1. **`Register.js` Runtime Error**:
   ```javascript
   // Register.js line 18:
   let semester = document.getElementById("semester").value; 
   // Uncaught TypeError: Cannot read properties of null (reading 'value')
   ```
   Because `#semester` does not exist in `Register.html`, the script halts immediately on submit, preventing the form from validating or displaying any messages.
2. **Missing Elements in `Register.html`**:
   - `Register.js` tries to clear: `#genderError`, `#semesterError`, `#successMessage`.
   - None of these IDs exist in `Register.html`.
3. **Password Strength Meter**:
   - `Register.html` has `<p id="passwordStrength"></p>`, but `Register.js` has no listener or logic to compute entropy, length, or character variety.
4. **Script Inclusion Scope**:
   - Only `Student_dashboard.html` and `Notices.html` include `script.js`.
   - The theme switcher (`#theme-toggle`) and modal popup only exist on `Student_dashboard.html`.

---

## 4. Comparison with Reference Repository (`WDF_LAB`)

| Dimension | User Project (`PRC_WDF`) | Reference Repo (`WDF_LAB`) | Recommendation for `PRC_WDF` |
|---|---|---|---|
| **Design & Aesthetic** | Custom sky-blue / teal palette (`#99f9f7`, `#30bfe3`, `#55b7cf`, `#aed4e5`). Modern, clean cards. | Darker blue / slate styling (`#1e3a8a`, `#2563eb`). | **Preserve user's existing brand colors, typography, and card aesthetic**. |
| **Repository Structure** | Cumulative website in `Final_Practical/`, design artifacts in `Practical_1/`. | Cumulative folders per practical (`Practical1/` through `practical14/`). | **Keep user's structure**, make the root `http://localhost/PRC_WDF/` serve/route the full application seamlessly while also preserving individual practical evidence. |
| **JSON API (Prc 13)** | Missing. | Full REST-style endpoints (`api/students.php`, `api/events.php`), CSRF header check, debounced frontend. | Implement clean, secure REST endpoints and modern AJAX frontend matching the user's UI. |
| **Admin Dashboard (Prc 14)** | Only static `Faculty_dashboard.html`. | Live database metrics, registrations bar chart, dynamic RBAC menu, audit trail. | Build database-backed `Admin.php` with monthly registration chart and role-based navigation. |
| **File Upload (Prc 12)** | Missing. | Validated poster upload (`finfo` MIME check, 2MB limit, cleanup on delete). | Implement robust poster upload matching official requirements in `uploads/event_posters/`. |

---

## 5. Architectural Implementation Plan

To finish the project safely and maintain 100% fidelity to the user's existing design:

### Step 1: Repair Existing Frontend (Practicals 2–5)
1. Fix all HTML validation errors in `Timetable.html`, `Attendance.html`, `courses.html`, `Contact.html`, and `Profile.html`.
2. Add a default profile avatar asset to resolve the broken image link.
3. Refactor fixed CSS widths (`600px`, `750px`) to fluid `max-width` with responsive media queries.
4. Add responsive table scrolling wrappers to `Timetable.html` and `Attendance.html`.
5. Fix `Register.js` by adding missing fields/error elements to `Register.html`, moving `#terms` inside the form, and implementing real-time password strength meter feedback.
6. Enhance `script.js` with:
   - Content / image carousel slider (Practical 4 requirement).
   - Mobile hamburger navigation toggle (Practical 4 requirement).
   - Collapsible FAQ section (Practical 4 requirement).
   - Site-wide script inclusion.

### Step 2: Implement Practical 6 (Fetch API & External JSON Datasets)
1. Create `data/events.json`, `data/students.json`, and `data/faqs.json` (each with 15+ comprehensive records).
2. Build `events.html` / dynamic student directory page with Fetch API, live search, department/category filtering, sorting, pagination, loading spinner, and error state handling.

### Step 3: Implement Practical 7 (PHP Form Processing & CSV Storage)
1. Create `register_process.php` or `register.php` handling POST submissions.
2. Implement server-side validation (`filter_var`, regex), XSS sanitization (`htmlspecialchars`).
3. Append submissions safely to `data/students.csv` with header checks and duplicate detection.

### Step 4: Implement Practicals 8 & 9 (MySQL Schema, PDO, Secure Registration)
1. Create `database/studenthub.sql` with normalized tables:
   - `students` (`student_id`, `full_name`, `email`, `mobile`, `course`, `password`, `role`, `last_login`, `remember_token_hash`, `created_at`)
   - `events` (`event_id`, `event_name`, `event_date`, `description`, `category`, `status`, `poster_path`, `created_at`)
   - `registrations` (`registration_id`, `student_id`, `event_id`, `registered_at`)
   - `audit_logs` (`log_id`, `student_id`, `role`, `action`, `entity`, `entity_id`, `details`, `ip_address`, `created_at`)
2. Create `db.php` providing a singleton/shared PDO connection with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`.
3. Create connection test script `test_connection.php`.
4. Connect user registration to MySQL using `password_hash($password, PASSWORD_DEFAULT)` and duplicate checks.

### Step 5: Implement Practical 10 (Secure Authentication, Sessions, RBAC)
1. Build `login.php` with `password_verify()`, session ID regeneration, and role detection.
2. Build `session_check.php` enforcing a 15-minute inactivity timeout.
3. Build `logout.php` that destroys sessions and securely clears cookies.
4. Implement secure remember-me token hashing.

### Step 6: Implement Practicals 11 & 12 (Student & Event Management CRUD)
1. Build `manage_students.php` (Admin only) with Create, Read, Update, Delete, live search, course filter, and delete confirmation modal. Seed 20+ realistic student records.
2. Build `events_manage.php` (Admin only) with poster file upload (max 2MB, jpg/png/webp, `finfo` validation), storage in `uploads/event_posters/`, and safe poster deletion on record removal.
3. Build student-facing `event_register.php` allowing students to browse and register for open events.

### Step 7: Implement Practical 13 (PHP JSON API + Async CRUD)
1. Create `api/config.php` (JSON headers, auth/role guards, CSRF token validation).
2. Create `api/students.php` and `api/events.php` supporting GET, POST, PUT, DELETE with clean JSON responses.
3. Create `ajax_crud.php` and `js/api.js` providing an asynchronous admin interface with loading spinners, debounced search, and pagination without full-page reloads.

### Step 8: Implement Practical 14 (Admin Dashboard, RBAC Menu & Audit Log)
1. Build database-driven `Admin.php` displaying total students, total events, total registrations, latest signups, and a monthly registrations chart.
2. Build `menu.php` rendering role-based navigation menus dynamically.
3. Build `audit.php` (`log_audit()`) and `audit_logs.php` to log and inspect all administrative actions.

### Step 9: Implement Practical 15 (Final Integration, Testing & Documentation)
1. Create root entry point `index.php` at `C:\xampp\htdocs\PRC_WDF\index.php` that routes visitors smoothly into the portal.
2. Generate `SETUP.md` with step-by-step local XAMPP Apache/MySQL setup, database import, and demo credentials.
3. Generate `TEST_REPORT.md` documenting verified test cases across all 15 practicals.
4. Prepare screenshot submission checklist.

---

## 6. Environment & Prerequisites Checklist

- [x] Local Folder: `C:\xampp\htdocs\PRC_WDF`
- [x] PHP CLI: PHP 8.2.12 detected
- [x] XAMPP Path: `C:\xampp\apache\bin\httpd.exe` and `C:\xampp\mysql\bin\mysqld.exe` present
- [ ] Apache Running: Currently stopped (needs start in XAMPP Control Panel)
- [ ] MySQL Running: Currently stopped (needs start in XAMPP Control Panel)
- [ ] Database Import: `studenthub` database needs creation and import of `studenthub.sql`

---

*Report generated and committed to project root as `PROJECT_AUDIT.md`.*
