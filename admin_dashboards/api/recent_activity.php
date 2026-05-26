<?php
session_start();
header('Content-Type: application/json');
include '../../db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$response = [];

try {
    // Get recent user registrations
    $stmt = $conn->query("SELECT full_name, created_at FROM users WHERE status = 'active' ORDER BY created_at DESC LIMIT 3");
    $new_users = $stmt->fetch_all(MYSQLI_ASSOC);

    // Get recent confirmed reservations
    $stmt = $conn->query("SELECT full_name, room_type, created_at FROM reservations WHERE status = 'confirmed' ORDER BY created_at DESC LIMIT 3");
    $confirmed_reservations = $stmt->fetch_all(MYSQLI_ASSOC);

    // Get recent facility bookings
    $stmt = $conn->query("SELECT fb.facility_name, u.full_name, fb.created_at 
                         FROM facility_bookings fb 
                         JOIN users u ON fb.user_id = u.id 
                         WHERE fb.status = 'confirmed' 
                         ORDER BY fb.created_at DESC 
                         LIMIT 3");
    $facility_bookings = $stmt->fetch_all(MYSQLI_ASSOC);

    // Combine and format activities
    $activities = [];
    
    foreach ($new_users as $user) {
        $activities[] = [
            'type' => 'user_registered',
            'title' => 'New user registered',
            'user' => $user['full_name'],
            'time' => $user['created_at'],
            'icon' => 'person-plus',
            'color' => 'success'
        ];
    }
    
    foreach ($confirmed_reservations as $reservation) {
        $activities[] = [
            'type' => 'reservation_confirmed',
            'title' => 'Reservation confirmed',
            'user' => $reservation['full_name'],
            'details' => $reservation['room_type'],
            'time' => $reservation['created_at'],
            'icon' => 'calendar-check',
            'color' => 'primary'
        ];
    }
    
    foreach ($facility_bookings as $booking) {
        $activities[] = [
            'type' => 'facility_booked',
            'title' => 'Facility booking',
            'user' => $booking['full_name'],
            'details' => $booking['facility_name'],
            'time' => $booking['created_at'],
            'icon' => 'building',
            'color' => 'info'
        ];
    }

    // Sort by time and limit to 4
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    
    $activities = array_slice($activities, 0, 4);

    $response = $activities;

} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
?>