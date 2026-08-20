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

// Allow deleting ANY timetable the teacher owns — including global ones
// First make it non-public so it can be safely removed
$stmt = mysqli_prepare($conn, "DELETE FROM timetables WHERE id = ? AND user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $timetable_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Timetable deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Timetable not found or you do not own it']);
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
