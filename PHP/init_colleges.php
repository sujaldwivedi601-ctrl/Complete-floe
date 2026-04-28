-- Database Setup SQL Queries for Colleges
-- Run this in your MySQL database (auth_system)

-- 1. Create colleges table
CREATE TABLE IF NOT EXISTS colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Add college_id column to user_accounts (if not exists)
ALTER TABLE user_accounts 
ADD COLUMN college_id INT NULL;

-- 3. Add foreign key constraint (optional)
ALTER TABLE user_accounts 
ADD CONSTRAINT fk_user_college 
FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL;

-- 4. Sample data (optional)
INSERT INTO colleges (name, code) VALUES 
('Massachusetts Institute of Technology', 'MIT001'),
('Stanford University', 'STN001'),
('Harvard University', 'HVL001'),
('Indian Institute of Technology Bombay', 'IITB'),
('Indian Institute of Technology Delhi', 'IITD'),
('National Institute of Technology', 'NIT001'),
('University of California Berkeley', 'UCB001'),
('Princeton University', 'PRN001');

-- 5. Create index for faster lookups
CREATE INDEX idx_colleges_code ON colleges(code);
CREATE INDEX idx_colleges_name ON colleges(name);
CREATE INDEX idx_user_college_id ON user_accounts(college_id);
