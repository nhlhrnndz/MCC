<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to view orders']);
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    
    $stmt = $conn->prepare("SELECT * FROM fnb_orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($order = $result->fetch_assoc()) {
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

$query = "SELECT 
            o.*,
            COUNT(oi.id) as item_count 
          FROM fnb_orders o 
          LEFT JOIN fnb_order_items oi ON o.id = oi.order_id 
          WHERE o.user_id = ? 
          GROUP BY o.id 
          ORDER BY o.created_at DESC";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['success' => true, 'data' => $orders]);
$stmt->close();
$conn->close();
?>