<?php
session_start();
require_once '../config/database.php'; // Your database connection

header('Content-Type: application/json');

try {
    $status = $_GET['status'] ?? 'pending';
    $user_id = $_SESSION['user_id']; // Assuming you have user authentication
    
    // Map status for database query
    $status_map = [
        'pending' => 'pending',
        'approved' => 'confirmed', 
        'cancelled' => 'cancelled'
    ];
    
    $db_status = $status_map[$status] ?? 'pending';
    
    $stmt = $pdo->prepare("
        SELECT * FROM reservations 
        WHERE user_id = ? AND payment_status = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id, $db_status]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($reservations ?: []);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch reservations']);
}
?>