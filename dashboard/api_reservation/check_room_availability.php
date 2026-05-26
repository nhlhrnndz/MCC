<?php
// dashboard/api_reservation/check_room_availability.php
header('Content-Type: application/json');
require_once '../../db_connect.php';

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get parameters
$room_number = $_GET['room_number'] ?? '';
$checkin_date = $_GET['checkin_date'] ?? '';
$checkout_date = $_GET['checkout_date'] ?? '';
$reservation_id = $_GET['reservation_id'] ?? 0; // For updates, exclude current reservation

if (empty($room_number) || empty($checkin_date) || empty($checkout_date)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Check for overlapping reservations
    $sql = "SELECT COUNT(*) as overlapping_count 
            FROM reservations 
            WHERE room_number = :room_number 
            AND status IN ('pending', 'confirmed')
            AND (
                (checkin_date < :checkout_date AND checkout_date > :checkin_date)
            )";
    
    // Exclude current reservation if updating
    if ($reservation_id > 0) {
        $sql .= " AND id != :reservation_id";
    }
    
    $stmt = $pdo->prepare($sql);
    $params = [
        ':room_number' => $room_number,
        ':checkin_date' => $checkin_date,
        ':checkout_date' => $checkout_date
    ];
    
    if ($reservation_id > 0) {
        $params[':reservation_id'] = $reservation_id;
    }
    
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $is_available = $result['overlapping_count'] == 0;
    
    echo json_encode([
        'success' => true,
        'available' => $is_available,
        'overlapping_count' => $result['overlapping_count'],
        'debug' => [
            'room_number' => $room_number,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'query' => $sql
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>