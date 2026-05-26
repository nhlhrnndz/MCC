<?php
//admin_dashboards\api\reject_reservation.php
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

if (!$id || !$type) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    if ($type === 'facility') {
        $stmt = $conn->prepare("UPDATE facility_bookings SET status = 'cancelled' WHERE booking_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Booking rejected!']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>