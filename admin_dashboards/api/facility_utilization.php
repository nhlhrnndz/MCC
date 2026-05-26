<?php
session_start();
header('Content-Type: application/json');
include '../../db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$response = [];

try {
    // Combine ALL facility bookings and room reservations
    $stmt = $conn->query("
        (
            SELECT 
                facility_name as name,
                COUNT(*) as booking_count,
                'facility' as type
            FROM facility_bookings 
            WHERE status = 'confirmed'
            GROUP BY facility_name
        )
        UNION ALL
        (
            SELECT 
                CONCAT(room_type, ' (Room)') as name,
                COUNT(*) as booking_count,
                'room' as type
            FROM reservations 
            WHERE status = 'confirmed'
            GROUP BY room_type
        )
        ORDER BY booking_count DESC
        LIMIT 4
    ");
    
    $combined_data = $stmt->fetch_all(MYSQLI_ASSOC);

    // Define realistic MONTHLY capacity for different types
    $capacity_map = [
        // Facility capacities (monthly - more realistic)
        'Private Pool' => 8,    // ~2 bookings per week
        'Tennis Court' => 12,   // ~3 bookings per week
        'Function Hall' => 4,   // ~1 booking per week
        'Public Pool' => 8,
        'Badminton' => 12,
        
        // Room capacities (monthly - more realistic)
        'Deluxe Room (Room)' => 8,     // 2 rooms × 4 weeks
        'Family Suite (Room)' => 4,    // 1 suite × 4 weeks
        'Standard Room (Room)' => 12,  // 3 rooms × 4 weeks
        'Test Room (Room)' => 8
    ];

    $utilization_data = [];
    
    foreach ($combined_data as $item) {
        $capacity = $capacity_map[$item['name']] ?? 8; // Default monthly capacity
        $utilization_percentage = min(round(($item['booking_count'] / $capacity) * 100), 95);
        
        $utilization_data[] = [
            'name' => $item['name'],
            'utilization' => $utilization_percentage,
            'bookings' => (int)$item['booking_count'],
            'type' => $item['type']
        ];
    }

    // Use real data and only fill missing slots with low utilization placeholders
    if (count($utilization_data) > 0) {
        $response = $utilization_data;
        
        // If we have less than 4 items, fill with low utilization placeholders
        if (count($response) < 4) {
            $placeholders = [
                ['name' => 'Tennis Court', 'utilization' => 17, 'bookings' => 2, 'type' => 'facility'],
                ['name' => 'Conference Room', 'utilization' => 13, 'bookings' => 1, 'type' => 'facility'],
                ['name' => 'Guest Room', 'utilization' => 25, 'bookings' => 2, 'type' => 'room'],
                ['name' => 'Available Soon', 'utilization' => 0, 'bookings' => 0, 'type' => 'info']
            ];
            
            for ($i = count($response); $i < 4; $i++) {
                $response[] = $placeholders[$i];
            }
        }
        
    } else {
        // No data at all
        $response = [
            ['name' => 'No Bookings Yet', 'utilization' => 0, 'bookings' => 0, 'type' => 'info'],
            ['name' => 'Check Back Later', 'utilization' => 0, 'bookings' => 0, 'type' => 'info'],
            ['name' => 'Facilities Ready', 'utilization' => 0, 'bookings' => 0, 'type' => 'info'],
            ['name' => 'For Booking', 'utilization' => 0, 'bookings' => 0, 'type' => 'info']
        ];
    }

} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}

echo json_encode($response);
?>