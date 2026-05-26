<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$managerId = $_SESSION['admin_id'];
$requestedManagerId = $_GET['manager_id'] ?? $managerId;

// Security check - ensure managers can only see their own events
if ($_SESSION['role'] === 'manager' && $requestedManagerId != $managerId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $query = "SELECT p.*, u.full_name as client_name 
              FROM event_proposals p 
              JOIN users u ON p.user_id = u.user_id 
              WHERE (p.event_date >= CURDATE() OR p.arrival_date >= CURDATE())
              AND p.status = 'confirmed'
              AND p.assigned_manager_id = ?
              ORDER BY COALESCE(p.event_date, p.arrival_date) ASC LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $requestedManagerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events,
        'count' => count($events),
        'last_updated' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>