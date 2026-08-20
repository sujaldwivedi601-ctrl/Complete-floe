<?php
include "db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

function saveCollege($conn, $name, $code) {
    $name = trim($name);
    $code = strtoupper(trim($code));
    
    $stmt = mysqli_prepare($conn, "SELECT id FROM colleges WHERE code = ?");
    mysqli_stmt_bind_param($stmt, "s", $code);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['id'];
    }
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO colleges (name, code, created_at) VALUES (?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "ss", $name, $code);
    mysqli_stmt_execute($stmt);
    
    return mysqli_insert_id($conn);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = $_POST['user_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $number = $_POST['number'] ?? '';
    $role = $_POST['role'] ?? '';
    $institution = $_POST['institution'] ?? '';
    $institution_code = $_POST['institution_code'] ?? '';
    $semester = $_POST['semester'] ?? 1;
    
    $college_id = saveCollege($conn, $institution, $institution_code);
    
    // Normalize role for database
    $roleNormalized = ucfirst(strtolower($role)); // 'student' -> 'Student'
    
    // Check if email already exists
    $check = mysqli_prepare($conn, "SELECT id FROM user_accounts WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);
    if (mysqli_stmt_num_rows($check) > 0) {
        mysqli_stmt_close($check);
        die("Error: This email is already registered. <a href='javascript:history.back()'>Go back</a>");
    }
    mysqli_stmt_close($check);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO user_accounts (user_name, email, password, number, role, institution, college_id, semester) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssii", $user_name, $email, $password, $number, $roleNormalized, $institution, $college_id, $semester);
        
        if (mysqli_stmt_execute($stmt)) {
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Start session and log them in
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['id'] = $new_user_id;
            $_SESSION['user_name'] = $user_name;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $roleNormalized;
            $_SESSION['college_id'] = $college_id;
            if ($roleNormalized === 'Student') {
                $_SESSION['semester'] = $semester;
            }
            
            // Redirect based on role
            if ($roleNormalized === 'Teacher') {
                header("Location: ../FOR_everyOne/teacher_dashboard.html");
            } elseif ($roleNormalized === 'Student') {
                header("Location: ../FOR_everyOne/student_dashboard.html");
            } else {
                header("Location: ../FOR_everyOne/index.html");
            }
            exit();
        } else {
            die("Execution Error: " . mysqli_stmt_error($stmt));
        }
    } else {
        die("Prepare Error: " . mysqli_error($conn));
    }
}
?>
