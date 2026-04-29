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
$college_id = $_SESSION['college_id'] ?? null;

$stmt = mysqli_prepare($conn, "UPDATE timetables SET is_public = 1 WHERE id = ? AND college_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $timetable_id, $college_id);
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
