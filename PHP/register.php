<?php
include "db.php";

// Error reporting on karein taaki pata chale kahan galti hai
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = $_POST['user_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $number = $_POST['number'] ?? ''; // Default to empty if not in form
    $role = $_POST['role'] ?? '';
    $institution = $_POST['institution'] ?? '';

    // Prepared statement use karein secure registration ke liye
    $stmt = mysqli_prepare($conn, "INSERT INTO user_accounts (user_name, email, password, number, role, institution) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssss", $user_name, $email, $password, $number, $role, $institution);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: ../FOR_everyOne/login.html");
            exit();
        } else {
            die("Execution Error: " . mysqli_stmt_error($stmt));
        }
    } else {
        die("Prepare Error: " . mysqli_error($conn));
    }
}
?>