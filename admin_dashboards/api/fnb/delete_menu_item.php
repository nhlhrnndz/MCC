<?php
session_start();
header('Content-Type: application/json');

require_once '../../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit();
}

// Check if item exists in any order before deleting
$check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM fnb_order_items WHERE menu_item_id = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete item as it has been ordered before']);
    $check_stmt->close();
    exit();
}
$check_stmt->close();

// Delete the item
$stmt = $conn->prepare("DELETE FROM fnb_menu WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Menu item deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete menu item: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>