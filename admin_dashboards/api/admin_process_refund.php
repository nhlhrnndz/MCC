<?php
//MCC\admin_dashboards\api\admin_process_refund.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$refund_id = $input['refund_id'] ?? null;
$action = $input['action'] ?? null;
$admin_notes = $input['admin_notes'] ?? '';

if (!$refund_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Get refund details
    $stmt = $conn->prepare("
        SELECT * FROM refunds 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $refund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $refund = $result->fetch_assoc();
    
    if (!$refund) {
        throw new Exception('Refund not found');
    }
    
    if ($action === 'approve') {
        // Update refund status to approved
        $update_stmt = $conn->prepare("
            UPDATE refunds 
            SET status = 'approved',
                processed_at = NOW(),
                processed_by = ?,
                admin_notes = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("isi", $_SESSION['admin_id'], $admin_notes, $refund_id);
        $update_stmt->execute();
        
        // Update reservation/booking refund status
        if ($refund['type'] === 'room') {
            $reservation_stmt = $conn->prepare("
                UPDATE reservations 
                SET refund_status = 'approved'
                WHERE id = ?
            ");
            $reservation_stmt->bind_param("i", $refund['reservation_id']);
            $reservation_stmt->execute();
        } else {
            $booking_stmt = $conn->prepare("
                UPDATE facility_bookings 
                SET refund_status = 'approved'
                WHERE booking_id = ?
            ");
            $booking_stmt->bind_param("i", $refund['booking_id']);
            $booking_stmt->execute();
        }
        
        $message = 'Refund approved successfully';
        
    } elseif ($action === 'reject') {
        // Update refund status to rejected
        $update_stmt = $conn->prepare("
            UPDATE refunds 
            SET status = 'rejected',
                processed_at = NOW(),
                processed_by = ?,
                admin_notes = ?
            WHERE id = ?
        ");
        $update_stmt->bind_param("isi", $_SESSION['admin_id'], $admin_notes, $refund_id);
        $update_stmt->execute();
        
        // Update reservation/booking refund status
        if ($refund['type'] === 'room') {
            $reservation_stmt = $conn->prepare("
                UPDATE reservations 
                SET refund_status = 'rejected'
                WHERE id = ?
            ");
            $reservation_stmt->bind_param("i", $refund['reservation_id']);
            $reservation_stmt->execute();
        } else {
            $booking_stmt = $conn->prepare("
                UPDATE facility_bookings 
                SET refund_status = 'rejected'
                WHERE booking_id = ?
            ");
            $booking_stmt->bind_param("i", $refund['booking_id']);
            $booking_stmt->execute();
        }
        
        $message = 'Refund request rejected';
        
    } elseif ($action === 'process') {
        // Mark refund as processed/paid
        $update_stmt = $conn->prepare("
            UPDATE refunds 
            SET status = 'processed',
                processed_at = NOW(),
                processed_by = ?,
                admin_notes = ?
            WHERE id = ? AND status = 'approved'
        ");
        $update_stmt->bind_param("isi", $_SESSION['admin_id'], $admin_notes, $refund_id);
        $update_stmt->execute();
        
        // Update reservation/booking refund status
        if ($refund['type'] === 'room') {
            $reservation_stmt = $conn->prepare("
                UPDATE reservations 
                SET refund_status = 'processed'
                WHERE id = ?
            ");
            $reservation_stmt->bind_param("i", $refund['reservation_id']);
            $reservation_stmt->execute();
        } else {
            $booking_stmt = $conn->prepare("
                UPDATE facility_bookings 
                SET refund_status = 'processed'
                WHERE booking_id = ?
            ");
            $booking_stmt->bind_param("i", $refund['booking_id']);
            $booking_stmt->execute();
        }
        
        $message = 'Refund marked as processed/paid';
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>