<?php
//MCC\admin_dashboards\api\get_refund_details.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../db_connect.php';  

$id = $_GET['id'] ?? 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

try {
    $sql = "
        SELECT 
            ref.*,
            r.reservation_ref, r.full_name, r.email, r.checkin_date, r.checkout_date, r.amount_paid,
            fb.payment_reference, fb.facility_name, fb.booking_date, fb.booking_time, fb.total_amount,
            u.full_name as user_name, u.email as user_email
        FROM refunds ref
        LEFT JOIN reservations r ON ref.reservation_id = r.id AND ref.type = 'room'
        LEFT JOIN facility_bookings fb ON ref.booking_id = fb.booking_id AND ref.type = 'facility'
        LEFT JOIN users u ON ref.requested_by = u.id
        WHERE ref.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $refund = $result->fetch_assoc();

    if ($refund) {
        echo json_encode(['success' => true, 'refund' => $refund]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Refund not found']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>