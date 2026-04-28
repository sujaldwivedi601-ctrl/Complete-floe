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

    // Create subjects table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_name VARCHAR(255) NOT NULL,
        theory_hours INT DEFAULT 0,
        practical_hours INT DEFAULT 0,
        teacher_name VARCHAR(255) DEFAULT NULL,
        semester INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✓ Table 'subjects' is ready.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
