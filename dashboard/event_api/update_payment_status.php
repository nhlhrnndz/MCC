<?php
// MCC\dashboard\event_api\update_payment_status.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once '../../db_connect.php';

// Direct session handling
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Validate required fields
if (empty($input['proposal_id']) || empty($input['payment_type']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: proposal_id, payment_type, status']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $proposal_id = $input['proposal_id'];
    $payment_type = $input['payment_type'];
    $status = $input['status'];
    
    // Validate payment type
    if (!in_array($payment_type, ['deposit', 'balance'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment type. Must be "deposit" or "balance"']);
        exit();
    }
    
    // Validate status
    if (!in_array($status, ['paid', 'pending'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status. Must be "paid" or "pending"']);
        exit();
    }
    
    // First get current proposal data
    $check_query = "SELECT id, user_id, status, deposit_paid, balance_paid FROM event_proposals WHERE id = ?";
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
    
    // Determine field and new status based on payment type
    $field = $payment_type . '_paid';
    $value = $status === 'paid' ? 1 : 0;
    
    // Set appropriate status based on payment type and action
    if ($payment_type === 'deposit') {
        if ($status === 'paid') {
            $new_status = 'payment_pending_verification';
        } else {
            $new_status = 'approved'; // Go back to approved if deposit is set to pending
        }
    } else {
        // Balance payment
        if ($status === 'paid') {
            $new_status = 'balance_pending_verification';
        } else {
            $new_status = 'confirmed'; // Go back to confirmed if balance is set to pending
        }
    }
    
    // Update the payment status and overall status
    $update_query = "UPDATE event_proposals 
                    SET {$field} = ?, 
                        status = ?, 
                        updated_at = NOW() 
                    WHERE id = ? AND user_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("isii", $value, $new_status, $proposal_id, $user_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => ucfirst($payment_type) . ' payment status updated successfully',
            'new_status' => $new_status,
            'payment_type' => $payment_type,
            'payment_status' => $status
        ]);
    } else {
        throw new Exception("Update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Update payment status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>