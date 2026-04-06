<?php
// login.php
session_start(); 
include "db.php";

$username = mysqli_real_escape_string($conn, $_POST['user_name']);
$password = $_POST['password'];

// ✅ FIX: Ensure 'id' is selected in the query
$sql = "SELECT id, email, password FROM users WHERE user_name='$username'";
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
