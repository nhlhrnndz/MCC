<?php
// MCC\dashboard\api_reservation\cancel_reservation.php - FIXED VERSION
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../../db_connect.php';

$user_id = $_SESSION['user_id'];
$reservation_id = $_POST['id'] ?? 0;
$type = $_POST['type'] ?? 'room';

try {
    $conn->begin_transaction();
    
    if ($type === 'room') {
        // Get reservation details
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reservation_id, $user_id);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();
        
        if (!$reservation) {
            throw new Exception("Reservation not found");
        }
        
        // Update reservation status - FIXED to set refund_status properly
        $update_stmt = $conn->prepare("
            UPDATE reservations 
            SET status = 'cancelled', 
                payment_status = 'cancelled',
                cancelled_at = NOW(),
                cancellation_reason = 'User requested cancellation',
                refund_status = 'not_requested'
            WHERE id = ? AND user_id = ?
        ");
        $update_stmt->bind_param("ii", $reservation_id, $user_id);
        $update_stmt->execute();
        
    } else {
        // Facility booking
        $stmt = $conn->prepare("SELECT * FROM facility_bookings WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $reservation_id, $user_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        if (!$booking) {
            throw new Exception("Booking not found");
        }
        
        // Update booking status - FIXED to set refund_status properly
        $update_stmt = $conn->prepare("
            UPDATE facility_bookings 
            SET status = 'cancelled',
                cancelled_at = NOW(),
                refund_status = 'not_requested'
            WHERE booking_id = ? AND user_id = ?
        ");
        $update_stmt->bind_param("ii", $reservation_id, $user_id);
        $update_stmt->execute();
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>