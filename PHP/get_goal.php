<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

$stmt = mysqli_prepare($conn, "SELECT goal_text, updated_at FROM goals WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'goal' => $row['goal_text'],
        'updated_at' => $row['updated_at']
    ]);
} else {
    echo json_encode(['success' => true, 'goal' => null]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>