<?php
// MCC\dashboard\event_api\get_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
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

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Proposal ID required']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $proposal_id = $_GET['id'];
    
    $query = "SELECT * FROM event_proposals WHERE id = ? AND user_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $proposal_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Proposal not found']);
        exit();
    }
    
    $row = $result->fetch_assoc();
    
    // Handle LONGBLOB payment proof
    $payment_proof = $row['payment_proof'];
    $has_payment_proof = false;
    $payment_proof_size = 0;
    
    if (!empty($payment_proof)) {
        $payment_proof_size = strlen($payment_proof);
        $has_payment_proof = ($payment_proof_size > 100);
    }
    
    $proposal = [
        'id' => $row['id'],
        'proposal_id' => $row['proposal_id'],
        'event_title' => $row['event_title'],
        'event_type' => $row['event_type'],
        'arrival_date' => $row['arrival_date'],
        'arrival_time' => $row['arrival_time'],
        'venue_preference' => $row['venue_preference'],
        'expected_guests' => $row['expected_guests'],
        'theme' => $row['theme'],
        'description' => $row['description'],
        'catering_request' => $row['catering_request'],
        'decorations' => $row['decorations'] ? json_decode($row['decorations'], true) : [],
        'addon_aircon' => (bool)$row['addon_aircon'],
        'addon_corkage' => (bool)$row['addon_corkage'],
        'estimated_budget' => $row['estimated_budget'],
        'payment_method' => $row['payment_method'],
        'venue_cost' => $row['venue_cost'],
        'catering_cost' => $row['catering_cost'],
        'additional_services_cost' => $row['additional_services_cost'],
        'total_estimated_cost' => $row['total_estimated_cost'],
        'final_quote_amount' => $row['final_quote_amount'],
        'deposit_amount' => $row['deposit_amount'],
        'deposit_paid' => (bool)$row['deposit_paid'],
        'balance_amount' => $row['balance_amount'] ?? null,
        'balance_paid' => (bool)($row['balance_paid'] ?? false),
        'balance_due_date' => $row['balance_due_date'],
        'status' => $row['status'],
        'manager_feedback' => $row['manager_feedback'],
        'payment_proof' => $has_payment_proof ? 'exists' : null,
        'has_payment_proof' => $has_payment_proof,
        'payment_proof_size' => $payment_proof_size,
        'submitted' => $row['submitted'],
        'updated_at' => $row['updated_at']
    ];
    
    echo json_encode([
        'success' => true,
        'proposal' => $proposal
    ]);
    
} catch (Exception $e) {
    error_log("Get proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>