<?php
require_once "db_pdo.php";

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$id = (int) ($data['id'] ?? 0);

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM teacher WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
