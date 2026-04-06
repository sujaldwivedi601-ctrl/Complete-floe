<?php
require_once "db_pdo.php";

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, teacher_name FROM teacher ORDER BY teacher_name ASC");
    $teachers = $stmt->fetchAll();
    echo json_encode($teachers);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
