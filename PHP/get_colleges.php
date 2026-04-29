<?php
// get_colleges.php
include "db.php";

header("Content-Type: application/json");

$search = $_GET['q'] ?? '';

if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $sql = "SELECT id, name, code FROM colleges WHERE name LIKE '%$search%' OR code LIKE '%$search%' ORDER BY name LIMIT 20";
} else {
    $sql = "SELECT id, name, code FROM colleges ORDER BY name LIMIT 50";
}

$result = mysqli_query($conn, $sql);
$colleges = [];

while ($row = mysqli_fetch_assoc($result)) {
    $colleges[] = $row;
}

echo json_encode($colleges);
?>
