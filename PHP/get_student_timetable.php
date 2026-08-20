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

// Fallback: fetch college_id from DB if session is missing it
if (!$college_id) {
    $fallbackStmt = mysqli_prepare($conn, "SELECT college_id FROM user_accounts WHERE id = ?");
    if ($fallbackStmt) {
        mysqli_stmt_bind_param($fallbackStmt, "i", $user_id);
        mysqli_stmt_execute($fallbackStmt);
        $fallbackResult = mysqli_stmt_get_result($fallbackStmt);
        if ($row = mysqli_fetch_assoc($fallbackResult)) {
            $college_id = $row['college_id'] ?? 0;
            $_SESSION['college_id'] = $college_id;
        }
        mysqli_stmt_close($fallbackStmt);
    }
}

$response = [
    'success' => true,
    'college_timetable' => null,
    'custom_blocks' => [],
    'tasks' => []
];

// 1. Get college published timetable (most recent one)
if ($college_id) {
    $stmt = mysqli_prepare($conn, "SELECT id, title, type, timetable_data FROM timetables WHERE college_id = ? AND is_public = 1 ORDER BY created_at DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $college_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $response['college_timetable'] = json_decode($row['timetable_data'], true);
        $response['timetable_title'] = $row['title'];
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

// 3. Get pending teacher-assigned tasks for the student
$stmt = mysqli_prepare($conn, "
    SELECT t.id, t.title, t.subject, t.due_date, t.status,
           u.user_name as teacher_name
    FROM tasks t
    LEFT JOIN user_accounts u ON t.teacher_id = u.id
    WHERE t.student_id = ? AND t.status = 'pending'
    ORDER BY t.due_date ASC, t.created_at DESC
    LIMIT 20
");
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
