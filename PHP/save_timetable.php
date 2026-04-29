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
$college_id = $_SESSION['college_id'] ?? null;
$title = $data['title'] ?? 'Untitled Timetable';
$type = $data['type'] ?? 'institution';
$is_public = $data['is_public'] ?? 0;
$timetable_data = json_encode($data['timetable_data']);

$stmt = mysqli_prepare($conn, "INSERT INTO timetables (user_id, college_id, title, type, is_public, timetable_data) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iisssi", $user_id, $college_id, $title, $type, $is_public, $timetable_data);
    if (mysqli_stmt_execute($stmt)) {
        $timetable_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Timetable saved successfully', 'timetable_id' => $timetable_id]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>
