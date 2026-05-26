<?php
// check_payment_status.php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../../db_connect.php';

$ref = $_GET['ref'] ?? '';
$type = $_GET['type'] ?? 'room';

if (empty($ref)) {
    echo json_encode(['success' => false, 'error' => 'No reference provided']);
    exit();
}

try {
    if ($type === 'facility') {
        // Check facility bookings
        $query = "SELECT status, booking_id, facility_name FROM facility_bookings WHERE payment_reference = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();

        if ($booking) {
            echo json_encode([
                'success' => true,
                'paid' => ($booking['status'] === 'confirmed'),
                'booking_type' => 'facility',
                'status' => $booking['status'],
                'timestamp' => time() // Add timestamp to prevent caching
            ]);
            exit();
        }
    } else {
        // Check room reservations
        $query = "SELECT status, id, room_type FROM reservations WHERE reservation_ref = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $ref);
        $stmt->execute();
        $result = $stmt->get_result();
        $booking = $result->fetch_assoc();
        $stmt->close();

        if ($booking) {
            echo json_encode([
                'success' => true,
                'paid' => ($booking['status'] === 'confirmed'),
                'booking_type' => 'room',
                'status' => $booking['status'],
                'timestamp' => time() // Add timestamp to prevent caching
            ]);
            exit();
        }
    }

    // If nothing found
    echo json_encode(['success' => false, 'paid' => false, 'error' => 'Booking not found', 'timestamp' => time()]);

} catch (Exception $e) {
    error_log("Check error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error', 'timestamp' => time()]);
}
?>