<?php
// MCC/admin_dashboards/api/get_reservation_details.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../db_connect.php';

$id   = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? '';

if ($id <= 0 || !in_array($type, ['room', 'facility'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    $reservation = null;
    $screenshot  = null;

    if ($type === 'room') {
        $stmt = $conn->prepare("
            SELECT 
                r.*,
                u.full_name AS user_name,
                u.email     AS user_email,
                gp.payment_screenshot AS screenshot_path
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            LEFT JOIN gcash_payments gp ON gp.reservation_ref = r.reservation_ref
            WHERE r.id = ?
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT 
                fb.*,
                fb.booking_id        AS id,
                fb.payment_reference AS reservation_ref,
                u.full_name         AS user_name,
                u.email             AS user_email,
                gp.payment_screenshot AS screenshot_path
            FROM facility_bookings fb
            JOIN users u ON fb.user_id = u.id
            LEFT JOIN gcash_payments gp ON gp.reservation_ref = fb.payment_reference
            WHERE fb.booking_id = ?
        ");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }

    // CORRECT PATH: /MCC/upload/payment_screenshots/filename.png
    if (!empty($reservation['screenshot_path'])) {
        $filename = $reservation['screenshot_path'];
        
        // Extract just the filename if there's a path in it
        if (strpos($filename, '/') !== false) {
            $filename = basename($filename);
        }
        
        // Use the correct web URL
        $screenshot = '/MCC/upload/payment_screenshots/' . $filename;
    }

    // SUCCESS
    echo json_encode([
        'success'     => true,
        'reservation' => $reservation,
        'screenshot'  => $screenshot
    ]);

} catch (Exception $e) {
    error_log("get_reservation_details.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>