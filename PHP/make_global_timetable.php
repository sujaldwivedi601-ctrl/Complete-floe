<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'No timetable ID provided']);
    exit();
}

$timetable_id = $data['id'];
$user_id = $_SESSION['id'];
$college_id = $_SESSION['college_id'] ?? null;

if (!$college_id) {
    echo json_encode(['success' => false, 'error' => 'No college associated with your account']);
    exit();
}

$stmt = mysqli_prepare($conn, "UPDATE timetables SET is_public = 1 WHERE id = ? AND (user_id = ? OR college_id = ?) AND type = 'institution'");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iii", $timetable_id, $user_id, $college_id);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Timetable is now global!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Timetable not found or already global']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>