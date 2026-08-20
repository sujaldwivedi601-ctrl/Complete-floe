<?php
// Debug endpoint - shows current session + DB state for timetable troubleshooting
// URL: http://localhost/autheritation/PHP/debug_timetable.php
session_start();
include "db.php";

header('Content-Type: text/plain');

echo "=== SESSION DEBUG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['id'] ?? 'NOT SET') . "\n";
echo "User Name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "College ID (session): " . ($_SESSION['college_id'] ?? 'NOT SET') . "\n";
echo "Semester (session): " . ($_SESSION['semester'] ?? 'NOT SET') . "\n\n";

if (!isset($_SESSION['id'])) {
    echo "⚠ NOT LOGGED IN. Please login first.\n";
    exit();
}

$user_id = $_SESSION['id'];

echo "=== USER FROM DATABASE ===\n";
$stmt = mysqli_prepare($conn, "SELECT u.id, u.user_name, u.email, u.role, u.college_id, u.semester, c.name as college_name, c.code as college_code FROM user_accounts u LEFT JOIN colleges c ON u.college_id = c.id WHERE u.id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if ($user = mysqli_fetch_assoc($result)) {
    foreach ($user as $k => $v) {
        echo "$k: $v\n";
    }
} else {
    echo "⚠ User not found in DB!\n";
}
echo "\n";

$role = $_SESSION['role'] ?? '';
$college_id = $_SESSION['college_id'] ?? ($user['college_id'] ?? 0);

echo "=== TIMETABLES IN DATABASE ===\n";
$stmt = mysqli_prepare($conn, "SELECT id, user_id, college_id, title, type, is_public, created_at FROM timetables ORDER BY created_at DESC LIMIT 20");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    echo "Timetable #{$row['id']}: '{$row['title']}'\n";
    echo "  user_id={$row['user_id']}, college_id={$row['college_id']}, type={$row['type']}, is_public={$row['is_public']}\n";
    echo "  created: {$row['created_at']}\n";
}
if ($count === 0) echo "  No timetables found in DB.\n";
echo "\n";

echo "=== PUBLIC TIMETABLES FOR COLLEGE $college_id ===\n";
if ($college_id) {
    $stmt = mysqli_prepare($conn, "SELECT id, title, type, is_public FROM timetables WHERE college_id = ? AND is_public = 1");
    mysqli_stmt_bind_param($stmt, "i", $college_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $count++;
        echo "  #{$row['id']}: '{$row['title']}' (type={$row['type']})\n";
    }
    if ($count === 0) echo "  No public timetables found for college_id=$college_id.\n";
} else {
    echo "  ⚠ No college_id set - cannot lookup public timetables.\n";
}
echo "\n";

echo "=== TASKS IN DATABASE ===\n";
$stmt = mysqli_prepare($conn, "SELECT t.id, t.teacher_id, t.student_id, t.title, t.subject, t.status, u1.user_name as teacher, u2.user_name as student FROM tasks t LEFT JOIN user_accounts u1 ON t.teacher_id = u1.id LEFT JOIN user_accounts u2 ON t.student_id = u2.id ORDER BY t.created_at DESC LIMIT 20");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    echo "Task #{$row['id']}: '{$row['title']}' ({$row['subject']})\n";
    echo "  teacher={$row['teacher']} → student={$row['student']}, status={$row['status']}\n";
}
if ($count === 0) echo "  No tasks found.\n";

echo "\n=== DONE ===\n";

mysqli_close($conn);
?>
