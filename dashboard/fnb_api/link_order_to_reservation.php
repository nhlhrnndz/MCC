<?php
session_start();
header('Content-Type: application/json');

require_once '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$reservation_id = intval($_POST['reservation_id'] ?? 0);
$event_proposal_id = intval($_POST['event_proposal_id'] ?? 0);

if ($order_id <= 0 || ($reservation_id <= 0 && $event_proposal_id <= 0)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify order belongs to user
$check_stmt = $conn->prepare("SELECT id FROM fnb_orders WHERE id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Update order with reservation or event proposal
if ($reservation_id > 0) {
    $stmt = $conn->prepare("UPDATE fnb_orders SET reservation_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $reservation_id, $order_id);
} else {
    $stmt = $conn->prepare("UPDATE fnb_orders SET event_proposal_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $event_proposal_id, $order_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order linked successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to link order']);
}

$stmt->close();
$conn->close();
?>