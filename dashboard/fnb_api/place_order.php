<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to place order']);
    exit();
}

$user_id = $_SESSION['user_id'];
$menu_item_id = intval($_POST['menu_item_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$unit_price = floatval($_POST['unit_price'] ?? 0);

if ($menu_item_id <= 0 || $quantity <= 0 || $unit_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order data']);
    exit();
}

// First, verify the menu item exists and is available
$check_stmt = $conn->prepare("SELECT id, name, price FROM fnb_menu WHERE id = ? AND is_available = 1");
$check_stmt->bind_param("i", $menu_item_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Selected menu item is not available']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

$conn->begin_transaction();

try {
    $total_amount = $quantity * $unit_price;
    
    $stmt = $conn->prepare("INSERT INTO fnb_orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("id", $user_id, $total_amount);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order: ' . $stmt->error);
    }
    
    $order_id = $stmt->insert_id;
    $stmt->close();
    
    $stmt = $conn->prepare("INSERT INTO fnb_order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $order_id, $menu_item_id, $quantity, $unit_price);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add order item: ' . $stmt->error);
    }
    
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order placed successfully', 'order_id' => $order_id]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>