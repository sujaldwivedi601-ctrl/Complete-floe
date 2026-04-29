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
$goal_text = $data['goal'] ?? '';

if (!$goal_text) {
    echo json_encode(['success' => false, 'error' => 'Goal text required']);
    exit();
}

$stmt = mysqli_prepare($conn, "
    INSERT INTO goals (user_id, goal_text) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE goal_text = VALUES(goal_text), updated_at = NOW()
");

mysqli_stmt_bind_param($stmt, "is", $user_id, $goal_text);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Goal saved!', 'timestamp' => date('Y-m-d H:i:s')]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
