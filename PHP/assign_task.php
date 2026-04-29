<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Teacher') {
    echo json_encode(['success' => false, 'error' => 'Only teachers can assign tasks']);
    exit();
}

$teacher_id = $_SESSION['id'];
$college_id = $_SESSION['college_id'] ?? 0;

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit();
}

$subject = $data['subject'] ?? '';
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$due_date = $data['due_date'] ?? null;
$student_id = $data['student_id'] ?? null;
$semester = $data['semester'] ?? null;

// Validate
if (!$title || !$subject) {
    echo json_encode(['success' => false, 'error' => 'Subject and title are required']);
    exit();
}

$inserted = 0;

// Determine which students to assign to
if ($student_id) {
    // Individual student - only one task
    $student_id = intval($student_id);
    $stmt = mysqli_prepare($conn, "
        INSERT INTO tasks (teacher_id, student_id, college_id, subject, title, description, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iiissss", $teacher_id, $student_id, $college_id, $subject, $title, $description, $due_date);
    
    if (mysqli_stmt_execute($stmt)) {
        $inserted = 1;
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_stmt_error($stmt)]);
        exit();
    }
    mysqli_stmt_close($stmt);
    
} elseif ($semester) {
    // Specific semester - assign to all students in that semester
    $semester = intval($semester);
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_accounts WHERE role = 'Student' AND college_id = ? AND semester = ?");
    mysqli_stmt_bind_param($stmt, "ii", $college_id, $semester);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $insertStmt = mysqli_prepare($conn, "
        INSERT INTO tasks (teacher_id, student_id, college_id, subject, title, description, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    while ($student = mysqli_fetch_assoc($result)) {
        mysqli_stmt_bind_param($insertStmt, "iiissss", $teacher_id, $student['id'], $college_id, $subject, $title, $description, $due_date);
        mysqli_stmt_execute($insertStmt);
        $inserted++;
    }
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($insertStmt);
    
} else {
    // All students in the college
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_accounts WHERE role = 'Student' AND college_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $college_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $insertStmt = mysqli_prepare($conn, "
        INSERT INTO tasks (teacher_id, student_id, college_id, subject, title, description, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    while ($student = mysqli_fetch_assoc($result)) {
        mysqli_stmt_bind_param($insertStmt, "iiissss", $teacher_id, $student['id'], $college_id, $subject, $title, $description, $due_date);
        mysqli_stmt_execute($insertStmt);
        $inserted++;
    }
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($insertStmt);
}

$message = $inserted === 1 ? "Task assigned to 1 student" : "Task assigned to $inserted students";
echo json_encode(['success' => true, 'message' => $message, 'count' => $inserted]);

mysqli_close($conn);
?>