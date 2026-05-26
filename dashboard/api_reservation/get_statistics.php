<?php
// dashboard/api_reservation/get_statistics.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    require_once '../../db_connect.php';

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // 1. UPCOMING RESERVATIONS (rooms & facilities with future dates)
    $upcoming_result = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT id FROM reservations 
            WHERE user_id = $user_id 
            AND status IN ('confirmed', 'pending') 
            AND checkin_date >= CURDATE()
            UNION ALL
            SELECT booking_id as id FROM facility_bookings 
            WHERE user_id = $user_id 
            AND status IN ('confirmed', 'pending') 
            AND booking_date >= CURDATE()
        ) as upcoming
    ");
    $upcoming_count = $upcoming_result->fetch_assoc()['count'];

    // 2. APPROVED EVENTS (only from event_proposals table)
    $approved_events_result = $conn->query("
        SELECT COUNT(*) as count FROM event_proposals 
        WHERE user_id = $user_id 
        AND status IN ('approved', 'confirmed')
    ");
    $approved_events_count = $approved_events_result->fetch_assoc()['count'];

    // 3. PENDING REQUESTS (all pending: rooms, facilities, events)
    $pending_result = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT id FROM reservations WHERE user_id = $user_id AND status = 'pending'
            UNION ALL
            SELECT booking_id as id FROM facility_bookings WHERE user_id = $user_id AND status = 'pending'
            UNION ALL
            SELECT id FROM event_proposals WHERE user_id = $user_id AND status = 'pending'
        ) as pending
    ");
    $pending_count = $pending_result->fetch_assoc()['count'];

    // 4. APPROVED RESERVATIONS (only confirmed rooms & facilities - NOT events)
    $approved_reservations_result = $conn->query("
        SELECT COUNT(*) as count FROM (
            SELECT id FROM reservations WHERE user_id = $user_id AND status = 'confirmed'
            UNION ALL
            SELECT booking_id as id FROM facility_bookings WHERE user_id = $user_id AND status = 'confirmed'
        ) as approved_reservations
    ");
    $approved_reservations_count = $approved_reservations_result->fetch_assoc()['count'];

    echo json_encode([
        'success' => true,
        'statistics' => [
            'upcoming' => $upcoming_count,
            'approved_events' => $approved_events_count,
            'pending' => $pending_count,
            'approved_reservations' => $approved_reservations_count
        ]
    ]);

} catch (Exception $e) {
    error_log("Dashboard statistics error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>