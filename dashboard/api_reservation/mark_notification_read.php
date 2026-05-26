<?php
session_start();
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../notification_helper.php'; // Updated path

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$notification_id) {
    echo json_encode(['success' => false, 'error' => 'Notification ID required']);
    exit;
}

try {
    $notificationHelper = new NotificationHelper($conn);
    $success = $notificationHelper->markAsRead($notification_id, $user_id);
    
    echo json_encode(['success' => $success]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>