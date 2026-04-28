<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'] ?? '';

$data = json_decode(file_get_contents('php://input'), true);
$task_id = $data['task_id'] ?? 0;
$new_status = $data['status'] ?? 'done';

if (!$task_id) {
    echo json_encode(['success' => false, 'error' => 'Task ID required']);
    exit();
}

// Only students can mark their own tasks done, teachers can update any
if ($role === 'Student') {
    $stmt = mysqli_prepare($conn, "
        UPDATE tasks SET status = ?, completed_at = NOW() 
        WHERE id = ? AND student_id = ?
    ");
    mysqli_stmt_bind_param($stmt, "sii", $new_status, $task_id, $user_id);
} else {
    $stmt = mysqli_prepare($conn, "
        UPDATE tasks SET status = ?, completed_at = NOW() 
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "si", $new_status, $task_id);
}

if (mysqli_stmt_execute($stmt)) {
    // Log progress when task is marked done
    if ($new_status === 'done' && $role === 'Student') {
        $logStmt = mysqli_prepare($conn, "INSERT INTO progress_log (user_id, task_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($logStmt, "ii", $user_id, $task_id);
        mysqli_stmt_execute($logStmt);
        mysqli_stmt_close($logStmt);
    }
    
    echo json_encode(['success' => true, 'message' => 'Task updated']);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>