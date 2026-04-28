<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['id'];
$role = $_SESSION['role'] ?? '';

if ($role !== 'Student') {
    echo json_encode(['success' => false, 'error' => 'Students only']);
    exit();
}

$response = [
    'success' => true,
    'streak' => 0,
    'tasks_this_week' => 0,
    'total_tasks' => 0,
    'completed_tasks' => 0,
    'completion_rate' => 0
];

// --- 1. Calculate streak (consecutive days with at least 1 completed task) ---
$streak = 0;
$checkDate = new DateTime();
$checkDate->modify('today');

$stmt = mysqli_prepare($conn, "
    SELECT DATE(completed_at) as comp_date, COUNT(*) as cnt 
    FROM progress_log 
    WHERE user_id = ? 
    GROUP BY DATE(completed_at) 
    ORDER BY comp_date DESC
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$datesWithProgress = [];
while ($row = mysqli_fetch_assoc($result)) {
    $datesWithProgress[$row['comp_date']] = $row['cnt'];
}
mysqli_stmt_close($stmt);

// Count consecutive days from today backwards
while (true) {
    $dateStr = $checkDate->format('Y-m-d');
    if (isset($datesWithProgress[$dateStr])) {
        $streak++;
        $checkDate->modify('-1 day');
    } else {
        break;
    }
}
$response['streak'] = $streak;

// --- 2. Tasks completed this week ---
$weekAgo = date('Y-m-d', strtotime('-7 days'));
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM progress_log WHERE user_id = ? AND completed_at >= ?");
mysqli_stmt_bind_param($stmt, "is", $user_id, $weekAgo);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$response['tasks_this_week'] = $row['cnt'];
mysqli_stmt_close($stmt);

// --- 3. Total and completed tasks ---
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done FROM tasks WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$response['total_tasks'] = $row['total'] ?? 0;
$response['completed_tasks'] = $row['done'] ?? 0;

// --- 4. Completion rate ---
if ($response['total_tasks'] > 0) {
    $response['completion_rate'] = round(($response['completed_tasks'] / $response['total_tasks']) * 100);
}

echo json_encode($response);

mysqli_close($conn);
?>