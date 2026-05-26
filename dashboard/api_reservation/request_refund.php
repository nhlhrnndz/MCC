<?php
// MCC/dashboard/api_reservation/request_refund.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

require_once '../../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;
$type = $input['type'] ?? 'room';
$reason = trim($input['refund_reason'] ?? '');

// Debug logging
error_log("=== REFUND REQUEST ===");
error_log("User ID: " . $_SESSION['user_id']);
error_log("ID: $id, Type: $type, Reason: " . substr($reason, 0, 50));

if (!$id || !$reason) {
    error_log("Missing data: ID=$id, Reason length=" . strlen($reason));
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $conn->autocommit(false);
    $user_id = $_SESSION['user_id'];

    // Check eligibility
    if ($type === 'room') {
        $stmt = $conn->prepare("SELECT id, amount_paid, refund_status FROM reservations WHERE id = ? AND user_id = ? AND status = 'cancelled'");
        $stmt->bind_param("ii", $id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT booking_id, total_amount AS amount_paid, refund_status FROM facility_bookings WHERE booking_id = ? AND user_id = ? AND status = 'cancelled'");
        $stmt->bind_param("ii", $id, $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    error_log("Eligibility check result: " . print_r($result, true));
    
    if (!$result) {
        throw new Exception("Reservation not found or not cancelled");
    }
    
    if ($result['amount_paid'] <= 0) {
        throw new Exception("No payment made");
    }
    
    // Check if refund already requested
    if ($result['refund_status'] !== 'not_requested') {
        throw new Exception("Refund already requested or processed");
    }

    $refund_amount = $result['amount_paid'] * 0.8;  // 80% refund
    $refund_ref = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    error_log("Refund amount: $refund_amount, Ref: $refund_ref");

    // Insert into refunds table
    $insert = $conn->prepare("INSERT INTO refunds 
        (reservation_id, booking_id, type, refund_ref, refund_amount, reason, requested_by, status, requested_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    $rid = $type === 'room' ? $id : null;
    $bid = $type === 'facility' ? $id : null;
    $insert->bind_param("iissdsi", $rid, $bid, $type, $refund_ref, $refund_amount, $reason, $user_id);
    
    if (!$insert->execute()) {
        throw new Exception("Failed to insert refund record: " . $conn->error);
    }
    
    error_log("Refund record inserted: " . $conn->insert_id);

    // Update original booking
    if ($type === 'room') {
        $update = $conn->prepare("UPDATE reservations SET 
            refund_status = 'requested', 
            refund_amount = ?, 
            refund_requested = 1, 
            refund_requested_at = NOW(), 
            refund_request_reason = ? 
            WHERE id = ?");
        $update->bind_param("dsi", $refund_amount, $reason, $id);
    } else {
        $update = $conn->prepare("UPDATE facility_bookings SET 
            refund_status = 'requested', 
            refund_amount = ?, 
            refund_requested = 1, 
            refund_requested_at = NOW(), 
            refund_request_reason = ? 
            WHERE booking_id = ?");
        $update->bind_param("dsi", $refund_amount, $reason, $id);
    }
    
    if (!$update->execute()) {
        throw new Exception("Failed to update booking: " . $conn->error);
    }
    
    error_log("Booking updated successfully");

    $conn->commit();
    error_log("Transaction committed successfully");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Refund request submitted successfully!',
        'refund_ref' => $refund_ref,
        'amount' => $refund_amount
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("ERROR: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>