<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$task_id = $data['id'] ?? 0;
$user_id = $_SESSION['id'];

if (!$task_id) {
    echo json_encode(['success' => false, 'error' => 'Task ID required']);
    exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM student_tasks WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>