<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$timetable_id = $data['id'] ?? null;

if ($_SESSION['role'] === 'Student') {
    echo json_encode(['success' => false, 'error' => 'Students cannot delete timetables']);
    exit();
}

if (!$timetable_id) {
    echo json_encode(['success' => false, 'error' => 'ID is required']);
    exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM timetables WHERE id = ? AND user_id = ? AND is_public = 0");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $timetable_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Timetable deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Timetable not found or access denied']);
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
