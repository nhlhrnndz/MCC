<?php
// MCC\admin_dashboards\api\mark_admin_notification_read.php
session_start();
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Not logged in. Please log in first.'
    ]);
    exit;
}

// Check if user is admin - FLEXIBLE CHECK
$is_admin = false;

// Check different possible admin session variables
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $is_admin = true;
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $is_admin = true;
} elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $is_admin = true;
} else {
    // Additional check: query the database to verify admin status
    $user_id = $_SESSION['user_id'];
    $check_admin_sql = "SELECT role, user_type FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_admin_sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            if (($row['role'] ?? '') === 'admin' || ($row['user_type'] ?? '') === 'admin') {
                $is_admin = true;
                // Update session for future requests
                $_SESSION['user_type'] = 'admin';
            }
        }
        $stmt->close();
    }
}

if (!$is_admin) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Admin privileges required.'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? null;

if (!$notification_id) {
    echo json_encode([
        'success' => false, 
        'error' => 'Notification ID required'
    ]);
    exit;
}

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Mark notification as read
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET status = 'read' 
        WHERE id = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $notification_id);
    $success = $stmt->execute();

    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => 'Notification marked as read'
        ]);
    } else {
        throw new Exception("Failed to update notification: " . $conn->error);
    }

} catch (Exception $e) {
    error_log("Mark admin notification read error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>