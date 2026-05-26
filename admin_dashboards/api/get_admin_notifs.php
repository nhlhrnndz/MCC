<?php
// MCC\admin_dashboards\api\get_admin_notifs.php
session_start();
header('Content-Type: application/json');

// Database connection
require_once __DIR__ . '/../../db_connect.php';

// Check if user is logged in as admin - CONSISTENT CHECK
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Not logged in. Please log in first.'
    ]);
    exit;
}

// Check if user is admin - FLEXIBLE CHECK
$is_admin = false;

// Check different possible admin session variables
if (isset($_SESSION['admin_id'])) {
    $is_admin = true;
} elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    $is_admin = true;
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $is_admin = true;
} else {
    // Additional check: query the database to verify admin status
    try {
        $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        if ($user_id) {
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
    } catch (Exception $e) {
        // Continue without database check
    }
}

if (!$is_admin) {
    echo json_encode([
        'success' => false, 
        'error' => 'Admin privileges required. Please contact administrator.'
    ]);
    exit;
}

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get filter parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // Build the base query for notifications
    $query = "
        SELECT n.*, 
               u.username as user_name,
               u.email as user_email
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC 
        LIMIT ? OFFSET ?
    ";

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'status' => $row['status'],
                'user_name' => $row['user_name'],
                'user_email' => $row['user_email'],
                'created_at' => $row['created_at'],
                'time_ago' => getTimeAgo($row['created_at'])
            ];
        }
    }

    // Get total counts for stats
    $count_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread,
        SUM(CASE WHEN type = 'reservation' THEN 1 ELSE 0 END) as reservations,
        SUM(CASE WHEN type = 'event' THEN 1 ELSE 0 END) as events,
        SUM(CASE WHEN type = 'payment' THEN 1 ELSE 0 END) as payments,
        SUM(CASE WHEN type = 'user' THEN 1 ELSE 0 END) as users,
        SUM(CASE WHEN type = 'system' THEN 1 ELSE 0 END) as system,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week
    FROM notifications";

    $count_result = $conn->query($count_query);
    
    if (!$count_result) {
        throw new Exception("Count query failed: " . $conn->error);
    }
    
    $counts = $count_result->fetch_assoc();

    // Calculate percentages for summary
    $total = $counts['total'] ?: 1; // Avoid division by zero
    $reservations_percent = round(($counts['reservations'] / $total) * 100);
    $events_percent = round(($counts['events'] / $total) * 100);
    $payments_percent = round(($counts['payments'] / $total) * 100);
    $system_percent = round(($counts['system'] / $total) * 100);
    $users_percent = round(($counts['users'] / $total) * 100);

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'stats' => [
            'total' => $counts['total'] ?? 0,
            'unread' => $counts['unread'] ?? 0,
            'today' => $counts['today'] ?? 0,
            'this_week' => $counts['this_week'] ?? 0,
            'reservations' => $counts['reservations'] ?? 0,
            'events' => $counts['events'] ?? 0,
            'payments' => $counts['payments'] ?? 0,
            'users' => $counts['users'] ?? 0,
            'system' => $counts['system'] ?? 0
        ],
        'summary' => [
            'reservations_percent' => $reservations_percent,
            'events_percent' => $events_percent,
            'payments_percent' => $payments_percent,
            'system_percent' => $system_percent,
            'users_percent' => $users_percent
        ],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'total' => $counts['total'] ?? 0
        ]
    ]);

} catch (Exception $e) {
    error_log("Admin notifications error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to load notifications: ' . $e->getMessage()
    ]);
}

// Helper function to format time
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