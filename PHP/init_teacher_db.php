<?php
require_once "db_pdo.php";

try {
    // Create teacher table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS teacher (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✓ Table 'teacher' is ready.\n";

    // Optional: Check if 'subjects' table needs a teacher column
    // The user might want to save who is assigned to what subject
    $stmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'assigned_teacher'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE subjects ADD COLUMN assigned_teacher VARCHAR(255) DEFAULT NULL");
        echo "✓ Added 'assigned_teacher' column to 'subjects' table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
