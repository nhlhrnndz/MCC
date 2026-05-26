<?php
// dashboard/api_reservation/get_user_reservations.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

require_once '../../db_connect.php';
$user_id = $_SESSION['user_id'];
$status = $_GET['status'] ?? 'pending';

try {
    $reservations = [];
    $facility_bookings = [];

    // Get room reservations
    $sql = "SELECT r.*, 'room' as type 
            FROM reservations r 
            WHERE r.user_id = ? 
            AND (r.status = ? OR r.payment_status = ?)
            ORDER BY r.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $status, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    // Get facility bookings
    $sql = "SELECT fb.*, 'facility' as type 
            FROM facility_bookings fb 
            WHERE fb.user_id = ? AND fb.status = ?
            ORDER BY fb.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $facility_bookings[] = $row;
    }

    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'facility_bookings' => $facility_bookings
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>