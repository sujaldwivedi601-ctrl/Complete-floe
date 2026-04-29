<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$college_id = $_SESSION['college_id'] ?? 0;

$response = [
    'success' => true,
    'college_timetable' => null,
    'custom_blocks' => [],
    'tasks' => []
];

// 1. Get college published timetable
if ($college_id) {
    $stmt = mysqli_prepare($conn, "SELECT timetable_data FROM timetables WHERE college_id = ? AND is_public = 1 ORDER BY created_at DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $college_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response['college_timetable'] = json_decode($row['timetable_data'], true);
    }
    mysqli_stmt_close($stmt);
}

// 2. Get student's custom blocks
$stmt = mysqli_prepare($conn, "SELECT custom_blocks FROM student_timetables WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $response['custom_blocks'] = json_decode($row['custom_blocks'], true) ?? [];
}
mysqli_stmt_close($stmt);

// 3. Get pending tasks for today and upcoming
$stmt = mysqli_prepare($conn, "SELECT id, subject, task_name, due_date, priority FROM tasks WHERE user_id = ? AND (due_date >= CURDATE() OR due_date IS NULL) ORDER BY due_date ASC LIMIT 20");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $response['tasks'][] = $row;
}
mysqli_stmt_close($stmt);

echo json_encode($response);

mysqli_close($conn);
?>
