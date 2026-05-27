<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to cancel order']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$check_stmt = $conn->prepare("SELECT status FROM fnb_orders WHERE id = ? AND user_id = ?");
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($row['status'] !== 'pending' && $row['status'] !== 'received') {
        echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled at this stage (Current status: ' . $row['status'] . ')']);
        $check_stmt->close();
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to you']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

$stmt = $conn->prepare("UPDATE fnb_orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>