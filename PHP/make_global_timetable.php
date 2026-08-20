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
$college_id = $_SESSION['college_id'] ?? null;

// Fallback: get college_id from DB if session is missing it
if (!$college_id) {
    $fallbackStmt = mysqli_prepare($conn, "SELECT college_id FROM user_accounts WHERE id = ?");
    if ($fallbackStmt) {
        mysqli_stmt_bind_param($fallbackStmt, "i", $user_id);
        mysqli_stmt_execute($fallbackStmt);
        $fallbackResult = mysqli_stmt_get_result($fallbackStmt);
        if ($row = mysqli_fetch_assoc($fallbackResult)) {
            $college_id = $row['college_id'] ?? null;
            $_SESSION['college_id'] = $college_id;
        }
        mysqli_stmt_close($fallbackStmt);
    }
}

if (!$college_id) {
    echo json_encode(['success' => false, 'error' => 'No college associated with your account']);
    exit();
}

// First: ensure the timetable's college_id is set correctly (it may be NULL if not set on save)
// Update college_id on the timetable if it belongs to this user
$fixStmt = mysqli_prepare($conn, "UPDATE timetables SET college_id = ? WHERE id = ? AND user_id = ? AND (college_id IS NULL OR college_id = 0)");
if ($fixStmt) {
    mysqli_stmt_bind_param($fixStmt, "iii", $college_id, $timetable_id, $user_id);
    mysqli_stmt_execute($fixStmt);
    mysqli_stmt_close($fixStmt);
}

// Now make it global — allow any timetable owned by this user in this college
// Removed the restrictive "type = 'institution'" check since teachers may use different types
$stmt = mysqli_prepare($conn, "UPDATE timetables SET is_public = 1, college_id = ? WHERE id = ? AND user_id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iii", $college_id, $timetable_id, $user_id);
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode(['success' => true, 'message' => 'Timetable is now global! Students in your college can see it.']);
        } else {
            // Check if timetable exists and is already global
            $checkStmt = mysqli_prepare($conn, "SELECT id, is_public, user_id FROM timetables WHERE id = ?");
            mysqli_stmt_bind_param($checkStmt, "i", $timetable_id);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            if ($checkRow = mysqli_fetch_assoc($checkResult)) {
                if ($checkRow['is_public'] == 1) {
                    echo json_encode(['success' => true, 'message' => 'Timetable is already global!']);
                } elseif ($checkRow['user_id'] != $user_id) {
                    echo json_encode(['success' => false, 'error' => 'You do not own this timetable']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Timetable not found']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Timetable not found']);
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
