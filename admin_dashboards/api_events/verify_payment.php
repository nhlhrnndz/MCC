<?php
// MCC/admin_dashboards/api_events/verify_payment.php

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

// FIX: Check admin_role instead of role
$user_role = $_SESSION['admin_role'] ?? 'user';
$allowed_roles = ['manager', 'admin', 'super_admin'];

if (!in_array($user_role, $allowed_roles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Access denied. Manager privileges required.',
        'debug_role' => $user_role
    ]);
    exit();
}


$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

// Validate required fields
if (empty($input['proposal_id']) || empty($input['action']) || empty($input['payment_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: proposal_id, action, payment_type']);
    exit();
}

try {
    $proposal_id = $input['proposal_id'];
    $action = $input['action']; // 'approved' or 'rejected'
    $payment_type = $input['payment_type']; // 'deposit' or 'balance'
    
    // Validate inputs
    if (!in_array($action, ['approved', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action. Must be "approved" or "rejected"']);
        exit();
    }
    
    if (!in_array($payment_type, ['deposit', 'balance'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid payment type. Must be "deposit" or "balance"']);
        exit();
    }
    
    // First get current proposal data
    $check_query = "SELECT id, status, deposit_paid, balance_paid FROM event_proposals WHERE id = ?";
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
    $new_status = '';
    $feedback = '';
    
    // Process verification based on payment type and action
    if ($payment_type === 'deposit') {
        if ($action === 'approved') {
            $new_status = 'confirmed';
            $feedback = 'Deposit payment verified and approved. You can now pay the remaining balance.';
            
            // Update deposit_paid status
            $payment_update = "UPDATE event_proposals SET deposit_paid = 1 WHERE id = ?";
            $payment_stmt = $conn->prepare($payment_update);
            $payment_stmt->bind_param("i", $proposal_id);
            $payment_stmt->execute();
        } else {
            $new_status = 'approved'; // Go back to approved status
            $feedback = 'Deposit payment proof rejected. Please submit a valid payment proof.';
            
            // Reset deposit_paid status
            $payment_update = "UPDATE event_proposals SET deposit_paid = 0 WHERE id = ?";
            $payment_stmt = $conn->prepare($payment_update);
            $payment_stmt->bind_param("i", $proposal_id);
            $payment_stmt->execute();
        }
    } else {
        // Balance payment verification
        if ($action === 'approved') {
            $new_status = 'fully_paid';
            $feedback = 'Balance payment verified and approved. Event is fully paid!';
            
            // Update balance_paid status
            $payment_update = "UPDATE event_proposals SET balance_paid = 1 WHERE id = ?";
            $payment_stmt = $conn->prepare($payment_update);
            $payment_stmt->bind_param("i", $proposal_id);
            $payment_stmt->execute();
        } else {
            $new_status = 'confirmed'; // Go back to confirmed status
            $feedback = 'Balance payment proof rejected. Please submit a valid payment proof.';
            
            // Reset balance_paid status
            $payment_update = "UPDATE event_proposals SET balance_paid = 0 WHERE id = ?";
            $payment_stmt = $conn->prepare($payment_update);
            $payment_stmt->bind_param("i", $proposal_id);
            $payment_stmt->execute();
        }
    }
    
    // Update the proposal with new status and feedback
    $update_query = "UPDATE event_proposals 
                    SET status = ?, 
                        manager_feedback = ?, 
                        updated_at = NOW() 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param("ssi", $new_status, $feedback, $proposal_id);
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment verification completed',
            'new_status' => $new_status,
            'payment_type' => $payment_type,
            'action' => $action,
            'feedback' => $feedback
        ]);
    } else {
        throw new Exception("Update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Verify payment error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>