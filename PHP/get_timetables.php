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

if ($role === 'Student') {
    // Students only see public timetables for their college
    $college_id = $_SESSION['college_id'] ?? 0;
    $stmt = mysqli_prepare($conn, "SELECT id, title, type, is_public, created_at FROM timetables WHERE college_id = ? AND is_public = 1 ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $college_id);
} else {
    // Teachers/Admins see their own timetables
    $stmt = mysqli_prepare($conn, "SELECT id, title, type, is_public, created_at FROM timetables WHERE user_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$timetables = [];
while ($row = mysqli_fetch_assoc($result)) {
    $timetables[] = $row;
}

echo json_encode(['success' => true, 'timetables' => $timetables]);
mysqli_stmt_close($stmt);

mysqli_close($conn);
?>
