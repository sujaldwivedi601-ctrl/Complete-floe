-- Add columns for publishing to students
-- Run this in your MySQL database (auth_system)

-- 1. Add is_public column
ALTER TABLE timetables 
ADD COLUMN is_public TINYINT(1) DEFAULT 0;

-- 2. Add college_id column
ALTER TABLE timetables 
ADD COLUMN college_id INT NULL;

-- 3. Add foreign key to colleges
ALTER TABLE timetables 
ADD CONSTRAINT fk_timetable_college 
FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL;

-- 4. Create indexes
CREATE INDEX idx_timetable_college ON timetables(college_id);
CREATE INDEX idx_timetable_public ON timetables(is_public);
CREATE INDEX idx_timetable_college_public ON timetables(college_id, is_public);