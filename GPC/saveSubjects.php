<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");

$host = 'localhost';
$db   = 'timetable_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {

    $pdo = new PDO($dsn, $user, $pass, $options);

    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception("Invalid data received.");
    }

    $pdo->beginTransaction();

    $semestersToUpdate = array_unique(array_column($data, 'semester'));

    foreach ($semestersToUpdate as $sem) {

        $stmt = $pdo->prepare("DELETE FROM subjects WHERE class_id = ?");
        $stmt->execute([$sem]);

    }

    $insertStmt = $pdo->prepare("
        INSERT INTO subjects (class_id, subject_name, theory_hours, practical_hours, assigned_teacher)
        VALUES (:semester, :subject, :theory, :practical, :teacher)
    ");

    foreach ($data as $row) {

        $insertStmt->execute([
            'semester'  => $row['semester'],
            'subject'   => $row['subject'],
            'theory'    => $row['theory'],
            'practical' => $row['practical'],
            'teacher'   => $row['teacher'] ?? null
        ]);

    }

    $pdo->commit();

    echo "Database updated successfully";

} catch (Exception $e) {

    if (isset($pdo)) $pdo->rollBack();

    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>