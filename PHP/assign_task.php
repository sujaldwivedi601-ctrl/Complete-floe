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

// Fallback: get college_id from DB if session is missing
if (!$college_id) {
    $fallbackStmt = mysqli_prepare($conn, "SELECT college_id FROM user_accounts WHERE id = ?");
    if ($fallbackStmt) {
        mysqli_stmt_bind_param($fallbackStmt, "i", $teacher_id);
        mysqli_stmt_execute($fallbackStmt);
        $fallbackResult = mysqli_stmt_get_result($fallbackStmt);
        if ($row = mysqli_fetch_assoc($fallbackResult)) {
            $college_id = $row['college_id'] ?? 0;
            $_SESSION['college_id'] = $college_id;
        }
        mysqli_stmt_close($fallbackStmt);
    }
}

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

// Handle empty due_date
if ($due_date === '') $due_date = null;

// Validate
if (!$title || !$subject) {
    echo json_encode(['success' => false, 'error' => 'Subject and title are required']);
    exit();
}

$inserted = 0;
$errors = [];

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
        $errors[] = mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
    
} elseif ($semester) {
    // Specific semester - assign to all students in that semester
    $semester = intval($semester);
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_accounts WHERE role = 'Student' AND college_id = ? AND semester = ?");
    mysqli_stmt_bind_param($stmt, "ii", $college_id, $semester);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $students = [];
    while ($student = mysqli_fetch_assoc($result)) {
        $students[] = $student['id'];
    }
    mysqli_stmt_close($stmt);

    if (count($students) === 0) {
        echo json_encode(['success' => false, 'error' => "No students found in semester $semester for your college"]);
        mysqli_close($conn);
        exit();
    }

    $insertStmt = mysqli_prepare($conn, "
        INSERT INTO tasks (teacher_id, student_id, college_id, subject, title, description, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($students as $sid) {
        mysqli_stmt_bind_param($insertStmt, "iiissss", $teacher_id, $sid, $college_id, $subject, $title, $description, $due_date);
        if (mysqli_stmt_execute($insertStmt)) {
            $inserted++;
        } else {
            $errors[] = mysqli_stmt_error($insertStmt);
        }
    }
    mysqli_stmt_close($insertStmt);
    
} else {
    // All students in the college
    $stmt = mysqli_prepare($conn, "SELECT id FROM user_accounts WHERE role = 'Student' AND college_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $college_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $students = [];
    while ($student = mysqli_fetch_assoc($result)) {
        $students[] = $student['id'];
    }
    mysqli_stmt_close($stmt);

    if (count($students) === 0) {
        echo json_encode(['success' => false, 'error' => 'No students found in your college']);
        mysqli_close($conn);
        exit();
    }

    $insertStmt = mysqli_prepare($conn, "
        INSERT INTO tasks (teacher_id, student_id, college_id, subject, title, description, due_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($students as $sid) {
        mysqli_stmt_bind_param($insertStmt, "iiissss", $teacher_id, $sid, $college_id, $subject, $title, $description, $due_date);
        if (mysqli_stmt_execute($insertStmt)) {
            $inserted++;
        } else {
            $errors[] = mysqli_stmt_error($insertStmt);
        }
    }
    mysqli_stmt_close($insertStmt);
}

if ($inserted > 0) {
    $message = $inserted === 1 ? "Task assigned to 1 student" : "Task assigned to $inserted students";
    echo json_encode(['success' => true, 'message' => $message, 'count' => $inserted]);
} else {
    $errorMsg = count($errors) > 0 ? implode('; ', $errors) : 'No students found to assign to';
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}

mysqli_close($conn);
?>
