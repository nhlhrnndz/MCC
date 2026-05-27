<?php
session_start();
header('Content-Type: application/json');

require_once '../../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get single item if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM fnb_menu WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
    $stmt->close();
    exit();
}

// Get all menu items
$query = "SELECT * FROM fnb_menu ORDER BY category, name";
$result = $conn->query($query);

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(['success' => true, 'data' => $items]);
$conn->close();
?>