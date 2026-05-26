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
    // Get pending reservations
    $stmt = $conn->query("SELECT reservation_ref, full_name, checkin_date, room_type, created_at 
                         FROM reservations 
                         WHERE status = 'pending' 
                         ORDER BY created_at DESC 
                         LIMIT 5");
    $pending_reservations = $stmt->fetch_all(MYSQLI_ASSOC);

    // Get pending facility bookings
    $stmt = $conn->query("SELECT facility_name, user_id, booking_date, booking_time, created_at 
                         FROM facility_bookings 
                         WHERE status = 'pending' 
                         ORDER BY created_at DESC 
                         LIMIT 5");
    $pending_facility_bookings = $stmt->fetch_all(MYSQLI_ASSOC);

    // Combine and format the data
    $pending_approvals = [];
    
    foreach ($pending_reservations as $reservation) {
        $pending_approvals[] = [
            'type' => 'Reservation',
            'title' => $reservation['room_type'] . ' Booking',
            'customer' => $reservation['full_name'],
            'date' => date('M j, Y', strtotime($reservation['checkin_date'])),
            'created_at' => $reservation['created_at']
        ];
    }
    
    foreach ($pending_facility_bookings as $booking) {
        // Get user name for facility booking
        $user_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $booking['user_id']);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_name = $user_result->fetch_assoc()['full_name'] ?? 'Unknown User';
        $user_stmt->close();

        $pending_approvals[] = [
            'type' => 'Facility',
            'title' => $booking['facility_name'] . ' Booking',
            'customer' => $user_name,
            'date' => date('M j, Y', strtotime($booking['booking_date'])),
            'created_at' => $booking['created_at']
        ];
    }

    // Sort by creation date and limit to 3
    usort($pending_approvals, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    $pending_approvals = array_slice($pending_approvals, 0, 3);

    $response = $pending_approvals;

} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
?>