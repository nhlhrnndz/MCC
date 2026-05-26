<?php
// save_room_reservation.php - WITH NOTIFICATIONS
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Fix the database connection path
try {
    require_once __DIR__ . '/../../db_connect.php';
    require_once __DIR__ . '/../notification_helper.php'; // ADD NOTIFICATION HELPER
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Function to generate reservation reference
function generateReservationRef() {
    return 'RES' . date('YmdHis') . mt_rand(100, 999);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log the request
        error_log("=== RESERVATION API CALL ===");
        error_log("POST Data: " . print_r($_POST, true));
        
        // Get POST data
        $user_id = $_POST['user_id'] ?? null;
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $contact_number = $_POST['contact_number'] ?? '';
        $checkin_date = $_POST['checkin_date'] ?? '';
        $checkout_date = $_POST['checkout_date'] ?? '';
        $arrival_time = $_POST['arrival_time'] ?? '';
        $adults = intval($_POST['adults'] ?? 1);
        $children = intval($_POST['children'] ?? 0);
        $room_type = $_POST['room_type'] ?? '';
        $room_number = $_POST['room_number'] ?? '';
        $room_rate = floatval($_POST['room_rate'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? '';
        $special_requests = $_POST['special_requests'] ?? '';
        $total_amount = floatval($_POST['total_amount'] ?? 0);
        $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);

        // Validate required fields
        if (!$user_id || !$checkin_date || !$checkout_date || !$room_type || !$room_number) {
            throw new Exception('Missing required fields');
        }

        // Calculate nights
        $checkin = new DateTime($checkin_date);
        $checkout = new DateTime($checkout_date);
        $nights = $checkout->diff($checkin)->days;

        if ($nights <= 0) {
            throw new Exception('Check-out date must be after check-in date');
        }

        // Generate reservation reference
        $reservation_ref = generateReservationRef();

        // Set default values
        $payment_status = 'pending';
        $amount_paid = 0.00;
        $status = 'pending';

        // Let's first check what columns actually exist and are required
        $result = $conn->query("DESCRIBE reservations");
        $columns = [];
        $required_columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
            if ($row['Null'] === 'NO' && $row['Default'] === null && $row['Extra'] !== 'auto_increment') {
                $required_columns[] = $row['Field'];
            }
        }
        
        error_log("All columns: " . implode(', ', $columns));
        error_log("Required columns: " . implode(', ', $required_columns));
        error_log("Total columns: " . count($columns));

        // Build the SQL dynamically based on actual table structure
        $insert_columns = [];
        $placeholders = [];
        $params = [];
        $types = '';
        
        // Define which columns we're inserting and their values
        $column_values = [
            'reservation_ref' => ['value' => $reservation_ref, 'type' => 's'],
            'user_id' => ['value' => $user_id, 'type' => 'i'],
            'full_name' => ['value' => $full_name, 'type' => 's'],
            'email' => ['value' => $email, 'type' => 's'],
            'contact_number' => ['value' => $contact_number, 'type' => 's'],
            'checkin_date' => ['value' => $checkin_date, 'type' => 's'],
            'checkout_date' => ['value' => $checkout_date, 'type' => 's'],
            'nights' => ['value' => $nights, 'type' => 'i'],
            'arrival_time' => ['value' => $arrival_time, 'type' => 's'],
            'adults' => ['value' => $adults, 'type' => 'i'],
            'children' => ['value' => $children, 'type' => 'i'],
            'room_type' => ['value' => $room_type, 'type' => 's'],
            'room_number' => ['value' => $room_number, 'type' => 's'],
            'room_rate' => ['value' => $room_rate, 'type' => 'd'],
            'total_amount' => ['value' => $total_amount, 'type' => 'd'],
            'deposit_amount' => ['value' => $deposit_amount, 'type' => 'd'],
            'payment_method' => ['value' => $payment_method, 'type' => 's'],
            'special_requests' => ['value' => $special_requests, 'type' => 's'],
            'status' => ['value' => $status, 'type' => 's'],
            'payment_status' => ['value' => $payment_status, 'type' => 's'],
            'amount_paid' => ['value' => $amount_paid, 'type' => 'd']
        ];
        
        foreach ($column_values as $column => $data) {
            $insert_columns[] = $column;
            $placeholders[] = '?';
            $params[] = $data['value'];
            $types .= $data['type'];
        }
        
        $sql = "INSERT INTO reservations (" . implode(', ', $insert_columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        error_log("Generated SQL: " . $sql);
        error_log("Parameters count: " . count($params));
        error_log("Types: " . $types);
        error_log("Columns being inserted: " . implode(', ', $insert_columns));

        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        $reservation_id = $stmt->insert_id;
        $stmt->close();

        error_log("Reservation created successfully! ID: $reservation_id, Ref: $reservation_ref");

        // ==================== NOTIFICATION TRIGGER ====================
        try {
            $notificationHelper = new NotificationHelper($conn);
            
            // Create new booking notification
            $notificationHelper->notifyNewBooking(
                $user_id, 
                'reservation', 
                $room_type, 
                $reservation_ref
            );
            
            // Also create a detailed pending notification
            $notificationHelper->createNotification(
                $user_id,
                'Room Reservation Submitted',
                "Your {$room_type} reservation (#{$reservation_ref}) for {$checkin_date} to {$checkout_date} has been received and is pending confirmation.",
                'reservation',
                $reservation_id
            );
            
            error_log("✅ Notifications created successfully for reservation #{$reservation_ref}");
            
        } catch (Exception $notificationError) {
            // Don't fail the reservation if notifications fail, just log it
            error_log("⚠️ Notification creation failed: " . $notificationError->getMessage());
        }
        // ==================== END NOTIFICATION TRIGGER ====================

        // Return success response
        echo json_encode([
            'success' => true,
            'reservation_id' => $reservation_id,
            'reservation_ref' => $reservation_ref,
            'message' => 'Reservation created successfully!',
            'debug' => [
                'columns_inserted' => count($insert_columns),
                'total_table_columns' => count($columns),
                'notifications_created' => true
            ]
        ]);

    } catch (Exception $e) {
        error_log("Reservation error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Reservation failed: ' . $e->getMessage(),
            'debug' => [
                'post_data' => $_POST,
                'columns_count' => count($columns ?? [])
            ]
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method. Use POST.'
    ]);
}
?>