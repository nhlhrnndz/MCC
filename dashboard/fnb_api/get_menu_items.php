<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to view menu']);
    exit();
}

$query = "SELECT * FROM fnb_menu WHERE is_available = 1 ORDER BY category, name";
$result = $conn->query($query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $conn->error]);
    exit();
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(['success' => true, 'data' => $items]);
$conn->close();
?>