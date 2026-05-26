<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// CORRECT PATH FOR api/ folder in admin_dashboards/
require_once '../../db_connect.php';

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM refunds WHERE status = 'pending'");
    $row = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'count' => (int)($row['count'] ?? 0)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>