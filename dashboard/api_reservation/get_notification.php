<?php
session_start();

// DEBUG: Check current directory structure
error_log("Current directory: " . __DIR__);
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));

// Fix the include paths - now it's in the same dashboard folder
require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../notification_helper.php'; // Changed path

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'User not logged in',
        'session_debug' => $_SESSION
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Test database connection first
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
    }
    
    $notificationHelper = new NotificationHelper($conn);
    
    // Get notifications
    $notifications_result = $notificationHelper->getUserNotifications($user_id, 20);
    
    if (!$notifications_result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $notifications = [];
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'time_ago' => getTimeAgo($row['created_at'])
        ];
    }
    
    $unread_count = $notificationHelper->getUnreadCount($user_id);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unread_count,
        'count' => count($notifications),
        'debug' => [
            'user_id' => $user_id,
            'notifications_found' => count($notifications)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Notification system error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'System error: ' . $e->getMessage()
    ]);
}

function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $diff = $current - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ($mins === 1 ? ' minute ago' : ' minutes ago');
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ($hours === 1 ? ' hour ago' : ' hours ago');
    }
    if ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ($days === 1 ? ' day ago' : ' days ago');
    }
    return date('M j, Y', $time);
}
?>