<?php
// MCC\dashboard\event_api\upload_payment_proof.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once __DIR__ . '/../../db_connect.php';

// Direct session handling
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit();
}

if (!isset($_FILES['payment_proof'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

if (!isset($_POST['proposal_id'])) {
    echo json_encode(['success' => false, 'message' => 'Proposal ID required']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $proposal_id = $_POST['proposal_id'];
    $file = $_FILES['payment_proof'];

    // Check if proposal exists and belongs to user
    $check_query = "SELECT id, status, deposit_paid, balance_paid FROM event_proposals WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $proposal_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Proposal not found or access denied']);
        exit();
    }
    
    $proposal = $check_result->fetch_assoc();
    
    // Determine payment type and validate status
    $new_status = '';
    $payment_type = '';
    
    if (($proposal['status'] === 'approved' || $proposal['deposit_paid'] == 0) && $proposal['balance_paid'] == 0) {
        // This is a deposit payment
        $payment_type = 'deposit';
        $new_status = 'payment_pending_verification';
    } else if ($proposal['status'] === 'confirmed' && $proposal['deposit_paid'] == 1 && $proposal['balance_paid'] == 0) {
        // This is a balance payment
        $payment_type = 'balance';
        $new_status = 'balance_pending_verification';
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot upload payment proof in current status']);
        exit();
    }

    // Validate file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, PDF']);
        exit();
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
        exit();
    }

    // Check file upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        $error_message = $upload_errors[$file['error']] ?? 'Unknown upload error';
        echo json_encode(['success' => false, 'message' => 'File upload error: ' . $error_message]);
        exit();
    }

    // Verify the file is actually an uploaded file
    if (!is_uploaded_file($file['tmp_name'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid file upload']);
        exit();
    }

    // Read file as binary data for LONGBLOB
    $file_content = file_get_contents($file['tmp_name']);
    if ($file_content === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file']);
        exit();
    }

    $file_size = strlen($file_content);
    if ($file_size === 0) {
        echo json_encode(['success' => false, 'message' => 'Uploaded file is empty']);
        exit();
    }

    // Update database with LONGBLOB data and appropriate status
    $update_query = "UPDATE event_proposals 
                    SET payment_proof = ?, 
                        status = ?,
                        updated_at = NOW() 
                    WHERE id = ? AND user_id = ?";
    
    $update_stmt = $conn->prepare($update_query);
    if (!$update_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Use send_long_data for LONGBLOB
    $null = NULL;
    $update_stmt->bind_param("bsii", $null, $new_status, $proposal_id, $user_id);
    $update_stmt->send_long_data(0, $file_content);

    if ($update_stmt->execute()) {
        // Verify the update was successful
        $verify_query = "SELECT status, LENGTH(payment_proof) as proof_size FROM event_proposals WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("i", $proposal_id);
        $verify_stmt->execute();
        $updated_proposal = $verify_stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment proof uploaded successfully! Awaiting verification.',
            'new_status' => $updated_proposal['status'],
            'proof_size' => $updated_proposal['proof_size'],
            'payment_type' => $payment_type
        ]);
    } else {
        throw new Exception("Database update failed: " . $update_stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Payment proof upload error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>