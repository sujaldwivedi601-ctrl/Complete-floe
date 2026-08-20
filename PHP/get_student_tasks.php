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
// These come from the `tasks` table where a teacher assigned them to this student
$stmt = mysqli_prepare($conn, "
    SELECT t.id, t.title, t.subject, t.description, t.due_date, t.status, t.created_at,
           u.user_name as teacher_name
    FROM tasks t
    LEFT JOIN user_accounts u ON t.teacher_id = u.id
    WHERE t.student_id = ? 
    ORDER BY 
        CASE t.status WHEN 'pending' THEN 0 WHEN 'done' THEN 1 WHEN 'completed' THEN 2 END ASC,
        t.created_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $row['source'] = 'teacher';
    // Normalize status: treat 'completed' same as 'done' for display
    if ($row['status'] === 'completed') {
        $row['status'] = 'done';
    }
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
