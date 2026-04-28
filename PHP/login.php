<?php
// login.php
session_start();
include "db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$username = mysqli_real_escape_string($conn, $_POST['user_name']);
$password = $_POST['password'];
$role = $_POST['role'] ?? 'student';

$sql = "SELECT id, email, password, role, college_id FROM user_accounts WHERE user_name='$username'";
$result = mysqli_query($conn, $sql);

function redirectByRole($userRole, $selectedRole) {
    if ($userRole === 'Teacher' || $selectedRole === 'teacher') {
        header("Location: ../FOR_everyOne/teacher_dashboard.html");
    } else {
        header("Location: ../FOR_everyOne/index.html");
    }
    exit();
}

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    // ADMIN LOGIN
    if ($username == "admin" && $password == "C04") {
        $_SESSION['email'] = "admin"; 
        $_SESSION['user_name'] = "admin";
        $_SESSION['id'] = 0;
        $_SESSION['role'] = 'Admin';
        $_SESSION['college_id'] = 1;
        header("Location: ../FOR_everyOne/generate_timetable.html");
        exit();
    } 
    
    // NORMAL USER LOGIN
    elseif (password_verify($password, $row['password'])) {
        $_SESSION['email'] = $row['email']; 
        $_SESSION['id'] = $row['id'];
        $_SESSION['role'] = $row['role'] ?? $selectedRole;
        $_SESSION['college_id'] = $row['college_id'] ?? null;

        redirectByRole($row['role'], $role);
    } 
    else {
        echo "Wrong Password";
    }
} else {
    echo "User does not exist";
}
?>