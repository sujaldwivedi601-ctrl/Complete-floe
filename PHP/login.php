<?php
// login.php
session_start();
include "db.php";
$user = $_POST['user_name'];
$password = $_POST['password'];
if($user=="admin" && $password=="C04"){
      header("Location: /timetable/index.php");
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$username = mysqli_real_escape_string($conn, $_POST['user_name']);
$password = $_POST['password'];
$role = $_POST['role'] ?? 'student';

$sql = "SELECT id, email, password, role, college_id FROM user_accounts WHERE user_name='$username'";
$result = mysqli_query($conn, $sql);

function redirectByRole($userRole) {
    if ($userRole === 'Teacher') {
        header("Location: ../FOR_everyOne/teacher_dashboard.html");
    } elseif ($userRole === 'Student') {
        header("Location: ../FOR_everyOne/student_dashboard.html");
    } else {
        header("Location: ../FOR_everyOne/index.html");
    }
    exit();
}

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    // Check if the selected role matches the user's actual role
    $inputRole = ucfirst(strtolower($role)); // Normalize input role (e.g. 'teacher' -> 'Teacher')
    if ($inputRole !== $row['role'] && $username !== 'admin') {
        die("Invalid role selected for this account. Please select your correct role.");
    }

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
        $_SESSION['user_name'] = $username; // Added user_name to session
        $_SESSION['id'] = $row['id'];
        $_SESSION['role'] = $row['role']; // Strictly use DB role
        $_SESSION['college_id'] = $row['college_id'] ?? null;
        
        // Fetch semester if student
        if ($row['role'] === 'Student') {
            $semStmt = mysqli_prepare($conn, "SELECT semester FROM user_accounts WHERE id = ?");
            if ($semStmt) {
                mysqli_stmt_bind_param($semStmt, "i", $row['id']);
                mysqli_stmt_execute($semStmt);
                $semResult = mysqli_stmt_get_result($semStmt);
                if ($semRow = mysqli_fetch_assoc($semResult)) {
                    $_SESSION['semester'] = $semRow['semester'] ?? 1;
                }
                mysqli_stmt_close($semStmt);
            }
        }

        redirectByRole($row['role']);
    } 
    else {
        echo "Wrong Password";
    }
} else {
    echo "User does not exist";
}
?>
