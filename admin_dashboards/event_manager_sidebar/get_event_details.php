<?php
include 'C:\xampp\htdocs\MCC\db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

$eventId = intval($_GET['id']);

try {
    // Get event details - use data directly from event_proposals
    $query = "SELECT 
                p.*,
                p.full_name as client_name,
                a.fullname as manager_name
              FROM event_proposals p
              LEFT JOIN admin_users a ON p.assigned_manager_id = a.id
              WHERE p.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        echo json_encode(['success' => true, 'event' => $event]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching event details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>