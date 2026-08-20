<?php
// Database Migration Script
// URL: http://localhost/autheritation/PHP/migrate_tasks.php
// Run once to fix schema issues

header('Content-Type: text/plain');
error_reporting(E_ALL);

echo "=== Database Migration ===\n\n";

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "auth_system";

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Cannot connect to MySQL: " . mysqli_connect_error() . "\n");
}
echo "✓ Connected to database '$dbname'\n\n";

// 1. Fix tasks table - ensure status ENUM includes 'completed'
echo "--- Fixing tasks table ---\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM tasks LIKE 'status'");
if ($result && $row = mysqli_fetch_assoc($result)) {
    $currentType = $row['Type'];
    echo "Current status column type: $currentType\n";
    
    if (strpos($currentType, 'completed') === false) {
        $sql = "ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'done', 'completed') DEFAULT 'pending'";
        if (mysqli_query($conn, $sql)) {
            echo "✓ Updated status ENUM to include 'completed'\n";
        } else {
            echo "✗ Failed to update status: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "✓ Status ENUM already includes 'completed'\n";
    }
} else {
    echo "✗ tasks table or status column not found\n";
}

// 2. Ensure semester column exists in user_accounts
echo "\n--- Checking user_accounts.semester ---\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM user_accounts LIKE 'semester'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "✓ semester column already exists\n";
} else {
    $sql = "ALTER TABLE user_accounts ADD COLUMN semester INT DEFAULT 1";
    if (mysqli_query($conn, $sql)) {
        echo "✓ Added semester column to user_accounts\n";
    } else {
        echo "✗ Failed: " . mysqli_error($conn) . "\n";
    }
}

// 3. Ensure student_tasks table exists
echo "\n--- Checking student_tasks table ---\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'student_tasks'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "✓ student_tasks table already exists\n";
} else {
    $sql = "CREATE TABLE student_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        subject VARCHAR(100),
        planned_time FLOAT,
        status ENUM('pending', 'done') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
    )";
    if (mysqli_query($conn, $sql)) {
        echo "✓ Created student_tasks table\n";
    } else {
        echo "✗ Failed: " . mysqli_error($conn) . "\n";
    }
}

// 4. Ensure student_timetables table exists
echo "\n--- Checking student_timetables table ---\n";
$result = mysqli_query($conn, "SHOW TABLES LIKE 'student_timetables'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "✓ student_timetables table already exists\n";
} else {
    $sql = "CREATE TABLE student_timetables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        custom_blocks JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
    )";
    if (mysqli_query($conn, $sql)) {
        echo "✓ Created student_timetables table\n";
    } else {
        echo "✗ Failed: " . mysqli_error($conn) . "\n";
    }
}

// 5. Ensure progress_log has the right schema (date + tasks_done + streak)
echo "\n--- Checking progress_log table ---\n";
$result = mysqli_query($conn, "SHOW COLUMNS FROM progress_log LIKE 'date'");
if ($result && mysqli_num_rows($result) > 0) {
    echo "✓ progress_log.date column exists\n";
} else {
    // The complete_task.php expects date, tasks_done, streak columns
    // But the original schema only has user_id, task_id, completed_at
    // We need to add the missing columns
    @mysqli_query($conn, "ALTER TABLE progress_log ADD COLUMN date DATE AFTER user_id");
    @mysqli_query($conn, "ALTER TABLE progress_log ADD COLUMN tasks_done INT DEFAULT 0 AFTER date");
    @mysqli_query($conn, "ALTER TABLE progress_log ADD COLUMN streak INT DEFAULT 0 AFTER tasks_done");
    // Make task_id nullable since the new schema doesn't always use it
    @mysqli_query($conn, "ALTER TABLE progress_log MODIFY COLUMN task_id INT NULL DEFAULT NULL");
    echo "✓ Added date, tasks_done, streak columns to progress_log\n";
}

// 6. Add indexes for performance
echo "\n--- Adding indexes ---\n";
@mysqli_query($conn, "CREATE INDEX idx_tasks_student_status ON tasks(student_id, status)");
@mysqli_query($conn, "CREATE INDEX idx_timetable_college_public ON timetables(college_id, is_public)");
echo "✓ Indexes added (or already exist)\n";

// 7. Verify data integrity
echo "\n--- Data Integrity Check ---\n";

// Check if any tasks exist
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM tasks");
$row = mysqli_fetch_assoc($result);
echo "Total tasks in DB: {$row['count']}\n";

// Check users
$result = mysqli_query($conn, "SELECT role, COUNT(*) as count FROM user_accounts GROUP BY role");
while ($row = mysqli_fetch_assoc($result)) {
    echo "Users with role '{$row['role']}': {$row['count']}\n";
}

// Check timetables
$result = mysqli_query($conn, "SELECT is_public, COUNT(*) as count FROM timetables GROUP BY is_public");
while ($row = mysqli_fetch_assoc($result)) {
    $label = $row['is_public'] ? 'Public/Global' : 'Private';
    echo "Timetables ($label): {$row['count']}\n";
}

// Check colleges
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM colleges");
$row = mysqli_fetch_assoc($result);
echo "Colleges: {$row['count']}\n";

echo "\n=== MIGRATION COMPLETE ===\n";

mysqli_close($conn);
?>
