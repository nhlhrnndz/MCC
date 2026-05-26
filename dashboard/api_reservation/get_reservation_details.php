<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }

require_once '../../db_connect.php';
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? 0;
$type = $_GET['type'] ?? 'room';

try {
    if ($type === 'room') {
        $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM facility_bookings WHERE booking_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>