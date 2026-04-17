<?php
include "db.php";

$sql = "CREATE TABLE IF NOT EXISTS timetables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('personal', 'institution') NOT NULL,
    timetable_data LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_accounts(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'timetables' created successfully or already exists.\n";
} else {
    echo "Error creating table: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);
?>
