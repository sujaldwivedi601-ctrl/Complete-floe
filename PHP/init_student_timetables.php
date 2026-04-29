-- Student Personal Timetable
-- Run this in your MySQL database (auth_system)

-- 1. Create student_timetables table
CREATE TABLE IF NOT EXISTS student_timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    custom_blocks JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- 2. Create tasks table (for pending tasks feature)
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(100),
    task_name VARCHAR(255),
    due_date DATE,
    status ENUM('pending', 'completed') DEFAULT 'priority',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- 3. Create indexes
CREATE INDEX idx_student_timetable_user ON student_timetables(user_id);
CREATE INDEX idx_tasks_user ON tasks(user_id);
CREATE INDEX idx_tasks_due ON tasks(due_date);
