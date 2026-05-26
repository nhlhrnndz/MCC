<?php
// MCC\dashboard\event_api\confirm_booking.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once '../../db_connect.php';
require_once 'helpers/SessionManager.php';

SessionManager::startSession();

if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

if (!isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proposal ID required']);
    exit();
}

try {
    $user_id = SessionManager::getUserId();
    $proposal_id = $input['id'];
    
    // First get the proposal to check ownership and status
    $check_query = "SELECT id, user_id, status, deposit_amount FROM event_proposals WHERE id = ?";
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
    
    // Check ownership
    if ($proposal['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Check if proposal is in correct status for deposit payment
    if ($proposal['status'] !== 'approved') {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Proposal must be approved before confirming booking'
        ]);
        exit();
    }
    
    // Update to mark deposit as paid and change status
    $update_query = "UPDATE event_proposals 
                    SET deposit_paid = 1,
                        status = 'payment_pending_verification',
                        updated_at = NOW()
                    WHERE id = ? AND user_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $proposal_id, $user_id);
    
    if ($update_stmt->execute()) {
        // Get updated proposal data
        $updated_query = "SELECT deposit_amount FROM event_proposals WHERE id = ?";
        $updated_stmt = $conn->prepare($updated_query);
        $updated_stmt->bind_param("i", $proposal_id);
        $updated_stmt->execute();
        $updated_result = $updated_stmt->get_result();
        $updated_proposal = $updated_result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Deposit marked as paid. Please upload payment proof.',
            'deposit_amount' => $updated_proposal['deposit_amount'],
            'new_status' => 'payment_pending_verification'
        ]);
    } else {
        throw new Exception("Update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Confirm booking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>