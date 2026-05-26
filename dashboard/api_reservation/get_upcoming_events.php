<?php
// dashboard/api_reservation/get_upcoming_events.php
session_start();
header('Content-Type: application/json');

// Add error handling to prevent HTML output
error_reporting(0); // Turn off error display
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// CORRECTED PATH - since file is in dashboard/api_reservation/
require_once '../../db_connect.php';

try {
    // Test database connection first
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $stmt = $conn->prepare("
        SELECT 
            proposal_id,
            event_title,
            event_type,
            arrival_date,
            arrival_time,
            venue_preference,
            expected_guests,
            total_estimated_cost,
            status,
            deposit_paid,
            balance_paid
        FROM event_proposals 
        WHERE user_id = ? 
          AND status = 'approved'
          AND arrival_date >= CURDATE()
        ORDER BY arrival_date ASC, arrival_time ASC
        LIMIT 10
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $row['arrival_date_formatted'] = date('M j, Y', strtotime($row['arrival_date']));
        $row['arrival_time_formatted'] = date('g:i A', strtotime($row['arrival_time'] ?? '18:00:00'));
        
        // Add payment status
        if ($row['balance_paid']) {
            $row['payment_status'] = 'fully_paid';
            $row['payment_badge'] = 'success';
            $row['payment_text'] = 'Fully Paid';
        } elseif ($row['deposit_paid']) {
            $row['payment_status'] = 'deposit_paid';
            $row['payment_badge'] = 'warning';
            $row['payment_text'] = 'Deposit Paid';
        } else {
            $row['payment_status'] = 'pending_payment';
            $row['payment_badge'] = 'danger';
            $row['payment_text'] = 'Payment Pending';
        }
        
        $iconMap = [
            'wedding'     => 'fa-ring',
            'birthday'    => 'fa-birthday-cake',
            'corporate'   => 'fa-briefcase',
            'conference'  => 'fa-chalkboard-teacher',
            'seminar'     => 'fa-graduation-cap',
            'party'       => 'fa-music',
            'anniversary' => 'fa-heart',
            'reunion'     => 'fa-users',
            'debut'       => 'fa-gem',
            'graduation'  => 'fa-graduation-cap',
            'other'       => 'fa-calendar-check'
        ];
        $row['icon'] = $iconMap[strtolower($row['event_type'] ?? '')] ?? 'fa-calendar-check';

        $events[] = $row;
    }

    echo json_encode([
        'success' => true,
        'events'  => $events,
        'count'   => count($events)
    ]);

} catch (Exception $e) {
    error_log("Upcoming events error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to load events: ' . $e->getMessage()]);
}

// Ensure no extra output
exit;
?>