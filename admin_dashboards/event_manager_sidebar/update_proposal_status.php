<?php
// MCC\admin_dashboards\event_manager_sidebar\update_proposal_status.php

header("Content-Type: application/json; charset=UTF-8");
session_start();

// ✅ CORRECT PATH: From admin_dashboards/event_manager_sidebar to MCC root
require_once __DIR__ . '/../../db_connect.php';

// Check if user is logged in as admin/manager
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in as admin']);
    exit();
}

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
if (empty($input['proposal_id']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: proposal_id, status']);
    exit();
}

// Function to log activity
function logActivity($conn, $user_id, $user_role, $action, $description, $proposal_id = null) {
    $log_query = "INSERT INTO activity_log (user_id, user_role, action, description, proposal_id, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
    $log_stmt = $conn->prepare($log_query);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    if ($log_stmt) {
        $log_stmt->bind_param("isssiss", $user_id, $user_role, $action, $description, $proposal_id, $ip_address, $user_agent);
        return $log_stmt->execute();
    }
    return false;
}

try {
    $proposal_id = $input['proposal_id'];
    $status = $input['status'];
    $final_quote_amount = isset($input['final_quote_amount']) ? (float)$input['final_quote_amount'] : null;
    $deposit_due_date = $input['deposit_due_date'] ?? null;
    $feedback = $input['feedback'] ?? '';
    $completion_date = $input['completion_date'] ?? null;
    $completion_notes = $input['completion_notes'] ?? '';
    $user_id = $_SESSION['admin_id'];

    // First check if proposal exists and get current data
    $check_query = "SELECT id, status, proposal_id, event_title, assigned_manager_id FROM event_proposals WHERE id = ?";
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
    $old_status = $proposal['status'];
    
    // Check if manager is assigned to this proposal (for certain actions)
    $allowed_statuses_without_assignment = ['pending', 'under_review', 'needs_changes'];
    if (!in_array($status, $allowed_statuses_without_assignment) && $proposal['assigned_manager_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this proposal']);
        exit();
    }
    
    // Build update query based on status and provided data
    $update_fields = ["status = ?", "updated_at = NOW()"];
    $params = [$status];
    $types = "s";
    
    // Add fields based on status and input
    if ($status === 'approved' && $final_quote_amount !== null) {
        if ($final_quote_amount <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Final quote amount must be greater than 0']);
            exit();
        }
        
        $deposit_amount = $final_quote_amount * 0.5;
        $balance_amount = $final_quote_amount - $deposit_amount;
        
        $update_fields[] = "final_quote_amount = ?";
        $update_fields[] = "deposit_amount = ?";
        $update_fields[] = "balance_amount = ?";
        $params[] = $final_quote_amount;
        $params[] = $deposit_amount;
        $params[] = $balance_amount;
        $types .= "ddd";
    }
    
    if ($deposit_due_date) {
        $update_fields[] = "deposit_due_date = ?";
        $params[] = $deposit_due_date;
        $types .= "s";
    }
    
    if ($feedback) {
        $update_fields[] = "manager_feedback = ?";
        $params[] = $feedback;
        $types .= "s";
    }
    
    if ($status === 'completed') {
        $update_fields[] = "completion_date = ?";
        $update_fields[] = "completion_notes = ?";
        $params[] = $completion_date ?: date('Y-m-d');
        $params[] = $completion_notes ?: 'Event completed successfully';
        $types .= "ss";
    }
    
    // Auto-assign manager if not assigned and status requires it
    if (($status === 'under_review' || $status === 'approved') && !$proposal['assigned_manager_id']) {
        $update_fields[] = "assigned_manager_id = ?";
        $params[] = $user_id;
        $types .= "i";
    }
    
    // Build and execute update query
    $update_query = "UPDATE event_proposals SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $params[] = $proposal_id;
    $types .= "i";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update_stmt->bind_param($types, ...$params);
    
    if ($update_stmt->execute()) {
        // Log the activity
        $action_description = "Status changed from {$old_status} to {$status}";
        
        if ($status === 'approved' && $final_quote_amount) {
            $action_description .= " with final quote: ₱" . number_format($final_quote_amount, 2);
        }
        
        if ($feedback) {
            $action_description .= " - Note: " . substr($feedback, 0, 100) . (strlen($feedback) > 100 ? '...' : '');
        }
        
        logActivity($conn, $user_id, $user_role, "proposal_{$status}", $action_description, $proposal_id);
        
        $response = [
            'success' => true,
            'message' => 'Proposal status updated successfully',
            'new_status' => $status,
            'logged' => true
        ];
        
        if ($status === 'approved' && $final_quote_amount !== null) {
            $response['final_quote_amount'] = $final_quote_amount;
            $response['deposit_amount'] = $final_quote_amount * 0.5;
            $response['balance_amount'] = $final_quote_amount * 0.5;
        }
        
        echo json_encode($response);
    } else {
        throw new Exception("Update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Update proposal status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>