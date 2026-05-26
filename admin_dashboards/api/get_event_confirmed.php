<?php
// get_event_confirmed.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$dbname = 'mcc'; // Replace with your database name
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to fetch confirmed event proposals
    $sql = "SELECT 
                id, 
                proposal_id, 
                full_name, 
                email,
                contact_number,
                event_title, 
                event_type,
                event_date, 
                arrival_date,
                arrival_time,
                venue_preference,
                expected_guests,
                total_estimated_cost,
                final_quote_amount,
                status,
                deposit_paid,
                balance_paid,
                submitted,
                updated_at
            FROM event_proposals 
            WHERE status = 'confirmed'
            ORDER BY event_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count total confirmed proposals
    $count_sql = "SELECT COUNT(*) as total FROM event_proposals WHERE status = 'confirmed'";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute();
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'proposals' => $proposals,
        'count' => (int)$count_result['total']
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_event_confirmed.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch confirmed proposals. Please try again later.'
    ]);
}
?>