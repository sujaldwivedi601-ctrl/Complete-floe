     -- Database Schema for Precision Architect (Smart Timetable)

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Student', 'Teacher', 'Admin') DEFAULT 'Student',
    institution VARCHAR(150),
    roll_number VARCHAR(50),
    branch VARCHAR(50),
    class_name VARCHAR(50),
    college_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Timetables Table
CREATE TABLE timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    type VARCHAR(50),
    institution VARCHAR(150),
    is_global BOOLEAN DEFAULT FALSE,
    timetable_data JSON, -- Stores the JSON structure of the timetable
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tasks Table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    teacher_name VARCHAR(100),
    target_type ENUM('individual', 'branch', 'college') NOT NULL,
    target_value VARCHAR(100), -- Roll Number, Branch Name, or Institution Name
    description TEXT,
    deadline DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Task_Recipients Table (Optional, or just store student IDs JSON as in my mock)
CREATE TABLE task_recipients (
    task_id INT NOT NULL,
    student_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (task_id, student_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
