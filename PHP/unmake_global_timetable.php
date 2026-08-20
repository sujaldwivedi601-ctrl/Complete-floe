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

$timetable_id = intval($data['id']);
$user_id = $_SESSION['id'];

// Set is_public = 0 — only owner can undo their own global timetable
$stmt = mysqli_prepare($conn, "UPDATE timetables SET is_public = 0 WHERE id = ? AND user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $timetable_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Timetable is now private. Students can no longer see it.']);
        } else {
            // Check if already private
            $checkStmt = mysqli_prepare($conn, "SELECT id, is_public FROM timetables WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($checkStmt, "ii", $timetable_id, $user_id);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            if ($row = mysqli_fetch_assoc($checkResult)) {
                echo json_encode(['success' => true, 'message' => 'Timetable is already private.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Timetable not found or you do not own it']);
            }
            mysqli_stmt_close($checkStmt);
        }
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
