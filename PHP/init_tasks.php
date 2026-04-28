-- Tasks Table for Teacher Assignment System
-- Run this in your MySQL database (auth_system)

-- 1. Create tasks table (replacing earlier version)
DROP TABLE IF EXISTS tasks;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL DEFAULT 0,
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

-- 2. Create indexes
CREATE INDEX idx_tasks_teacher ON tasks(teacher_id);
CREATE INDEX idx_tasks_student ON tasks(student_id);
CREATE INDEX idx_tasks_college ON tasks(college_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due ON tasks(due_date);

-- 3. Update subject table if exists (for dropdown)
-- You'll need to extract subjects from the PDF or timetable