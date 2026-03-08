<?php

$conn = mysqli_connect("localhost","root","","authentication");

if(!$conn){
    die("Connection Failed");
}

$username = $_POST['user_name'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE user_name='$username'";

$result = mysqli_query($conn,$sql);



 if(mysqli_num_rows($result) > 0){

    $row = mysqli_fetch_assoc($result);

       if($username == "admin" && $password == "C04"){ 
           header("Location: ../GPC/GPC.html");
           exit();
       } elseif (password_verify($password,$row['password'])){
                header("Location: .FOR_everyOne/target.html");
                exit();
                }else{
                    echo "Wrong Password";
                    }

}else{
    echo "User does not exist";
}


?>