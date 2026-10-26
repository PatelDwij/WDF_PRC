# StudentHub Portal — Entity-Relationship (ER) Model & Schema Specification
**Course:** ITUE203 — Web Development Frameworks  
**Practical 8:** Database Schema, ER Model, and PDO Connectivity  
**Student:** Dwij Patel (Student ID: 25CE077)  
**Institution:** CSPIT, CHARUSAT — B.Tech Computer Engineering  

---

## 1. Database Architecture Overview

The `studenthub` database is designed according to Third Normal Form (3NF) principles, eliminating data redundancy, transitive dependencies, and update anomalies. It comprises five core entities:

1. **`students`**: User identity, academic profiles, role-based authorization (`student`, `faculty`, `admin`), and bcrypt hashed credentials.
2. **`events`**: Campus technical hackathons, workshops, cultural festivals, and seminars with poster metadata, venue, and capacity quotas.
3. **`registrations`**: Relational junction table mapping student participation in events with a composite unique constraint preventing duplicate signups.
4. **`audit_logs`**: Immutable security log tracking all administrative, CRUD, and access events.
5. **`contact_messages`**: Public inquiries submitted via the helpdesk form.

---

## 2. Visual Entity-Relationship Diagram (Mermaid)

```mermaid
erDiagram
    STUDENTS ||--o{ REGISTRATIONS : "submits"
    EVENTS ||--o{ REGISTRATIONS : "receives"
    STUDENTS ||--o{ AUDIT_LOGS : "triggers"
    STUDENTS ||--o{ EVENTS : "creates/organizes"

    STUDENTS {
        varchar(20) student_id PK "Primary Key (e.g. 25CE077, ADM001)"
        varchar(100) full_name "Full name of user"
        varchar(100) email UK "Unique official email address"
        varchar(15) mobile "10-digit mobile number"
        enum gender "Male, Female, Other"
        varchar(20) year "Academic year (First to Fourth Year / Faculty)"
        tinyint semester "Current semester (1 to 8)"
        varchar(100) course "Department/Branch"
        enum role "student, faculty, admin"
        varchar(255) password "Bcrypt password hash"
        enum status "active, inactive"
        varchar(255) remember_token_hash "Hashed remember-me token"
        datetime last_login "Timestamp of last authentication"
        datetime created_at "Registration timestamp"
        datetime updated_at "Last record update"
    }

    EVENTS {
        int event_id PK "Auto-increment primary key"
        varchar(150) title "Event title"
        text description "Detailed event description"
        varchar(50) category "Workshop, Technical, Seminar, Cultural, Sports"
        datetime event_date "Date and time of event"
        varchar(100) venue "Campus location/auditorium"
        varchar(255) poster_path "Relative path to validated poster image"
        int capacity "Maximum attendee capacity"
        enum status "upcoming, completed, cancelled"
        varchar(20) created_by FK "Student ID of creator/organizer"
        datetime created_at "Creation timestamp"
    }

    REGISTRATIONS {
        int registration_id PK "Auto-increment primary key"
        varchar(20) student_id FK "References STUDENTS(student_id)"
        int event_id FK "References EVENTS(event_id)"
        datetime registered_at "Registration timestamp"
        enum status "confirmed, attended, cancelled"
    }

    AUDIT_LOGS {
        int log_id PK "Auto-increment primary key"
        varchar(20) user_id "User identifier or SYSTEM"
        varchar(20) role "Role at execution time"
        varchar(50) action "ACTION_NAME (e.g., CREATE, UPDATE, DELETE, LOGIN)"
        varchar(50) entity "Target table/model"
        varchar(50) entity_id "Target primary key"
        text details "Sanitized contextual JSON/text"
        varchar(45) ip_address "IPv4 or IPv6 client address"
        datetime created_at "Timestamp of occurrence"
    }

    CONTACT_MESSAGES {
        int message_id PK "Auto-increment primary key"
        varchar(100) name "Sender name"
        varchar(100) email "Sender email"
        varchar(150) subject "Inquiry subject"
        text message "Inquiry message body"
        enum status "unread, read, resolved"
        datetime created_at "Submission timestamp"
    }
```

---

## 3. Relational Constraints & Integrity Rules

1. **Referential Integrity**:
   - `registrations.student_id` $\rightarrow$ `students.student_id` with `ON DELETE CASCADE ON UPDATE CASCADE`.
   - `registrations.event_id` $\rightarrow$ `events.event_id` with `ON DELETE CASCADE ON UPDATE CASCADE`.
2. **Duplicate Prevention**:
   - `uk_student_email` on `students.email` enforces unique email addresses globally.
   - `uk_student_event` composite unique key on `(student_id, event_id)` prevents a student from registering twice for the same event.
3. **Indexing Strategy**:
   - Primary Keys: B-Tree clustered index on `student_id`, `event_id`, `registration_id`, `log_id`, `message_id`.
   - Foreign Keys & Filters: Secondary non-clustered indexes on `role`, `course`, `category`, `event_date`, and `created_at` for high-throughput pagination and live search queries.
