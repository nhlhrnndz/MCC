<?php
//MCC\admin_dashboards\api\process_refund.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../db_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$refund_id = $input['refund_id'] ?? null;
$action = $input['action'] ?? null;
$admin_notes = $input['admin_notes'] ?? '';

if (!$refund_id || !in_array($action, ['approve', 'reject', 'process'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT * FROM refunds WHERE id = ?");
    $stmt->bind_param("i", $refund_id);
    $stmt->execute();
    $refund = $stmt->get_result()->fetch_assoc();

    if (!$refund) throw new Exception('Refund not found');

    $new_status = $action === 'process' ? 'processed' : $action . 'ed';
    if ($action === 'process' && $refund['status'] !== 'approved') {
        throw new Exception('Only approved refunds can be processed');
    }

    $update = $conn->prepare("UPDATE refunds SET status = ?, processed_at = NOW(), processed_by = ?, admin_notes = ? WHERE id = ?");
    $update->bind_param("sisi", $new_status, $_SESSION['admin_id'], $admin_notes, $refund_id);
    $update->execute();

    if ($refund['type'] === 'room' && $refund['reservation_id']) {
        $conn->query("UPDATE reservations SET refund_status = '$new_status' WHERE id = " . (int)$refund['reservation_id']);
    } elseif ($refund['type'] === 'facility' && $refund['booking_id']) {
        $conn->query("UPDATE facility_bookings SET refund_status = '$new_status' WHERE booking_id = " . (int)$refund['booking_id']);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Refund approved' : ($action === 'reject' ? 'Refund rejected' : 'Refund processed')
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>