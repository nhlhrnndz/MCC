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
    // --- Total Users (ALL TIME & Growth) ---
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE status = 'active'");
    $total_users = $stmt->fetch_assoc()['total_users'];
    $stmt = $conn->query("SELECT COUNT(*) as last_month_users FROM users WHERE status = 'active' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
    $last_month_users = $stmt->fetch_assoc()['last_month_users'];

    // --- Total CONFIRMED Reservations/Bookings (ALL TIME & Growth) ---
    // Total Confirmed
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'confirmed') + 
            (SELECT COUNT(*) FROM facility_bookings WHERE status = 'confirmed') 
        AS total_confirmed;
    ");
    $total_reservations = $stmt->fetch_assoc()['total_confirmed'];

    // Total Confirmed Last Month
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'confirmed' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)) + 
            (SELECT COUNT(*) FROM facility_bookings WHERE status = 'confirmed' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)) 
        AS last_month_confirmed;
    ");
    $last_month_reservations = $stmt->fetch_assoc()['last_month_confirmed'];


    // --- Total PENDING Reservations/Bookings (ALL TIME & Growth) ---
    // Total Pending
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'pending') + 
            (SELECT COUNT(*) FROM facility_bookings WHERE status = 'pending') 
        AS total_pending;
    ");
    $pending_reservations = $stmt->fetch_assoc()['total_pending'];

    // Total Pending Last Month
    $stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'pending' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)) + 
            (SELECT COUNT(*) FROM facility_bookings WHERE status = 'pending' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)) 
        AS last_month_pending;
    ");
    $last_month_pending = $stmt->fetch_assoc()['last_month_pending'];


    // --- Total CONFIRMED Event Proposals (ALL TIME & Growth) --- // <-- NEW SECTION
    $stmt = $conn->query("SELECT COUNT(*) as total_event_proposals FROM event_proposals WHERE status = 'confirmed'");
    $total_event_proposals = $stmt->fetch_assoc()['total_event_proposals'];

    $stmt = $conn->query("SELECT COUNT(*) as last_month_event_proposals FROM event_proposals WHERE status = 'confirmed' AND MONTH(submitted) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(submitted) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
    $last_month_event_proposals = $stmt->fetch_assoc()['last_month_event_proposals'];


    // Calculate growth percentages
    $users_growth = $last_month_users > 0 ? 
        round((($total_users - $last_month_users) / $last_month_users) * 100, 1) : ($total_users > 0 ? 100 : 0);

    $reservations_growth = $last_month_reservations > 0 ? 
        round((($total_reservations - $last_month_reservations) / $last_month_reservations) * 100, 1) : ($total_reservations > 0 ? 100 : 0);
        
    $pending_reservations_growth = $last_month_pending > 0 ? 
        round((($pending_reservations - $last_month_pending) / $last_month_pending) * 100, 1) : ($pending_reservations > 0 ? 100 : 0);
        
    $event_proposals_growth = $last_month_event_proposals > 0 ? // <-- NEW GROWTH CALC
        round((($total_event_proposals - $last_month_event_proposals) / $last_month_event_proposals) * 100, 1) : ($total_event_proposals > 0 ? 100 : 0);


    // Active Facilities count (optional, keep if needed for other features)
    $stmt = $conn->query("SELECT COUNT(DISTINCT facility_name) as active_facilities FROM facility_bookings WHERE status = 'confirmed'");
    $active_facilities = $stmt->fetch_assoc()['active_facilities'];

    $response = [
        'total_users' => $total_users,
        'total_reservations' => $total_reservations,
        'pending_reservations' => $pending_reservations, 
        'total_event_proposals' => $total_event_proposals, // <-- NEW RESPONSE DATA
        'active_facilities' => $active_facilities,
        'users_growth' => $users_growth,
        'reservations_growth' => $reservations_growth,
        'pending_reservations_growth' => $pending_reservations_growth, 
        'event_proposals_growth' => $event_proposals_growth, // <-- NEW RESPONSE DATA
    ];

} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
?>