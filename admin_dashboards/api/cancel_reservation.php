<?php
// MCC\admin_dashboards\api\cancel_reservation.php - FIXED VERSION
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';

$post = json_decode(file_get_contents('php://input'), true);
$id = $post['id'] ?? null;
$type = $post['type'] ?? null;
$refund_amount = $post['refund_amount'] ?? 0;
$cancellation_fee = $post['cancellation_fee'] ?? 0;
$cancellation_reason = $post['cancellation_reason'] ?? 'Cancelled by admin';
$admin_notes = $post['admin_notes'] ?? '';

if (!$id || !$type) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    $conn->begin_transaction();

    if ($type === 'facility') {
        // FACILITY BOOKING CANCELLATION WITH REFUND
        $stmt = $conn->prepare("
            SELECT fb.*, COALESCE(gp.amount, 0) as gcash_amount_paid
            FROM facility_bookings fb 
            LEFT JOIN gcash_payments gp ON fb.payment_reference = gp.reservation_ref
            WHERE fb.booking_id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        
        if (!$reservation) {
            throw new Exception('Facility booking not found');
        }
        
        // Update facility booking with refund details - FIXED for your schema
        $update_stmt = $conn->prepare("
            UPDATE facility_bookings 
            SET status = 'cancelled',
                cancellation_reason = ?,
                admin_notes = ?,
                refund_amount = ?,
                cancellation_fee = ?,
                cancelled_at = NOW(),
                refund_status = ?
            WHERE booking_id = ?
        ");
        
        $refund_status = ($refund_amount > 0) ? 'approved' : 'not_requested';
        $update_stmt->bind_param("ssddssi", $cancellation_reason, $admin_notes, $refund_amount, $cancellation_fee, $refund_status, $id);
        
    } else {
        // ROOM RESERVATION CANCELLATION WITH REFUND
        $stmt = $conn->prepare("
            SELECT r.*, COALESCE(gp.amount, 0) as gcash_amount_paid
            FROM reservations r 
            LEFT JOIN gcash_payments gp ON r.reservation_ref = gp.reservation_ref
            WHERE r.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservation = $result->fetch_assoc();
        
        if (!$reservation) {
            throw new Exception('Reservation not found');
        }
        
        // Update reservation with refund details - FIXED for your schema
        $update_stmt = $conn->prepare("
            UPDATE reservations 
            SET status = 'cancelled',
                payment_status = ?,
                cancellation_reason = ?,
                admin_notes = ?,
                refund_amount = ?,
                cancellation_fee = ?,
                cancelled_at = NOW(),
                refund_status = ?
            WHERE id = ?
        ");
        
        $payment_status = ($refund_amount > 0) ? 'refund_pending' : 'cancelled';
        $refund_status = ($refund_amount > 0) ? 'approved' : 'not_requested';
        $update_stmt->bind_param("sssddssi", $payment_status, $cancellation_reason, $admin_notes, $refund_amount, $cancellation_fee, $refund_status, $id);
    }

    $update_stmt->execute();

    if ($update_stmt->affected_rows === 0) {
        throw new Exception('Booking not found or cannot be cancelled');
    }

    // Create refund record if refund amount > 0
    if ($refund_amount > 0) {
        $refund_ref = 'REF' . date('YmdHis') . strtoupper(substr(uniqid(), -6));
        $reservation_ref = ($type === 'facility') ? $reservation['payment_reference'] : $reservation['reservation_ref'];
        $original_amount = $reservation['gcash_amount_paid'] > 0 ? $reservation['gcash_amount_paid'] : $reservation['total_amount'];
        
        $refund_stmt = $conn->prepare("
            INSERT INTO refunds 
            (refund_ref, reservation_id, booking_id, type, refund_amount, cancellation_fee, 
             reason, admin_notes, status, requested_by, processed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?)
        ");
        
        $requested_by = $reservation['user_id'];
        $booking_id = ($type === 'facility') ? $id : null;
        $reservation_id = ($type === 'room') ? $id : null;
        
        $refund_stmt->bind_param(
            "siisddssii",
            $refund_ref,
            $reservation_id,
            $booking_id,
            $type,
            $refund_amount,
            $cancellation_fee,
            $cancellation_reason,
            $admin_notes,
            $requested_by,
            $_SESSION['admin_id']
        );
        
        $refund_stmt->execute();
        
        // Log GCash refund requirement
        if (($reservation['payment_type'] === 'gcash_full' || $reservation['payment_type'] === 'gcash_deposit') && $refund_amount > 0) {
            error_log("ADMIN: GCash refund required - Amount: {$refund_amount}, Ref: {$reservation_ref}, Admin: {$_SESSION['admin_id']}");
        }
    }

    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $refund_amount > 0 ? 
            'Booking cancelled and refund approved!' : 
            'Booking cancelled successfully!',
        'refund_amount' => $refund_amount,
        'cancellation_fee' => $cancellation_fee
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>