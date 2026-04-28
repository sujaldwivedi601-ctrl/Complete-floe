-- Progress Log Table for Tracking
-- Run this in your MySQL database (auth_system)

-- 1. Create progress_log table
CREATE TABLE IF NOT EXISTS progress_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- 2. Create index for faster lookups
CREATE INDEX idx_progress_user ON progress_log(user_id);
CREATE INDEX idx_progress_date ON progress_log(completed_at);
CREATE INDEX idx_progress_task ON progress_log(task_id);