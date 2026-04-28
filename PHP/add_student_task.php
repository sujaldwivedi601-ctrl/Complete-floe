<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['id'];
$title = $data['title'] ?? '';
$subject = $data['subject'] ?? '';
$planned_time = $data['planned_time'] ?? 1;

if (!$title || !$subject) {
    echo json_encode(['success' => false, 'error' => 'Title and subject required']);
    exit();
}

$stmt = mysqli_prepare($conn, "INSERT INTO student_tasks (user_id, title, subject, planned_time, status) VALUES (?, ?, ?, ?, 'pending')");
mysqli_stmt_bind_param($stmt, "issd", $user_id, $title, $subject, $planned_time);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>