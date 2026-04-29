<?php
header("Content-Type: application/json");

// DB connection
$conn = new mysqli("localhost", "root", "", "timetable_system");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB Connection failed"]));
}

// Get JSON data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

// Insert each subject
foreach ($data as $sub) {
    $name = $conn->real_escape_string($sub['subject']);
    $theory = (int)$sub['theory'];
    $practical = (int)$sub['practical'];
    $teacher = $conn->real_escape_string($sub['teacher']);

    // Skip if no teacher assigned
    if (empty($teacher)) continue;

    $sql = "INSERT INTO subjects (subject_name, theory_hours, practical_hours, teacher_name)
            VALUES ('$name', $theory, $practical, '$teacher')";

    $conn->query($sql);
}

echo json_encode(["status" => "success"]);
$conn->close();
?>
