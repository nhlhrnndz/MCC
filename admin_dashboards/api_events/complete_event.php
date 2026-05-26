<?php
// MCC\dashboard\event_api\complete_event.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once '../../db_connect.php';

// Direct session handling
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in as admin']);
    exit();
}

// ✅ FIX: Check admin_role instead of role
$user_role = $_SESSION['admin_role'] ?? 'user';
if ($user_role !== 'manager' && $user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Manager privileges required.']);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Validate required fields
if (empty($input['proposal_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: proposal_id']);
    exit();
}

try {
    $proposal_id = $input['proposal_id'];
    $completion_date = $input['completion_date'] ?? date('Y-m-d');
    $completion_notes = $input['completion_notes'] ?? 'Event completed successfully. Thank you for choosing our services!';
    
    // First check if proposal exists and is in correct status
    $check_query = "SELECT id, status, arrival_date FROM event_proposals WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $proposal_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Proposal not found']);
        exit();
    }
    
    $proposal = $check_result->fetch_assoc();
    
    // Check if proposal can be marked as completed
    $completable_statuses = ['fully_paid', 'confirmed'];
    if (!in_array($proposal['status'], $completable_statuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot complete event in current status: ' . $proposal['status'] . '. Event must be fully paid or confirmed.'
        ]);
        exit();
    }
    
    // Update proposal status to completed
    $update_query = "UPDATE event_proposals 
                    SET status = 'completed', 
                        manager_feedback = ?, 
                        completion_date = ?,
                        completion_notes = ?,
                        updated_at = NOW() 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("sssi", $completion_notes, $completion_date, $completion_notes, $proposal_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Event marked as completed successfully',
            'new_status' => 'completed',
            'completion_date' => $completion_date
        ]);
    } else {
        throw new Exception("Update failed: " . $update_stmt->execute());
    }
    
} catch (Exception $e) {
    error_log("Complete event error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>