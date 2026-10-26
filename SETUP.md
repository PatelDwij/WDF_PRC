# StudentHub Portal — Local Setup & Verification Guide

**Course:** ITUE203 — Web Development Frameworks  
**Student:** Dwij Patel (Student ID: 25CE077)  
**University:** CHARUSAT — B.Tech Computer Engineering  

---

## 1. Prerequisites

Ensure the following tools are available on the evaluation computer:

- **XAMPP for Windows** (v8.2.x or higher) with Apache and MySQL/MariaDB installed.
- **PHP CLI**: `C:\xampp\php\php.exe` (PHP 8.2+ recommended).
- **Web Browser**: Google Chrome, Mozilla Firefox, or Microsoft Edge.
- **Git** (optional, for inspecting version control history).

---

## 2. Step-by-Step Setup Instructions

### Step 2.1: Place Repository in XAMPP Web Root
Ensure the project folder is placed inside:
```
C:\xampp\htdocs\PRC_WDF
```

### Step 2.2: Launch Apache and MySQL Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache** (Default Port: 80 / 443).
3. Click **Start** for **MySQL** (Default Port: 3306).

*Alternatively, from PowerShell / Command Prompt:*
```powershell
Start-Process "C:\xampp\apache\bin\httpd.exe" -ArgumentList "-d C:/xampp/apache" -WorkingDirectory "C:\xampp\apache" -WindowStyle Hidden
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=mysql\bin\my.ini", "--standalone" -WorkingDirectory "C:\xampp" -WindowStyle Hidden
```

### Step 2.3: Database Creation and Schema Import
Import the provided SQL schema file into MariaDB:

**Option A — Using Command Prompt / PowerShell (Recommended):**
```powershell
Get-Content "C:\xampp\htdocs\PRC_WDF\Final_Practical\database\studenthub.sql" | & "C:\xampp\mysql\bin\mysql.exe" -u root
```

**Option B — Using phpMyAdmin:**
1. Navigate to `http://localhost/phpmyadmin/` in your browser.
2. Click **Import** in the top menu.
3. Select `C:\xampp\htdocs\PRC_WDF\Final_Practical\database\studenthub.sql`.
4. Click **Go** to execute and import all 5 tables and 25+ seed records.

### Step 2.4: Verify Configuration
Verify that `Final_Practical/config/config.php` has appropriate local database settings:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'studenthub');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## 3. Direct Local URLs

Once Apache and MySQL are running:

| Page / Module | Local URL | Primary Features / Practical |
|---|---|---|
| **Root Entry** | `http://localhost/PRC_WDF/` | Automatic redirect to active portal |
| **Portal Home** | `http://localhost/PRC_WDF/Final_Practical/index.php` | Dynamic home with session nav & carousel |
| **User Sign In** | `http://localhost/PRC_WDF/Final_Practical/Login.php` | Practical 10: Secure authentication |
| **User Registration** | `http://localhost/PRC_WDF/Final_Practical/Register.php` | Practicals 7 & 9: Server validation & dual storage |
| **Student Dashboard** | `http://localhost/PRC_WDF/Final_Practical/Student_dashboard.php` | Practicals 10, 11: Academic standing & registered events |
| **Faculty Dashboard** | `http://localhost/PRC_WDF/Final_Practical/Faculty_dashboard.php` | Practicals 10, 14: Department metrics & quick actions |
| **Admin Dashboard** | `http://localhost/PRC_WDF/Final_Practical/Admin.php` | Practical 14: Live KPI metrics & charts |
| **Students CRUD** | `http://localhost/PRC_WDF/Final_Practical/manage_students.php` | Practical 11: Complete Student CRUD (Search/Filter) |
| **Events CRUD** | `http://localhost/PRC_WDF/Final_Practical/manage_events.php` | Practical 12: Event CRUD & validated poster upload |
| **Campus Events Portal** | `http://localhost/PRC_WDF/Final_Practical/events.php` | Practical 12: Student event discovery & registration |
| **Interactive Async CRUD** | `http://localhost/PRC_WDF/Final_Practical/ajax_crud.php` | Practical 13: Fetch API single-page async CRUD |
| **Audit Trail Logs** | `http://localhost/PRC_WDF/Final_Practical/audit_logs.php` | Practical 14: Security activity viewer & filters |
| **Student Registry** | `http://localhost/PRC_WDF/Final_Practical/view_students.php` | Practical 7: JSON/CSV stored record inspection |
| **Contact Helpdesk** | `http://localhost/PRC_WDF/Final_Practical/Contact.php` | Practical 7: Server validated contact form |
| **JSON Directory** | `http://localhost/PRC_WDF/Final_Practical/Directory.html` | Practical 6: Fetch API external JSON explorer |
| **REST API Students** | `http://localhost/PRC_WDF/Final_Practical/api/students.php` | Practical 13: JSON endpoint |
| **REST API Events** | `http://localhost/PRC_WDF/Final_Practical/api/events.php` | Practical 13: JSON endpoint |
| **PDO Connection Test** | `http://localhost/PRC_WDF/Final_Practical/test_connection.php` | Practical 8: JSON connectivity check |

---

## 4. Default Test Credentials

All demo accounts use password: `Password@123`

- **Administrator:** `admin@studenthub.edu`
- **Faculty:** `rajesh.sharma@charusat.edu.in` or `FAC001`
- **Faculty:** `neha.verma@charusat.edu.in` or `FAC002`
- **Student:** `dwij.patel@studenthub.edu` or `25CE077`
- **Student:** `aarav.mehta@studenthub.edu` or `25CE001`

---

## 5. Running Automated Integration Tests

To run the automated CLI test runner verifying all practicals:
```powershell
& "C:\xampp\php\php.exe" "C:\xampp\htdocs\PRC_WDF\Final_Practical\tests\run_tests.php"
```
Expect **24 tests passed (100% pass rate)**.
