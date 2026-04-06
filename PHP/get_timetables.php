<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

$stmt = mysqli_prepare($conn, "SELECT id, title, type, created_at FROM timetables WHERE user_id = ? ORDER BY created_at DESC");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
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
