-- Complete Database Schema for Precision Architect
-- Compatible with all PHP files in the project
-- Run this in phpMyAdmin or via: SOURCE Quewry.sql;

CREATE DATABASE IF NOT EXISTS auth_system;
USE auth_system;

-- Table 1: User Accounts (main users table)
CREATE TABLE IF NOT EXISTS user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    number VARCHAR(50),
    role ENUM('Student', 'Teacher', 'Admin') DEFAULT 'Student',
    institution VARCHAR(150),
    college_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 2: Timetables
CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('personal', 'institution') NOT NULL,
    timetable_data LONGTEXT,
    is_public TINYINT(1) DEFAULT 0,
    college_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- Table 3: Colleges
CREATE TABLE IF NOT EXISTS colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample colleges
INSERT INTO colleges (name, code) VALUES 
('Lovely Professional University', 'LPU'),
('Punjab Technical University', 'PTU'),
('Maharaja Ranjit Singh Punjab Technical University', 'MRSPTU'),
('Guru Nanak Dev University', 'GNDU'),
('Punjab University', 'PU'),
('Chandigarh University', 'CU');

-- Table 4: Tasks (Teacher assigned to students)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    college_id INT,
    subject VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    status ENUM('pending', 'done') DEFAULT 'pending',
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL
);

-- Table 5: Goals (AI Goal Setting)
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    goal_text TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- Table 6: Progress Log (Task completion tracking)
CREATE TABLE IF NOT EXISTS progress_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- Table 7: Student Timetables (Personal custom blocks)
CREATE TABLE IF NOT EXISTS student_timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    custom_blocks JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- Table 8: Teacher (GPC management)
CREATE TABLE IF NOT EXISTS teacher (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 9: Subjects (extracted from PDF)
CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    theory_hours INT DEFAULT 0,
    practical_hours INT DEFAULT 0,
    teacher_name VARCHAR(255),
    semester INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add indexes for performance
CREATE INDEX idx_timetable_user ON timetables(user_id);
CREATE INDEX idx_timetable_college ON timetables(college_id);
CREATE INDEX idx_tasks_teacher ON tasks(teacher_id);
CREATE INDEX idx_tasks_student ON tasks(student_id);
CREATE INDEX idx_tasks_college ON tasks(college_id);
CREATE INDEX idx_colleges_code ON colleges(code);
CREATE INDEX idx_user_college_id ON user_accounts(college_id);