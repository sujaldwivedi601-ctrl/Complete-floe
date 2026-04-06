<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit();
}

$user_id = $_SESSION['id'];
$title = $data['title'] ?? 'Untitled Timetable';
$type = $data['type'] ?? 'institution';
$timetable_data = json_encode($data['timetable_data']);

$stmt = mysqli_prepare($conn, "INSERT INTO timetables (user_id, title, type, timetable_data) VALUES (?, ?, ?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $type, $timetable_data);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Timetable saved successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>
