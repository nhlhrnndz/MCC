<?php
// event_api/cancel_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// db_connect.php is in root folder, so go up two levels from event_api folder
require_once __DIR__ . '/../../db_connect.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['proposal_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proposal ID required']);
    exit();
}

if (empty($input['cancellation_reason'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cancellation reason required']);
    exit();
}

try {
    $proposal_id = $input['proposal_id'];
    $reason = $input['cancellation_reason'];
    $user_id = $_SESSION['user_id'];
    
    // First check if proposal exists and belongs to user
    $check_query = "SELECT id, status FROM event_proposals WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $proposal_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proposal not found or access denied']);
        exit();
    }
    
    $proposal = $check_result->fetch_assoc();
    
    // Check if proposal can be cancelled (only certain statuses)
    $allowed_statuses = ['pending', 'under_review', 'approved', 'payment_pending_verification', 'needs_changes'];
    if (!in_array($proposal['status'], $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Cannot cancel proposal in current status: ' . $proposal['status']]);
        exit();
    }
    
    // Update proposal status to cancelled
    $query = "UPDATE event_proposals SET status = 'cancelled', cancellation_reason = ?, updated_at = NOW() WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $reason, $proposal_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Proposal cancelled successfully']);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Cancel proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>