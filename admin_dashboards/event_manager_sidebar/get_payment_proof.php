<?php
// manager/get_payment_proof.php
session_start();
include 'C:\xampp\htdocs\MCC\db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'manager') {
    header('HTTP/1.1 401 Unauthorized');
    exit('Access denied. Please log in as manager.');
}

if (!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Proposal ID required');
}

$proposal_id = $_GET['id'];
$download = isset($_GET['download']) && $_GET['download'] == '1';

try {
    // Check if proposal exists and get payment proof
    $stmt = $conn->prepare("
        SELECT payment_proof, proposal_id 
        FROM event_proposals 
        WHERE id = ? AND payment_proof IS NOT NULL
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('HTTP/1.1 404 Not Found');
        exit('Payment proof not found');
    }
    
    $proposal = $result->fetch_assoc();
    $payment_proof = $proposal['payment_proof'];
    
    if (empty($payment_proof)) {
        header('HTTP/1.1 404 Not Found');
        exit('No payment proof data found');
    }
    
    // Try to detect file type
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $payment_proof);
        finfo_close($finfo);
    } else {
        // Fallback mime type detection for Windows
        $mime_type = 'application/octet-stream';
        
        // Simple detection based on file signatures
        if (substr($payment_proof, 0, 3) === "\xFF\xD8\xFF") {
            $mime_type = 'image/jpeg';
        } elseif (substr($payment_proof, 0, 8) === "\x89PNG\r\n\x1a\n") {
            $mime_type = 'image/png';
        } elseif (substr($payment_proof, 0, 4) === "GIF8") {
            $mime_type = 'image/gif';
        } elseif (substr($payment_proof, 0, 4) === "%PDF") {
            $mime_type = 'application/pdf';
        }
    }
    
    // Set appropriate headers
    if ($download) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="payment_proof_' . $proposal['proposal_id'] . '"');
    } else {
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: inline');
    }
    
    header('Content-Length: ' . strlen($payment_proof));
    header('Cache-Control: private, max-age=3600');
    
    echo $payment_proof;
    
} catch (Exception $e) {
    error_log("Get payment proof error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    exit('Server error: ' . $e->getMessage());
}
?>