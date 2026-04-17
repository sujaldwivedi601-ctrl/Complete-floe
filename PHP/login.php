<?php
// login.php
session_start();
include "db.php";

// Add error reporting to catch database issues
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$username = mysqli_real_escape_string($conn, $_POST['user_name']);
$password = $_POST['password'];

// ✅ FIX: Using user_accounts table instead of users
$sql = "SELECT id, email, password FROM user_accounts WHERE user_name='$username'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    // 🔐 ADMIN LOGIN
    if ($username == "admin" && $password == "C04") {
        $_SESSION['email'] = "admin"; 
        $_SESSION['user_name'] = "admin";
        $_SESSION['id'] = 0; // Admin ID
        header("Location: /timetable/index.php");
        exit();
    } 
    
    // 👤 NORMAL USER LOGIN
    elseif (password_verify($password, $row['password'])) {
        $_SESSION['email'] = $row['email']; 
        $_SESSION['id'] = $row['id']; // ✅ This will now be correctly stored

        header("Location: ../FOR_everyOne/index.html");
        exit();
    } 
    else {
        echo "Wrong Password";
    }
} else {
    echo "User does not exist";
}
?>
