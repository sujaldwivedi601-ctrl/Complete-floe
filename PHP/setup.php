<?php
// Fresh database setup - run in browser
// URL: http://localhost/autheritation/PHP/setup.php

header('Content-Type: text/plain');
error_reporting(E_ALL);

echo "=== Fresh Database Setup ===\n\n";

// Connect without database first to create it
$host = "localhost";
$user = "root";
$pass = "";

$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Cannot connect to MySQL: " . mysqli_connect_error() . "\n");
}
echo "✓ Connected to MySQL\n\n";

// 1. Create database
$dbname = "auth_system";
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    echo "✓ Database '$dbname' created\n";
} else {
    die("Cannot create database: " . mysqli_error($conn) . "\n");
}

mysqli_select_db($conn, $dbname);

// 2. Create user_accounts table
$sql = "CREATE TABLE IF NOT EXISTS user_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    number VARCHAR(20),
    role VARCHAR(20) DEFAULT 'Student',
    institution VARCHAR(200),
    college_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ user_accounts table\n";

// 3. Create colleges table
$sql = "CREATE TABLE IF NOT EXISTS colleges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ colleges table\n";

// 4. Create timetables table
$sql = "CREATE TABLE IF NOT EXISTS timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    college_id INT,
    title VARCHAR(200),
    type VARCHAR(50),
    is_public TINYINT(1) DEFAULT 0,
    timetable_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ timetables table\n";

// 5. Create tasks table
$sql = "CREATE TABLE IF NOT EXISTS tasks (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ tasks table\n";

// 6. Create goals table
$sql = "CREATE TABLE IF NOT EXISTS goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    goal_text TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ goals table\n";

// 7. Create progress_log table
$sql = "CREATE TABLE IF NOT EXISTS progress_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ progress_log table\n";

// 8. Create student_timetables table
$sql = "CREATE TABLE IF NOT EXISTS student_timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    custom_blocks JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);
echo "✓ student_timetables table\n";

// Add foreign keys (ignore if error)
@mysqli_query($conn, "ALTER TABLE user_accounts ADD FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL");
@mysqli_query($conn, "ALTER TABLE timetables ADD FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL");
@mysqli_query($conn, "ALTER TABLE tasks ADD FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE SET NULL");
echo "✓ Foreign keys added\n";

// Add sample college
$sql = "INSERT IGNORE INTO colleges (name, code) VALUES ('Demo College', 'DEMO')";
mysqli_query($conn, $sql);
echo "✓ Sample college added\n";

// Create test teacher user (password: teacher123)
$password = password_hash('teacher123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO user_accounts (user_name, email, password, role, college_id) 
VALUES ('teacher1', 'teacher@demo.com', '$password', 'Teacher', 1)";
mysqli_query($conn, $sql);

// Create test student user (password: student123)
$password = password_hash('student123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO user_accounts (user_name, email, password, role, college_id) 
VALUES ('student1', 'student@demo.com', '$password', 'Student', 1)";
mysqli_query($conn, $sql);
echo "✓ Test users created\n";

echo "\n=== SETUP COMPLETE ===\n\n";
echo "Database: auth_system\n\n";
echo "Test Accounts:\n";
echo "  Teacher: teacher1 / teacher123\n";
echo "  Student: student1 / student123\n";
echo "  College: DEMO\n\n";
echo "Now update your db.php if needed:\n";
echo "\$database = 'auth_system';\n";
?>
