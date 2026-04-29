-- Goals Table for AI Goal Setting
-- Run this in your MySQL database (auth_system)

-- 1. Create goals table
CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    goal_text TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
);

-- 2. Create index
CREATE INDEX idx_goals_user ON goals(user_id);
