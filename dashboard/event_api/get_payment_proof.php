<?php
// MCC\dashboard\event_api\get_payment_proof.php

require_once '../../db_connect.php';

// Direct session handling
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Not logged in');
}

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Proposal ID required');
}

$proposal_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$download = isset($_GET['download']) && $_GET['download'] == '1';

try {
    // Check if proposal exists and belongs to user
    $stmt = $conn->prepare("
        SELECT payment_proof, proposal_id 
        FROM event_proposals 
        WHERE id = ? AND user_id = ? AND payment_proof IS NOT NULL
    ");
    $stmt->bind_param("ii", $proposal_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('HTTP/1.1 404 Not Found');
        exit('Payment proof not found or access denied');
    }
    
    $proposal = $result->fetch_assoc();
    $payment_proof = $proposal['payment_proof'];
    
    if (empty($payment_proof)) {
        header('HTTP/1.1 404 Not Found');
        exit('No payment proof data found');
    }
    
    // Try to detect file type from the binary data
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_buffer($finfo, $payment_proof);
    finfo_close($finfo);
    
    // Fallback mime types based on common file signatures
    if ($mime_type == 'application/octet-stream') {
        // Check for common file signatures
        $signatures = [
            'image/jpeg' => "\xFF\xD8\xFF",
            'image/png' => "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A",
            'image/gif' => "GIF",
            'application/pdf' => "%PDF"
        ];
        
        foreach ($signatures as $mime => $sig) {
            if (substr($payment_proof, 0, strlen($sig)) === $sig) {
                $mime_type = $mime;
                break;
            }
        }
    }
    
    // Set appropriate headers
    if ($download) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="payment_proof_' . $proposal['proposal_id'] . '"');
    } else {
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline');
        
        // Add filename for inline display when possible
        if (strpos($mime_type, 'image/') === 0 || $mime_type === 'application/pdf') {
            header('Content-Disposition: inline; filename="payment_proof_' . $proposal['proposal_id'] . '"');
        }
    }
    
    header('Content-Length: ' . strlen($payment_proof));
    header('Cache-Control: private, max-age=3600');
    
    // Output the binary data
    echo $payment_proof;
    
} catch (Exception $e) {
    error_log("Get payment proof error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Server error: ' . $e->getMessage());
}
?>