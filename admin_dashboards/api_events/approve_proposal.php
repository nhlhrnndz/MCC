<?php
// MCC\dashboard\event_api\approve_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once '../../db_connect.php';

// Direct session handling
session_start();

// Check if user is logged in - using admin session for manager
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Manager role check - using admin_role
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
if (empty($input['proposal_id']) || empty($input['final_quote_amount'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: proposal_id, final_quote_amount']);
    exit();
}

try {
    $proposal_id = $input['proposal_id'];
    $final_quote_amount = (float)$input['final_quote_amount'];
    $deposit_due_date = $input['deposit_due_date'] ?? null;
    $feedback = $input['feedback'] ?? '';
    
    // Validate final quote amount
    if ($final_quote_amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Final quote amount must be greater than 0']);
        exit();
    }
    
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
    
    // Check if proposal can be approved (should be pending or under_review)
    $approvable_statuses = ['pending', 'under_review', 'needs_changes'];
    if (!in_array($proposal['status'], $approvable_statuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Proposal cannot be approved in current status: ' . $proposal['status']
        ]);
        exit();
    }
    
    // Calculate amounts (50% deposit, 50% balance)
    $deposit_amount = $final_quote_amount * 0.5;
    $balance_amount = $final_quote_amount - $deposit_amount;
    
    // Update proposal with approval details
    $update_query = "UPDATE event_proposals 
                    SET status = 'approved', 
                        final_quote_amount = ?, 
                        deposit_amount = ?, 
                        balance_amount = ?,
                        deposit_due_date = ?, 
                        manager_feedback = ?, 
                        updated_at = NOW() 
                    WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param(
        "dddssi", 
        $final_quote_amount, 
        $deposit_amount, 
        $balance_amount,
        $deposit_due_date, 
        $feedback, 
        $proposal_id
    );
    
    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Proposal approved successfully',
            'final_quote_amount' => $final_quote_amount,
            'deposit_amount' => $deposit_amount,
            'balance_amount' => $balance_amount,
            'new_status' => 'approved'
        ]);
    } else {
        throw new Exception("Update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Approve proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>