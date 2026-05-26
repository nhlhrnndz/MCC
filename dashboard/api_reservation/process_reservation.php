<?php
// dashboard/api_reservation/process_reservation.php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get data from session
$user_id = $_SESSION['user_id'] ?? 0;
$full_name = $_SESSION['full_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$contact_number = $_SESSION['contact_number'] ?? '';
$checkin_date = $_SESSION['checkin_date'] ?? '';
$checkout_date = $_SESSION['checkout_date'] ?? '';
$arrival_time = $_SESSION['arrival_time'] ?? '';
$adults = $_SESSION['adults'] ?? 1;
$children = $_SESSION['children'] ?? 0;
$room_type = $_SESSION['selected_room'] ?? '';
$room_number = $_SESSION['room_number'] ?? '';
$room_rate = $_SESSION['room_rate'] ?? 0;
$special_requests = $_SESSION['special_requests'] ?? '';
$payment_method = $_SESSION['payment_method'] ?? '';

try {
    // Validate required fields
    if (empty($room_type) || empty($room_number) || empty($full_name) || empty($email) || empty($checkin_date) || empty($checkout_date)) {
        throw new Exception('Please complete all required fields');
    }

    // Calculate total amount and nights
    $checkin = new DateTime($checkin_date);
    $checkout = new DateTime($checkout_date);
    $nights = $checkin->diff($checkout)->days;
    $total_amount = $room_rate * $nights;

    // Calculate deposit
    if ($payment_method === 'gcash_deposit') {
        $deposit_amount = $total_amount * 0.20;
    } elseif ($payment_method === 'gcash_full') {
        $deposit_amount = $total_amount;
    } else {
        $deposit_amount = 500;
    }

    // ========== DOUBLE BOOKING PROTECTION ==========
    $check_availability = $conn->prepare("
        SELECT id FROM reservations 
        WHERE room_number = ? 
        AND checkin_date < ? 
        AND checkout_date > ? 
        AND status IN ('pending', 'confirmed')
        LIMIT 1
    ");
    
    $check_availability->bind_param("sss", $room_number, $checkout_date, $checkin_date);
    $check_availability->execute();
    $check_availability->store_result();
    
    if ($check_availability->num_rows > 0) {
        throw new Exception('Sorry, this room is already booked for the selected dates. Please choose different dates or another room.');
    }
    $check_availability->close();
    // ========== END DOUBLE BOOKING PROTECTION ==========

    // Generate reservation reference
    $reservation_ref = 'RES' . date('Ymd') . strtoupper(uniqid());

    // Create reservation
    $stmt = $conn->prepare("
        INSERT INTO reservations 
        (reservation_ref, user_id, full_name, email, contact_number, 
         checkin_date, checkout_date, nights, arrival_time, adults, children,
         room_type, room_number, room_rate, total_amount, payment_method, 
         special_requests, status, payment_status, deposit_amount, amount_paid) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, 0.00)
    ");

    $stmt->bind_param(
        "sissssssiissdssssd", 
        $reservation_ref, $user_id, $full_name, $email, $contact_number,
        $checkin_date, $checkout_date, $nights, $arrival_time, $adults, $children,
        $room_type, $room_number, $room_rate, $total_amount, $payment_method,
        $special_requests, $deposit_amount
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create reservation: " . $stmt->error);
    }
    
    $reservation_id = $stmt->insert_id;
    $stmt->close();

    // Clear session data after successful reservation
    $keys_to_clear = ['current_step', 'selected_room', 'room_number', 'room_rate', 'checkin_date', 
                     'checkout_date', 'arrival_time', 'adults', 'children', 'special_requests', 
                     'payment_method', 'full_name', 'email', 'contact_number'];
    foreach ($keys_to_clear as $key) {
        unset($_SESSION[$key]);
    }

    // Store reservation reference for success pages
    $_SESSION['reservation_ref'] = $reservation_ref;

    // Handle GCash payment
    if (strpos($payment_method, 'gcash') !== false) {
        // Generate payment data
        $payment_id = 'gcash_' . uniqid() . '_' . time();
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // Create GCash payment record
        $payment_stmt = $conn->prepare("
            INSERT INTO gcash_payments 
            (payment_id, reservation_ref, amount, customer_email, customer_name, 
             status, expires_at, payment_method) 
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
        ");

        $payment_stmt->bind_param(
            "ssdssss",
            $payment_id,
            $reservation_ref,
            $deposit_amount,
            $email,
            $full_name,
            $expires_at,
            $payment_method
        );

        if (!$payment_stmt->execute()) {
            throw new Exception("Failed to create payment record: " . $payment_stmt->error);
        }
        $payment_stmt->close();

        // Store payment info in session
        $_SESSION['gcash_payment_id'] = $payment_id;
        $_SESSION['payment_amount'] = $deposit_amount;
        $_SESSION['payment_method_selected'] = $payment_method;

        echo json_encode([
            'success' => true,
            'redirect' => 'gcash_checkout.php?ref=' . $reservation_ref,
            'reservation_ref' => $reservation_ref,
            'payment_id' => $payment_id
        ]);

    } else {
        // For non-GCash payments (property_deposit)
        $_SESSION['success'] = 'Reservation submitted successfully! Please complete your ₱500 deposit payment within 24 hours.';
        
        echo json_encode([
            'success' => true,
            'redirect' => 'payment_success.php?ref=' . $reservation_ref,
            'reservation_ref' => $reservation_ref
        ]);
    }

} catch (Exception $e) {
    error_log("Reservation API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>