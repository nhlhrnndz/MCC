<?php
// MCC\dashboard\event_api\reject_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../../db_connect.php';

session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in as admin']);
    exit();
}

// ✅ FIXED: Manager role check - using admin_role
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
if (empty($input['proposal_id']) || empty($input['feedback'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: proposal_id, feedback']);
    exit();
}

try {
    $proposal_id = $input['proposal_id'];
    $feedback = $input['feedback'];
    
    // First check if proposal exists and is in correct status
    $check_query = "SELECT id, status FROM event_proposals WHERE id = ?";
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
    
    // Check if proposal can be rejected
    $rejectable_statuses = ['pending', 'under_review', 'needs_changes'];
    if (!in_array($proposal['status'], $rejectable_statuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot reject proposal in current status: ' . $proposal['status']
        ]);
        exit();
    }
    
    // Update proposal status to rejected
    $update_query = "UPDATE event_proposals 
                    SET status = 'rejected', 
                        manager_feedback = ?, 
                        updated_at = NOW() 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("si", $feedback, $proposal_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Proposal rejected successfully',
            'new_status' => 'rejected'
        ]);
    } else {
        throw new Exception("Update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Reject proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>