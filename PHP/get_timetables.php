<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

$stmt = mysqli_prepare($conn, "SELECT id, title, type, is_public, created_at FROM timetables WHERE user_id = ? OR (college_id = (SELECT college_id FROM user_accounts WHERE id = ?) AND is_public = 1) ORDER BY created_at DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $timetables = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $timetables[] = $row;
    }
    
    echo json_encode(['success' => true, 'timetables' => $timetables]);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>
