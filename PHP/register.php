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
    
    $college_id = saveCollege($conn, $institution, $institution_code);
    
    $stmt = mysqli_prepare($conn, "INSERT INTO user_accounts (user_name, email, password, number, role, institution, college_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssi", $user_name, $email, $password, $number, $role, $institution, $college_id);
        
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