<?php
session_start();
header('Content-Type: application/json');

require_once '../../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get specific order if ID is provided
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    // Get order details
    $stmt = $conn->prepare("SELECT * FROM fnb_orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($order = $result->fetch_assoc()) {
        // Get order items
        $items_stmt = $conn->prepare("
            SELECT oi.*, m.name 
            FROM fnb_order_items oi 
            JOIN fnb_menu m ON oi.menu_item_id = m.id 
            WHERE oi.order_id = ?
        ");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        
        $items = [];
        while ($item = $items_result->fetch_assoc()) {
            $items[] = $item;
        }
        
        $order['items'] = $items;
        echo json_encode(['success' => true, 'data' => $order]);
        $items_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
    $stmt->close();
    exit();
}

// Get all orders
$query = "SELECT * FROM fnb_orders ORDER BY created_at DESC";
$result = $conn->query($query);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['success' => true, 'data' => $orders]);
$conn->close();
?>