<?php
session_start();
header('Content-Type: application/json');

require_once '../../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get form data
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$category = trim($_POST['category'] ?? '');
$image = trim($_POST['image'] ?? '');
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Validate required fields
if (empty($name) || empty($description) || $price <= 0 || empty($category)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
    exit();
}

// Insert into database
$stmt = $conn->prepare("INSERT INTO fnb_menu (name, description, price, category, image, is_available) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdssi", $name, $description, $price, $category, $image, $is_available);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Menu item added successfully', 'id' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add menu item: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>