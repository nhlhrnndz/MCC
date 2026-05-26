<?php
// MCC\dashboard\event_api\delete_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once '../../db_connect.php';

// Direct session handling
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

if (empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proposal ID required']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $proposal_id = $input['id'];
    
    // First verify the proposal exists and belongs to user
    $verify_query = "SELECT id, status, payment_proof FROM event_proposals WHERE id = ? AND user_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    
    if (!$verify_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $verify_stmt->bind_param("ii", $proposal_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Proposal not found or access denied']);
        exit();
    }
    
    $proposal = $verify_result->fetch_assoc();
    
    // Check if proposal can be deleted
    $deletable_statuses = ['pending', 'under_review', 'needs_changes'];
    if (!in_array($proposal['status'], $deletable_statuses)) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot delete proposal with current status: ' . $proposal['status']
        ]);
        exit();
    }
    
    // Note: For LONGBLOB storage, we don't need to delete files since payment_proof is stored in database
    
    // Delete the proposal
    $delete_query = "DELETE FROM event_proposals WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    if (!$delete_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $delete_stmt->bind_param("ii", $proposal_id, $user_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Proposal deleted successfully'
        ]);
    } else {
        throw new Exception("Delete failed: " . $delete_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Delete proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete proposal: ' . $e->getMessage()
    ]);
}
?>