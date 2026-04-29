<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['timetable_id'])) {
    echo json_encode(['success' => false, 'error' => 'No timetable ID provided']);
    exit();
}

$timetable_id = $data['timetable_id'];
$user_id = $_SESSION['id'];
$college_id = $_SESSION['college_id'] ?? null;

// Only teachers/admins can publish
if ($_SESSION['role'] !== 'Teacher' && $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Only teachers can publish timetables']);
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE timetables SET is_public = 1 WHERE id = ? AND (user_id = ? OR college_id = ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iii", $timetable_id, $user_id, $college_id);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Timetable published to students!']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>
