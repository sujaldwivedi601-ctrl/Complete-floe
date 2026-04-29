<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

$response = [
    'success' => true,
    'teacher_tasks' => [],
    'student_tasks' => []
];

// 1. Get teacher-assigned tasks (HIGH priority - cannot delete)
$stmt = mysqli_prepare($conn, "SELECT id, title, subject, planned_time, status FROM tasks WHERE student_id = ? AND (status = 'pending' OR status = 'done') ORDER BY status ASC, created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $row['source'] = 'teacher';
    $response['teacher_tasks'][] = $row;
}
mysqli_stmt_close($stmt);

// 2. Get student-created tasks (normal priority - can delete)
$stmt = mysqli_prepare($conn, "SELECT id, title, subject, planned_time, status, created_at FROM student_tasks WHERE user_id = ? ORDER BY status ASC, created_at DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $row['source'] = 'student';
    $response['student_tasks'][] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode($response);

mysqli_close($conn);
?>
