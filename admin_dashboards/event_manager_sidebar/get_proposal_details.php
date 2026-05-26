<?php
//
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

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Proposal ID is required']);
    exit();
}

$proposalId = intval($_GET['id']);
$managerId = $_SESSION['admin_id'];

try {
    // Get proposal details - use data directly from event_proposals (no JOIN needed)
    $query = "SELECT 
                p.*,
                p.full_name as user_name,
                p.email as user_email,
                p.contact_number as user_contact
              FROM event_proposals p 
              WHERE p.id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $proposalId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Proposal not found with ID: ' . $proposalId]);
        exit();
    }
    
    $proposal = $result->fetch_assoc();
    
    // Handle LONGBLOB payment proof properly
    $payment_proof = $proposal['payment_proof'];
    $has_payment_proof = false;
    $payment_proof_size = 0;
    
    // Check if payment_proof contains actual binary data
    if (!empty($payment_proof)) {
        $proof_string = $payment_proof;
        $payment_proof_size = strlen($proof_string);
        $has_payment_proof = ($payment_proof_size > 100);
        $proposal['payment_proof'] = $has_payment_proof ? 'exists' : null;
    } else {
        $proposal['payment_proof'] = null;
    }
    
    // Add payment proof flags to the proposal
    $proposal['has_payment_proof'] = $has_payment_proof;
    $proposal['payment_proof_size'] = $payment_proof_size;
    
    // Convert JSON decorations to readable format
    if ($proposal['decorations'] && $proposal['decorations'] !== 'null' && $proposal['decorations'] !== '[]') {
        try {
            $decorations = json_decode($proposal['decorations'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decorations)) {
                $proposal['decorations'] = $decorations;
            } else {
                $proposal['decorations'] = [];
            }
        } catch (Exception $e) {
            $proposal['decorations'] = [];
        }
    } else {
        $proposal['decorations'] = [];
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'proposal' => $proposal
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_proposal_details.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>