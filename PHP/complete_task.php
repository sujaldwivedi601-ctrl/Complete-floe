<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$task_id = $data['task_id'] ?? 0;
$user_id = $_SESSION['id'];

if (!$task_id) {
    echo json_encode(['success' => false, 'error' => 'Task ID required']);
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE tasks SET status = 'done' WHERE id = ? AND student_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $task_id, $user_id);

if (mysqli_stmt_execute($stmt)) {
    // Update progress
    $date = date('Y-m-d');
    $checkStmt = mysqli_prepare($conn, "SELECT id FROM progress_log WHERE user_id = ? AND date = ?");
    mysqli_stmt_bind_param($checkStmt, "is", $user_id, $date);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $updateStmt = mysqli_prepare($conn, "UPDATE progress_log SET tasks_done = tasks_done + 1 WHERE user_id = ? AND date = ?");
        mysqli_stmt_bind_param($updateStmt, "is", $user_id, $date);
        mysqli_stmt_execute($updateStmt);
        mysqli_stmt_close($updateStmt);
    } else {
        $insertStmt = mysqli_prepare($conn, "INSERT INTO progress_log (user_id, date, tasks_done, streak) VALUES (?, ?, 1, 1)");
        mysqli_stmt_bind_param($insertStmt, "is", $user_id, $date);
        mysqli_stmt_execute($insertStmt);
        mysqli_stmt_close($insertStmt);
    }
    mysqli_stmt_close($checkStmt);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>