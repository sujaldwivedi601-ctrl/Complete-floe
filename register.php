<?php

include "db.php";

$user_name = $_POST['user_name'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$number = $_POST['number'];

$sql = "INSERT INTO users (user_name, email, password, number)
        VALUES ('$user_name', '$email', '$password', '$number')";

if (mysqli_query($conn, $sql)) {
     header("Location: ./target.html");
} else {
    echo "Error: " . mysqli_error($conn);
}

?>