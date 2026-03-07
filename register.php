<?php

include "db.php";

$user_name = $_POST['user_name'];
$email = $_POST['email'];
$password = $_POST['password'];
$number = $_POST['number'];

$sql = "INSERT INTO users (user_name, email, password, number)
        VALUES ('$user_name', '$email', '$password', '$number')";

if (mysqli_query($conn, $sql)) {
    echo "Registration Successful!";
} else {
    echo "Error: " . mysqli_error($conn);
}

?>