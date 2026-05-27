<?php
session_start();
header('Content-Type: application/json');

require_once '../../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';

$allowed_statuses = ['pending', 'received', 'preparing', 'ready', 'delivered', 'cancelled'];

if ($order_id <= 0 || !in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID or status']);
    exit();
}

$stmt = $conn->prepare("UPDATE fnb_orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>