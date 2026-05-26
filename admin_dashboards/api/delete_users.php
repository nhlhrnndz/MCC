<?php
// delete_user.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['id'] ?? null;
$role = $data['role'] ?? null;

if (!$userId || !$role) {
    echo json_encode(['error' => 'Missing user ID or role']);
    exit();
}

try {
    if ($role === 'User') {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    } else {
        $stmt = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
    }
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>