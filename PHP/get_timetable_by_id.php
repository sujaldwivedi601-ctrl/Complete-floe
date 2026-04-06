<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$timetable_id = $_GET['id'] ?? null;

if (!$timetable_id) {
    echo json_encode(['success' => false, 'error' => 'ID is required']);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT id, title, type, timetable_data, created_at FROM timetables WHERE id = ? AND user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $timetable_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($timetable = mysqli_fetch_assoc($result)) {
        $timetable['timetable_data'] = json_decode($timetable['timetable_data'], true);
        echo json_encode(['success' => true, 'timetable' => $timetable]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Timetable not found or access denied']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>
