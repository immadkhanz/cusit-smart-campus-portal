-- ══════════════════════════════════════════════
-- CUSIT Smart Campus Portal — Database Schema
-- 10 Tables | Run setup.php for auto-creation
-- ══════════════════════════════════════════════

DROP DATABASE IF EXISTS cusit_portal;
CREATE DATABASE cusit_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cusit_portal;

-- Users (Admin + Student roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints (one active rule enforced in PHP)
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending','in_progress','resolved','rejected') NOT NULL DEFAULT 'pending',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Events
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    venue VARCHAR(200) NOT NULL,
    total_seats INT NOT NULL DEFAULT 100,
    registered_count INT NOT NULL DEFAULT 0,
    status ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event Registrations (UNIQUE prevents duplicates)
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_student (event_id, student_id)
);

-- FYP Groups
CREATE TABLE fyp_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(150) NOT NULL,
    project_title VARCHAR(255) NOT NULL,
    description TEXT,
    supervisor VARCHAR(150) NULL,
    status ENUM('pending','approved','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- FYP Members
CREATE TABLE fyp_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    student_id INT NOT NULL,
    FOREIGN KEY (group_id) REFERENCES fyp_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_group_student (group_id, student_id)
);

-- FYP Milestones
CREATE TABLE fyp_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES fyp_groups(id) ON DELETE CASCADE
);

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(200) NOT NULL,
    credits INT NOT NULL DEFAULT 3,
    instructor VARCHAR(150) NOT NULL,
    day ENUM('Monday','Tuesday','Wednesday','Thursday','Friday') NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    room VARCHAR(50) NOT NULL,
    max_students INT NOT NULL DEFAULT 40,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Course Enrollments
CREATE TABLE course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_student (course_id, student_id)
);

-- Attendance
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('present','absent') NOT NULL DEFAULT 'present',
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id),
    UNIQUE KEY unique_attendance (course_id, student_id, date)
);
