<?php
require_once "db_pdo.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$teacher_name = $data['teacher_name'] ?? '';

if (empty($teacher_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Teacher name is required']);
    exit;
}

try {
    // Check if teacher already exists
    $stmt = $pdo->prepare("SELECT id FROM teacher WHERE teacher_name = ?");
    $stmt->execute([$teacher_name]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Teacher already exists']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO teacher (teacher_name) VALUES (?)");
    $stmt->execute([$teacher_name]);
    $id = $pdo->lastInsertId();

    echo json_encode(['id' => (int)$id, 'teacher_name' => $teacher_name]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
