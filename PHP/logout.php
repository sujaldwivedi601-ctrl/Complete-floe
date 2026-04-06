<?php
session_start();
session_unset();
session_destroy();
header("Location: ../FOR_everyOne/login.html");
exit();
?>
