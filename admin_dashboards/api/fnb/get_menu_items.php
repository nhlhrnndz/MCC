<?php
session_start();
header('Content-Type: application/json');

require_once '../../../db_connect.php';

// Check if user is logged in and has fnb_manager role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'fnb_manager') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Helper function to fix image URL for admin panel
function fixImagePath($imagePath) {
    if (empty($imagePath)) {
        return null;
    }
    
    // If it's already a full URL or starts with /MCC/, return as is
    if (strpos($imagePath, 'http') === 0 || strpos($imagePath, '/MCC/') === 0) {
        return $imagePath;
    }
    
    // If it starts with ../upload/, convert to /MCC/upload/
    if (strpos($imagePath, '../upload/') === 0) {
        return '/MCC/' . str_replace('../', '', $imagePath);
    }
    
    // If it starts with upload/, convert to /MCC/upload/
    if (strpos($imagePath, 'upload/') === 0) {
        return '/MCC/' . $imagePath;
    }
    
    // If it's just a filename, assume it's in upload folder
    if (strpos($imagePath, '/') === false) {
        return '/MCC/upload/' . $imagePath;
    }
    
    // Default: add /MCC/ prefix
    return '/MCC/' . $imagePath;
}

// Get single item if ID is provided
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM fnb_menu WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $row['image'] = fixImagePath($row['image']);
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
    $row['image'] = fixImagePath($row['image']);
    $items[] = $row;
}

echo json_encode(['success' => true, 'data' => $items]);
$conn->close();
?>