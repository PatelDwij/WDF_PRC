-- ============================================================================
-- StudentHub Portal - Complete Database Schema & Seed Data
-- WDF Practical 8: Database Schema, ER Model, and PDO Connectivity
-- Author: Dwij Patel (Student ID: 25CE077)
-- University: CHARUSAT - B.Tech Computer Engineering (Semester 3)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `studenthub`;

-- ----------------------------------------------------------------------------
-- Table 1: students
-- Core user entity storing authentication, profile, and role-based credentials.
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `students`;

CREATE TABLE `students` (
  `student_id` VARCHAR(20) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `mobile` VARCHAR(15) NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
  `year` VARCHAR(20) NOT NULL DEFAULT 'Second Year',
  `semester` TINYINT NOT NULL DEFAULT 3,
  `course` VARCHAR(100) NOT NULL DEFAULT 'Computer Engineering',
  `role` ENUM('student', 'faculty', 'admin') NOT NULL DEFAULT 'student',
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `remember_token_hash` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `uk_student_email` (`email`),
  KEY `idx_student_role` (`role`),
  KEY `idx_student_course` (`course`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 2: events
-- Institutional workshops, hackathons, seminars, and extracurricular activities.
-- ----------------------------------------------------------------------------
CREATE TABLE `events` (
  `event_id` INT AUTO_INCREMENT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'Workshop',
  `event_date` DATETIME NOT NULL,
  `venue` VARCHAR(100) NOT NULL,
  `poster_path` VARCHAR(255) DEFAULT NULL,
  `capacity` INT NOT NULL DEFAULT 100,
  `status` ENUM('upcoming', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `created_by` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_event_category` (`category`),
  KEY `idx_event_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 3: registrations
-- Relational mapping of student participation in campus events.
-- ----------------------------------------------------------------------------
CREATE TABLE `registrations` (
  `registration_id` INT AUTO_INCREMENT NOT NULL,
  `student_id` VARCHAR(20) NOT NULL,
  `event_id` INT NOT NULL,
  `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('confirmed', 'attended', 'cancelled') NOT NULL DEFAULT 'confirmed',
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `uk_student_event` (`student_id`, `event_id`),
  KEY `idx_reg_student` (`student_id`),
  KEY `idx_reg_event` (`event_id`),
  CONSTRAINT `fk_registrations_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_registrations_event`
    FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 4: audit_logs
-- Immutable security log tracking all administrative, CRUD, and access events.
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `log_id` INT AUTO_INCREMENT NOT NULL,
  `user_id` VARCHAR(20) DEFAULT NULL,
  `role` VARCHAR(20) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL,
  `entity` VARCHAR(50) NOT NULL,
  `entity_id` VARCHAR(50) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_entity` (`entity`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table 5: contact_messages
-- User support inquiries submitted via the public contact form.
-- ----------------------------------------------------------------------------
CREATE TABLE `contact_messages` (
  `message_id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read', 'resolved') NOT NULL DEFAULT 'unread',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `idx_contact_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA INSERTION
-- Password for all seed accounts: 'Password@123'
-- Hash: $2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa
-- ============================================================================

-- 1. Administrative & Faculty Accounts
INSERT INTO `students` (`student_id`, `full_name`, `email`, `mobile`, `gender`, `year`, `semester`, `course`, `role`, `password`, `status`) VALUES
('ADM001', 'Dr. Amit Patel', 'admin@studenthub.edu', '9876500001', 'Male', 'Faculty', 0, 'Computer Engineering', 'admin', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('FAC001', 'Prof. Rajesh Sharma', 'rajesh.sharma@charusat.edu.in', '9876500002', 'Male', 'Faculty', 0, 'Computer Engineering', 'faculty', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('FAC002', 'Dr. Neha Verma', 'neha.verma@charusat.edu.in', '9876500003', 'Female', 'Faculty', 0, 'Information Technology', 'faculty', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active');

-- 2. 22 Student Accounts (At least 20 records required by Practical 11)
INSERT INTO `students` (`student_id`, `full_name`, `email`, `mobile`, `gender`, `year`, `semester`, `course`, `role`, `password`, `status`) VALUES
('25CE077', 'Dwij Patel', 'dwij.patel@studenthub.edu', '9876543210', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE001', 'Aarav Mehta', 'aarav.mehta@studenthub.edu', '9825100001', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE002', 'Ananya Shah', 'ananya.shah@studenthub.edu', '9825100002', 'Female', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE003', 'Dhruv Trivedi', 'dhruv.trivedi@studenthub.edu', '9825100003', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE004', 'Diya Joshi', 'diya.joshi@studenthub.edu', '9825100004', 'Female', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE005', 'Harshil Parikh', 'harshil.parikh@studenthub.edu', '9825100005', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE006', 'Isha Dave', 'isha.dave@studenthub.edu', '9825100006', 'Female', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE007', 'Jayant Bhatt', 'jayant.bhatt@studenthub.edu', '9825100007', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE008', 'Kavya Desai', 'kavya.desai@studenthub.edu', '9825100008', 'Female', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE009', 'Manan Pandya', 'manan.pandya@studenthub.edu', '9825100009', 'Male', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE010', 'Nidhi Vora', 'nidhi.vora@studenthub.edu', '9825100010', 'Female', 'Second Year', 3, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25IT001', 'Parth Shukla', 'parth.shukla@studenthub.edu', '9825100011', 'Male', 'Second Year', 3, 'Information Technology', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25IT002', 'Pooja Barot', 'pooja.barot@studenthub.edu', '9825100012', 'Female', 'Second Year', 3, 'Information Technology', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25IT003', 'Rohan Panchal', 'rohan.panchal@studenthub.edu', '9825100013', 'Male', 'Second Year', 3, 'Information Technology', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25IT004', 'Riya Solanki', 'riya.solanki@studenthub.edu', '9825100014', 'Female', 'Second Year', 3, 'Information Technology', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CS001', 'Siddharth Rao', 'siddharth.rao@studenthub.edu', '9825100015', 'Male', 'Third Year', 5, 'Computer Science', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CS002', 'Tanvi Sompura', 'tanvi.sompura@studenthub.edu', '9825100016', 'Female', 'Third Year', 5, 'Computer Science', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CS003', 'Utkarsh Nair', 'utkarsh.nair@studenthub.edu', '9825100017', 'Male', 'Third Year', 5, 'Computer Science', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25EC001', 'Varun Goswami', 'varun.goswami@studenthub.edu', '9825100018', 'Male', 'First Year', 1, 'Electronics Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25EC002', 'Vidhi Raval', 'vidhi.raval@studenthub.edu', '9825100019', 'Female', 'First Year', 1, 'Electronics Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE020', 'Yashraj Jadeja', 'yashraj.jadeja@studenthub.edu', '9825100020', 'Male', 'Fourth Year', 7, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active'),
('25CE021', 'Zoya Mansuri', 'zoya.mansuri@studenthub.edu', '9825100021', 'Female', 'Fourth Year', 7, 'Computer Engineering', 'student', '$2y$10$WFabujENFhwBGAHLdyjhYOt4wPP9T2LMf4hWzZKkHcnL3RT.hxhHa', 'active');

-- 3. Institutional Events (Practical 12 Seed Events)
INSERT INTO `events` (`event_id`, `title`, `description`, `category`, `event_date`, `venue`, `poster_path`, `capacity`, `status`, `created_by`) VALUES
(1, 'CHARUSAT Annual Hackathon 2026', 'A 24-hour hackathon focused on solving real-world challenges using AI, Web, and Cloud technologies.', 'Technical', '2026-11-15 09:00:00', 'CSPIT Auditorium & Labs', NULL, 150, 'upcoming', 'ADM001'),
(2, 'Cloud Computing with AWS & Docker Workshop', 'Hands-on practical workshop exploring containerization, Docker containers, and scalable AWS cloud deployment.', 'Workshop', '2026-11-20 10:00:00', 'Lab 301, Computer Engineering', NULL, 60, 'upcoming', 'FAC001'),
(3, 'Web Development Frameworks Symposium', 'A collaborative symposium where student developers showcase state-of-the-art web architectures.', 'Seminar', '2026-11-25 14:00:00', 'Seminar Hall B', NULL, 120, 'upcoming', 'FAC001'),
(4, 'Cyber Security & Ethical Hacking Bootcamp', 'Intensive hands-on seminar covering OWASP Top 10 vulnerabilities, penetration testing, and safe coding.', 'Workshop', '2026-12-05 10:30:00', 'Cyber Security Center of Excellence', NULL, 80, 'upcoming', 'FAC002'),
(5, 'Spandan 2026 - Annual Cultural Festival', 'Annual university cultural celebration featuring music, drama, dance, and fine arts competitions.', 'Cultural', '2026-12-18 17:00:00', 'University Central Grounds', NULL, 500, 'upcoming', 'ADM001'),
(6, 'Inter-Department Cricket Tournament', 'Annual inter-branch cricket championship fostering sportsmanship and teamwork.', 'Sports', '2026-12-22 08:30:00', 'University Sports Complex', NULL, 200, 'upcoming', 'ADM001');

-- 4. Sample Registrations
INSERT INTO `registrations` (`student_id`, `event_id`, `registered_at`, `status`) VALUES
('25CE077', 1, '2026-10-21 10:15:00', 'confirmed'),
('25CE077', 2, '2026-10-22 14:30:00', 'confirmed'),
('25CE001', 1, '2026-10-21 11:00:00', 'confirmed'),
('25CE002', 1, '2026-10-21 11:45:00', 'confirmed'),
('25CE003', 2, '2026-10-22 09:15:00', 'confirmed'),
('25CE004', 3, '2026-10-23 16:20:00', 'confirmed'),
('25CE005', 4, '2026-10-24 10:00:00', 'confirmed'),
('25IT001', 1, '2026-10-21 12:10:00', 'confirmed'),
('25IT002', 2, '2026-10-22 15:40:00', 'confirmed'),
('25CS001', 3, '2026-10-23 17:05:00', 'confirmed');

-- 5. Initial Audit Logs
INSERT INTO `audit_logs` (`user_id`, `role`, `action`, `entity`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
('SYSTEM', 'system', 'DATABASE_INITIALIZATION', 'database', 'studenthub', 'Database schema created and initial seed datasets inserted successfully.', '127.0.0.1', CURRENT_TIMESTAMP),
('ADM001', 'admin', 'EVENT_CREATED', 'events', '1', 'Created event: CHARUSAT Annual Hackathon 2026', '127.0.0.1', CURRENT_TIMESTAMP),
('FAC001', 'faculty', 'EVENT_CREATED', 'events', '2', 'Created event: Cloud Computing with AWS & Docker Workshop', '127.0.0.1', CURRENT_TIMESTAMP),
('25CE077', 'student', 'EVENT_REGISTERED', 'registrations', '1', 'Registered for event: CHARUSAT Annual Hackathon 2026', '127.0.0.1', CURRENT_TIMESTAMP);
