<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$custom_blocks = json_encode($data['custom_blocks'] ?? []);

$stmt = mysqli_prepare($conn, "
    INSERT INTO student_timetables (user_id, custom_blocks) 
    VALUES (?, ?) 
    ON DUPLICATE KEY UPDATE custom_blocks = VALUES(custom_blocks), updated_at = NOW()
");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $user_id, $custom_blocks);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Personal timetable saved']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>