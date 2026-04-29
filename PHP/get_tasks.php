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
$college_id = $_SESSION['college_id'] ?? 0;

if ($role === 'Student') {
    // Student: get their tasks only
    $stmt = mysqli_prepare($conn, "
        SELECT t.id, t.subject, t.title, t.description, t.due_date, t.status, t.created_at, 
               u.user_name as teacher_name
        FROM tasks t
        LEFT JOIN user_accounts u ON t.teacher_id = u.id
        WHERE t.student_id = ? AND t.college_id = ?
        ORDER BY t.due_date ASC, t.status ASC
    ");
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $college_id);
} elseif ($role === 'Teacher') {
    // Teacher: get tasks they assigned
    $stmt = mysqli_prepare($conn, "
        SELECT t.id, t.subject, t.title, t.description, t.due_date, t.status, t.created_at,
               u.user_name as student_name
        FROM tasks t
        LEFT JOIN user_accounts u ON t.student_id = u.id
        WHERE t.teacher_id = ?
        ORDER BY t.created_at DESC
    ");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid role']);
    exit();
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$tasks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tasks[] = $row;
}

echo json_encode([
    'success' => true, 
    'tasks' => $tasks,
    'count' => count($tasks)
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
