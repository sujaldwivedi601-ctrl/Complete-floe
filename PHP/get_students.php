<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Teacher') {
    echo json_encode(['success' => false, 'error' => 'Teachers only']);
    exit();
}

$college_id = $_SESSION['college_id'] ?? 0;

$stmt = mysqli_prepare($conn, "SELECT id, user_name, email FROM user_accounts WHERE role = 'Student' AND college_id = ? ORDER BY user_name");
mysqli_stmt_bind_param($stmt, "i", $college_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

echo json_encode(['success' => true, 'students' => $students]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>