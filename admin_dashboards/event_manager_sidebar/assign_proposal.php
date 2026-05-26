<?php
//admin_dashboards\event_manager_sidebar\assign_proposal.php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection from root
include 'C:\xampp\htdocs\MCC\db_connect.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'manager') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied. Please log in as manager.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $proposalId = $input['proposal_id'] ?? null;
    $action = $input['action'] ?? 'assign';
    $managerId = $_SESSION['admin_id'];

    if (!$proposalId) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Proposal ID is required']);
        exit();
    }

    try {
        // Check if proposal exists
        $checkQuery = "SELECT status, assigned_manager_id FROM event_proposals WHERE id = ?";
        $stmt = $conn->prepare($checkQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $proposalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $proposal = $result->fetch_assoc();

        if (!$proposal) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Proposal not found with ID: ' . $proposalId]);
            exit();
        }

        // Handle assignment
        if ($action === 'assign') {
            if ($proposal['assigned_manager_id']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Proposal is already assigned to another manager']);
                exit();
            }

            $updateQuery = "UPDATE event_proposals SET assigned_manager_id = ?, status = 'under_review', updated_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $managerId, $proposalId);
            
        } elseif ($action === 'takeover') {
            if (!$proposal['assigned_manager_id']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Proposal is not assigned to anyone']);
                exit();
            }

            if ($proposal['assigned_manager_id'] == $managerId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'You already own this proposal']);
                exit();
            }

            $updateQuery = "UPDATE event_proposals SET assigned_manager_id = ?, status = 'under_review', updated_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $managerId, $proposalId);
        }

        if ($updateStmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Proposal assigned successfully']);
        } else {
            throw new Exception("Update failed: " . $conn->error);
        }
        
    } catch (Exception $e) {
        error_log("Error in assign_proposal.php: " . $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
}
?>