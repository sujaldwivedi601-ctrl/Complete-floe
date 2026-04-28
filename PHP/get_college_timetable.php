<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$college_id = $_SESSION['college_id'] ?? 0;

if (!$college_id) {
    echo json_encode(['success' => false, 'error' => 'No college associated']);
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT id, title, type, timetable_data, created_at FROM timetables WHERE college_id = ? AND is_public = 1 ORDER BY created_at DESC LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $college_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($timetable = mysqli_fetch_assoc($result)) {
    $timetable['timetable_data'] = json_decode($timetable['timetable_data'], true);
    echo json_encode(['success' => true, 'timetable' => $timetable]);
} else {
    echo json_encode(['success' => false, 'error' => 'No published timetable found']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>