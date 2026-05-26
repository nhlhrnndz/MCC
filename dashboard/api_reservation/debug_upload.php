<?php
// debug_upload.php - Fixed version with CORRECT path
header('Content-Type: application/json');

// Enable error reporting to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test database connection with CORRECT path (go up TWO levels)
    $db_path = __DIR__ . '/../../db_connect.php';
    
    if (!file_exists($db_path)) {
        throw new Exception('Database file not found at: ' . $db_path);
    }
    
    require_once $db_path;
    
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn->connect_error ?? 'Unknown error'));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug endpoint working!',
        'database' => 'Connected successfully',
        'file_path' => $db_path,
        'file_exists' => file_exists($db_path) ? 'yes' : 'no'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Debug error: ' . $e->getMessage()
    ]);
}
?>