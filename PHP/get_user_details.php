<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];

// Fetch user details from database
$stmt = mysqli_prepare($conn, "SELECT user_name, email, role, institution FROM users WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['error' => 'Database error']);
}
?>
