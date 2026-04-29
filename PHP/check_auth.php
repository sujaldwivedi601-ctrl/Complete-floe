<?php
// check_auth.php
session_start();

function checkAuth($requiredRole = null) {
    if (!isset($_SESSION['id'])) {
        // Not logged in
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not authenticated', 'redirect' => '../FOR_everyOne/index.html']);
            exit();
        } else {
            header("Location: ../FOR_everyOne/index.html");
            exit();
        }
    }

    if ($requiredRole && $_SESSION['role'] !== $requiredRole && $_SESSION['role'] !== 'Admin') {
        // Logged in but wrong role
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access', 'redirect' => $_SESSION['role'] === 'Student' ? '../FOR_everyOne/student_dashboard.html' : '../FOR_everyOne/teacher_dashboard.html']);
            exit();
        } else {
            $redirect = $_SESSION['role'] === 'Student' ? "../FOR_everyOne/student_dashboard.html" : "../FOR_everyOne/teacher_dashboard.html";
            header("Location: $redirect");
            exit();
        }
    }
}

// If this file is called directly via AJAX, return session status
if (basename($_SERVER['PHP_SELF']) == 'check_auth.php') {
    header('Content-Type: application/json');
    echo json_encode([
        'authenticated' => isset($_SESSION['id']),
        'role' => $_SESSION['role'] ?? null,
        'user_name' => $_SESSION['user_name'] ?? null
    ]);
}
?>
