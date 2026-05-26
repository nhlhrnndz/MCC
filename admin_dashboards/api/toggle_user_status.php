<?php
// toggle_user_status.php
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
$currentStatus = $data['current_status'] ?? null;

if (!$userId || !$role || !$currentStatus) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

try {
    if ($role === 'User') {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE admin_users SET status = ? WHERE id = ?");
    }
    
    $stmt->bind_param("si", $newStatus, $userId);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'new_status' => $newStatus]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>