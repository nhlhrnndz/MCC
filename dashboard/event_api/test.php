<?php
// Test endpoint to check if API is working
require_once 'config/database.php';
require_once 'helpers/SessionManager.php';

SessionManager::startSession();

header("Content-Type: application/json; charset=UTF-8");

// Initialize database connection
$database = new DatabaseConfig();
$db = $database->getConnection();

if ($db) {
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'session' => SessionManager::getUserData()
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
}
?>