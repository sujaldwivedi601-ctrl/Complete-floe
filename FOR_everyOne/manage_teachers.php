<?php
// manage_teachers.php
ob_start(); 
session_start();
require_once 'db.php'; 
ob_clean(); 

header('Content-Type: application/json');

// 1. Define variables FIRST (Fixes the "Undefined variable $action" error)
$action = $_GET['action'] ?? '';
$user_id = $_SESSION['id'] ?? 0; 
$email = $_SESSION['email'] ?? '';

// 2. Handle Fetch Action
if ($action === 'fetch') {
    // ✅ FIXED: Using mysqli prepared statements instead of PDO
    $stmt = $conn->prepare("SELECT id, teacher_name FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

// 3. Handle Add Action
if ($action === 'add') {
    $data = json_decode(file_get_contents("php://input"), true);
    $name = trim($data['name'] ?? '');
    
    if ($name && $user_id > 0) {
        // ✅ FIXED: Added user_id to query and used bind_param
        $stmt = $conn->prepare("INSERT INTO teachers (user_id, user_email, teacher_name) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $email, $name);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "id" => $conn->insert_id]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid data or session expired"]);
    }
    exit;
}

// 4. Handle Delete Action
if ($action === 'delete') {
    $id = $_GET['id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    }
    exit;
}
?>
