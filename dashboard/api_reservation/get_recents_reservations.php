<?php
// api_reservation/get_recents_reservations.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
require_once '../../db_connect.php';

$reservations = [];

try {
    // ROOM RESERVATIONS
    $stmt = $conn->prepare("
        SELECT 
            'room' AS type,
            id,
            reservation_ref,
            room_type,
            room_number,
            checkin_date,
            checkout_date,
            nights,
            arrival_time,
            adults,
            children,
            room_rate,
            total_amount,
            payment_method,
            special_requests,
            status,
            payment_status,
            created_at
        FROM reservations 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $stmt->close();

    // FACILITY BOOKINGS
    $stmt = $conn->prepare("
        SELECT 
            'facility' AS type,
            booking_id AS id,
            payment_reference AS reservation_ref,
            facility_name,
            facility_type,
            booking_date AS checkin_date,
            booking_time,
            hours,
            guest_count AS guests,
            total_amount,
            status,
            created_at
        FROM facility_bookings 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    $stmt->close();

    // Sort by date
    usort($reservations, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode([
        'success' => true,
        'reservations' => $reservations
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>