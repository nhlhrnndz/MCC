<?php
session_start();

// Alternative path
require_once '../db_connect.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Temporary debug function to check database structure
function debugTableStructure() {
    global $conn;
    
    if (!$conn) {
        error_log("DEBUG: No database connection");
        return;
    }
    
    try {
        $result = $conn->query("DESCRIBE reservations");
        if ($result) {
            error_log("=== DEBUG: RESERVATIONS TABLE STRUCTURE ===");
            while ($row = $result->fetch_assoc()) {
                error_log("Column: " . $row['Field'] . " | Type: " . $row['Type'] . " | Null: " . $row['Null'] . " | Key: " . $row['Key'] . " | Default: " . $row['Default']);
            }
            error_log("=== END DEBUG ===");
        } else {
            error_log("DEBUG: Failed to describe table: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("DEBUG: Error checking table structure: " . $e->getMessage());
    }
}

// Call this function when the page loads for debugging
debugTableStructure();

// Input validation functions
function validatePhoneNumber($phone) {
    // Basic Philippine phone number validation
    return preg_match('/^(09|\+639)\d{9}$/', $phone);
}

function validateDates($checkin, $checkout) {
    try {
        $checkin_date = new DateTime($checkin);
        $checkout_date = new DateTime($checkout);
        $today = new DateTime();
        
        return $checkin_date >= $today && $checkout_date > $checkin_date;
    } catch (Exception $e) {
        return false;
    }
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}


// Add this function to your reservation_form.php
function debugReservationData() {
    global $conn;
    
    error_log("=== DEBUG RESERVATION DATA ===");
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'NOT SET'));
    error_log("Full Name: " . ($_SESSION['full_name'] ?? 'NOT SET'));
    error_log("Room Type: " . ($_SESSION['selected_room'] ?? 'NOT SET'));
    error_log("Room Number: " . ($_SESSION['room_number'] ?? 'NOT SET'));
    error_log("Payment Method: " . ($_SESSION['payment_method'] ?? 'NOT SET'));
    
    // Test database connection
    if ($conn->connect_error) {
        error_log("DB Connection FAILED: " . $conn->connect_error);
    } else {
        error_log("DB Connection: OK");
    }
    
    error_log("=== END DEBUG ===");
}

// Call this before submitReservation() to see what's happening
debugReservationData();

// ========== UPDATED SUBMIT RESERVATION FUNCTION ==========
function submitReservation() {
    global $conn;
    
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

        // Calculate payment amounts based on payment method - UPDATED FOR DATABASE ENUM
        if ($payment_method === 'gcash_full') {
            $deposit_amount = $total_amount; // Full payment
            $amount_paid = 0.00; // Will be updated when payment is confirmed
            $payment_status = 'pending'; // Using database enum value
        } elseif ($payment_method === 'gcash_deposit') {
            $deposit_amount = $total_amount * 0.20; // 20% deposit
            $amount_paid = 0.00; // Will be updated when payment is confirmed
            $payment_status = 'pending'; // Using database enum value
        } else {
            $deposit_amount = 500; // Property deposit holding fee
            $amount_paid = 0.00; // No payment made yet
            $payment_status = 'pending'; // Using database enum value
        }

        // Double booking protection
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

        // Generate reservation reference
        $reservation_ref = 'RES' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        $status = 'pending';

        $stmt = $conn->prepare("
            INSERT INTO reservations
            (reservation_ref, user_id, full_name, email, contact_number,
             checkin_date, checkout_date, nights, arrival_time, adults, children,
             room_type, room_number, room_rate, total_amount, payment_method,
             special_requests, status, payment_status, deposit_amount, amount_paid)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sissssssiissdssssssdd",
            $reservation_ref,
            $user_id,
            $full_name,
            $email,
            $contact_number,
            $checkin_date,
            $checkout_date,
            $nights,
            $arrival_time,
            $adults,
            $children,
            $room_type,
            $room_number,
            $room_rate,
            $total_amount,
            $payment_method,
            $special_requests,
            $status,
            $payment_status,
            $deposit_amount,
            $amount_paid
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create reservation: " . $stmt->error);
        }
        
        $reservation_id = $stmt->insert_id;
        $stmt->close();

// Inside submitReservation(), right after creating the reservation
if (in_array($payment_method, ['gcash_full', 'gcash_deposit'])) {
    createGcashPayment($reservation_ref, $payment_method, $deposit_amount, $total_amount);

    $_SESSION['gcash_payment_id'] = $_SESSION['gcash_payment_id'] ?? '';
    $_SESSION['payment_amount'] = ($payment_method === 'gcash_full') ? $total_amount : $deposit_amount;
    $_SESSION['reservation_ref'] = $reservation_ref;
    $_SESSION['payment_method_selected'] = $payment_method;

    $_SESSION['current_step'] = 'gcash_payment';
    
    // FIX: Pass ALL required parameters to gcash_checkout.php
    $payment_type = ($payment_method === 'gcash_full') ? 'full' : 'deposit';
    header('Location: gcash_checkout.php?type=room&ref=' . $reservation_ref . '&amount=' . $_SESSION['payment_amount'] . '&payment_type=' . $payment_type);
    exit();
}
        // For property deposit, go to success page
        $_SESSION['last_reservation'] = [
            'reservation_ref' => $reservation_ref,
            'selected_room' => $room_type,
            'room_number' => $room_number,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'total_amount' => $total_amount,
            'deposit_amount' => $deposit_amount,
            'payment_status' => $payment_status
        ];

        // Clear session data after successful reservation
        $keys_to_clear = ['current_step', 'selected_room', 'room_number', 'room_rate', 'checkin_date', 
                         'checkout_date', 'arrival_time', 'adults', 'children', 'special_requests', 
                         'payment_method'];
        foreach ($keys_to_clear as $key) {
            unset($_SESSION[$key]);
        }

        // Set success message and redirect to complete step
        $_SESSION['success'] = 'Reservation submitted successfully! Your reference number is: ' . $reservation_ref;
        $_SESSION['current_step'] = 'complete';

        // Redirect to prevent form resubmission
        header('Location: reservation_form.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        error_log("Reservation submission error: " . $e->getMessage());
        header('Location: reservation_form.php');
        exit();
    }
}

function createGcashPayment($reservation_ref, $payment_method, $deposit_amount, $total_amount) {
    global $conn;
   
    try {
        // Generate unique payment ID
        $payment_id = 'GC' . date('YmdHis') . strtoupper(substr(uniqid(), -6));
       
        // Determine payment amount
        $amount = ($payment_method === 'gcash_full') ? $total_amount : $deposit_amount;
       
        // Set expiration (30 minutes from now)
        $expires_at = date('Y-m-d H:i:s', time() + (30 * 60));
       
        // FIXED: Now matches exactly 5 placeholders and 5 bind variables
        $stmt = $conn->prepare("
            INSERT INTO gcash_payments
            (payment_id, reservation_ref, amount, payment_type, status, expires_at, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?, NOW())
        ");
       
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // FIXED: 5 parameters → "ssdss"
        $stmt->bind_param("ssdss", $payment_id, $reservation_ref, $amount, $payment_method, $expires_at);
       
        if (!$stmt->execute()) {
            throw new Exception("Failed to create GCash payment: " . $stmt->error);
        }
       
        $stmt->close();
       
        // Store payment ID in session
        $_SESSION['gcash_payment_id'] = $payment_id;
        $_SESSION['payment_amount'] = $amount;  // Make sure this is set!
       
        return $payment_id;
       
    } catch (Exception $e) {
        error_log("GCash payment creation error: " . $e->getMessage());
        throw new Exception("Payment system error. Please try again.");
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: user_login.php');
    exit();
}

// Reset for "Make Another Reservation"
if (isset($_GET['new_reservation'])) {
    $keys = ['current_step','selected_room','room_number','room_rate','checkin_date','checkout_date',
             'arrival_time','adults','children','special_requests','payment_method','reservation_ref','success','error'];
    foreach ($keys as $k) unset($_SESSION[$k]);
    $_SESSION['current_step'] = 'room';
}

// Load user data safely
$full_name = $_SESSION['full_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$contact_number = $_SESSION['contact_number'] ?? '';

$_SESSION['current_step'] = $_SESSION['current_step'] ?? 'room';

// Handle POST - ADD SUBMIT RESERVATION HANDLING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    // CSRF validation
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = 'Security validation failed. Please try again.';
        header('Location: reservation_form.php');
        exit();
    }
    
    $_SESSION['current_step'] = $_POST['step'];
    
    // ALWAYS PRESERVE ALL FORM DATA
    foreach ($_POST as $k => $v) {
        if (!in_array($k, ['step','next_step','prev_step','submit_reservation','csrf_token'])) {
            $_SESSION[$k] = htmlspecialchars(trim($v));
        }
    }
    
    // Special handling for room rate to ensure it's numeric
    if (isset($_POST['room_rate'])) {
        $_SESSION['room_rate'] = (float)$_POST['room_rate'];
    }

    if (isset($_POST['next_step'])) {
        switch ($_SESSION['current_step']) {
            case 'room':     
                $_SESSION['current_step'] = empty($_SESSION['selected_room']) ? 'room' : 'info'; 
                if (!empty($_SESSION['selected_room'])) {
                    session_regenerate_id(true);
                }
                break;
            case 'info':     
                // Enhanced validation
                $name_valid = !empty($_SESSION['full_name']) && strlen($_SESSION['full_name']) >= 2;
                $email_valid = !empty($_SESSION['email']) && isValidEmail($_SESSION['email']);
                $phone_valid = !empty($_SESSION['contact_number']) && validatePhoneNumber($_SESSION['contact_number']);
                
                if (!$name_valid || !$email_valid || !$phone_valid) {
                    $_SESSION['current_step'] = 'info';
                    if (!$name_valid) $_SESSION['error'] = 'Please enter a valid full name (at least 2 characters)';
                    elseif (!$email_valid) $_SESSION['error'] = 'Please enter a valid email address';
                    elseif (!$phone_valid) $_SESSION['error'] = 'Please enter a valid Philippine phone number (09XXXXXXXXX or +639XXXXXXXXX)';
                } else {
                    $_SESSION['current_step'] = 'details';
                }
                break;
            case 'details':  
                // Validate that dates are set and valid
                if (empty($_SESSION['checkin_date']) || empty($_SESSION['checkout_date'])) {
                    $_SESSION['current_step'] = 'details';
                    $_SESSION['error'] = 'Please select both check-in and check-out dates';
                } elseif (!validateDates($_SESSION['checkin_date'], $_SESSION['checkout_date'])) {
                    $_SESSION['current_step'] = 'details';
                    $_SESSION['error'] = 'Check-out date must be after check-in date and dates cannot be in the past';
                } else {
                    $_SESSION['current_step'] = 'payment';
                }
                break;
            case 'payment':  
                $_SESSION['current_step'] = empty($_SESSION['payment_method']) ? 'payment' : 'confirm'; 
                break;
        }
    } elseif (isset($_POST['prev_step'])) {
        $steps = ['room','info','details','payment','confirm'];
        $i = array_search($_SESSION['current_step'], $steps);
        if ($i > 0) $_SESSION['current_step'] = $steps[$i - 1];
    } elseif (isset($_POST['submit_reservation'])) {
        // Handle reservation submission
        submitReservation();
    }
}

// Safe calculations - NO WARNINGS, NO ERRORS
$room_rate     = (float)($_SESSION['room_rate'] ?? 0);
$checkin_date  = $_SESSION['checkin_date']  ?? '';
$checkout_date = $_SESSION['checkout_date'] ?? '';

$nights = $total_amount = $deposit = 0;
if ($checkin_date && $checkout_date && $checkin_date < $checkout_date) {
    try {
        $in  = new DateTime($checkin_date);
        $out = new DateTime($checkout_date);
        $nights = $out->diff($in)->days;
        $total_amount = $room_rate * $nights;
        $deposit = $total_amount * 0.20;
    } catch (Exception $e) {
        // Silent fail - values remain 0
    }
}

// Safe display variables
$arrival_time     = $_SESSION['arrival_time']     ?? '14:00';
$adults           = $_SESSION['adults']           ?? '1';
$children         = $_SESSION['children']         ?? '0';
$selected_room    = $_SESSION['selected_room']    ?? '';
$room_number      = $_SESSION['room_number']      ?? '';
$payment_method   = $_SESSION['payment_method']   ?? '';
$special_requests = $_SESSION['special_requests'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Malaruhatan Country Club | Make Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #00b8a9;
            --primary-dark: #00998c;
            --primary-light: #e3f8f6;
            --form-bg: #f9fbfc;
            --white: #ffffff;
        }
        
        body {
            background: linear-gradient(to bottom, #f5f7fa, #e3f8f6);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-container {
            background: var(--form-bg);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .progress-container {
            margin-bottom: 3rem;
            position: relative;
        }
        
        .progress-bar {
            height: 8px;
            background: var(--primary-color);
            transition: width 0.5s ease;
            border-radius: 4px;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            position: relative;
        }
        
        .step-circle {
            width: 50px;
            height: 50px;
            background: #e9ecef;
            color: #6c757d;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 2;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #e9ecef;
            transition: all 0.3s ease;
        }
        
        .step-circle.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        
        .step-circle.completed {
            background: var(--primary-dark);
            color: white;
            box-shadow: 0 0 0 2px var(--primary-dark);
        }
        
        .step-label {
            position: absolute;
            top: 55px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .step-circle.active .step-label,
        .step-circle.completed .step-label {
            color: var(--primary-color);
        }
        
        .btn-proposal {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-proposal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 184, 169, 0.3);
            color: white;
        }
        
        .policy-notice {
            background: #e8f5e8;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .deposit-box {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .summary {
            background: var(--primary-light);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-item.total {
            font-weight: bold;
            font-size: 1.1rem;
            border-bottom: none;
            border-top: 2px solid #dee2e6;
            margin-top: 0.5rem;
            padding-top: 1rem;
        }
        
        .highlight {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .room-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .room-card.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
        }
        
        .placeholder-img {
            height: 180px;
            background: linear-gradient(45deg, #e9ecef, #dee2e6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
        
        .payment-info {
            display: none;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 1rem;
            background: var(--primary-light);
        }
        
        .payment-info.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .child-policy {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 0.75rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .section-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, #dee2e6, transparent);
            margin: 1.5rem 0;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 184, 169, 0.25);
        }
        
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            gap: 1rem;
        }
        
        .qr-code-container {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin: 1rem 0;
        }
        
        .qr-placeholder {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, #e9ecef, #dee2e6);
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .nav-buttons {
                flex-direction: column;
            }
            .nav-buttons .btn {
                width: 100%;
                margin: 5px 0;
            }
            .step-circle {
                width: 40px;
                height: 40px;
            }
            .step-label {
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container text-center">
            <h1 class="fw-bold">Make New Reservation</h1>
            <p class="lead mb-0">Enjoy a serene getaway at Malaruhatan Country Club</p>
        </div>
    </header>

    <div class="container my-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Progress Bar -->
        <div class="progress-container">
            <?php
            $steps = ['room', 'info', 'details', 'payment', 'confirm'];
            $step_labels = ['Room Selection', 'Your Information', 'Reservation Details', 'Payment', 'Confirmation'];
            $current_step_index = array_search($_SESSION['current_step'], $steps);
            $progress_width = ($current_step_index + 1) / count($steps) * 100;
            ?>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar" style="width: <?php echo $progress_width; ?>%"></div>
            </div>
            
            <div class="progress-steps">
                <?php foreach ($steps as $index => $step): ?>
                    <?php
                    $is_active = $_SESSION['current_step'] === $step;
                    $is_completed = $index < $current_step_index;
                    ?>
                    <div class="text-center">
                        <span class="step-circle <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>">
                            <?php if ($is_completed): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo ($index + 1); ?>
                            <?php endif; ?>
                            <span class="step-label"><?php echo $step_labels[$index]; ?></span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($_SESSION['current_step'] === 'complete'): ?>
    <!-- Success Page -->
    <div class="form-container text-center">
        <div class="mb-4">
            <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
        </div>
        <h2 class="text-success mb-3">Reservation Submitted Successfully!</h2>
        
        <?php if (isset($_SESSION['last_reservation'])): ?>
            <div class="card mb-4 border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">Reservation Details</h5>
                    <p class="mb-2"><strong>Reference Number:</strong> <span class="highlight"><?php echo $_SESSION['last_reservation']['reservation_ref']; ?></span></p>
                    <p class="mb-2"><strong>Room:</strong> <?php echo htmlspecialchars($_SESSION['last_reservation']['selected_room']); ?> - <?php echo htmlspecialchars($_SESSION['last_reservation']['room_number']); ?></p>
                    <p class="mb-2"><strong>Check-in:</strong> <?php echo htmlspecialchars($_SESSION['last_reservation']['checkin_date']); ?></p>
                    <p class="mb-2"><strong>Check-out:</strong> <?php echo htmlspecialchars($_SESSION['last_reservation']['checkout_date']); ?></p>
                    <p class="mb-0"><strong>Total Amount:</strong> ₱<?php echo number_format($_SESSION['last_reservation']['total_amount'], 2); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="alert alert-info text-start">
            <h6><i class="fas fa-info-circle me-2"></i>What happens next?</h6>
            <ul class="mb-0">
                <li>We will contact you within 24 hours to confirm your booking</li>
                <li>For online payments, please complete your payment within 2 hours</li>
                <li>You will receive a confirmation email with all details</li>
                <li>For inquiries, call us at (02) 1234-5678</li>
            </ul>
        </div>

        <div class="d-flex justify-content-center nav-buttons mt-4 flex-wrap gap-3">
            <a href="user_dashboard.php" class="btn btn-proposal px-4">
                <i class="fas fa-home me-2"></i>Back to Dashboard
            </a>
            <a href="reservation_form.php?new_reservation=1" class="btn btn-outline-secondary px-4">
                <i class="fas fa-plus me-2"></i>Make Another Reservation
            </a>
        </div>
    </div>

        <?php else: ?>
            <!-- Reservation Form -->
            <form method="POST" id="reservationForm">
                <input type="hidden" name="step" value="<?php echo $_SESSION['current_step']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <!-- PRESERVE ALL FORM DATA ACROSS ALL STEPS -->
                <input type="hidden" name="selected_room" value="<?php echo htmlspecialchars($selected_room); ?>">
                <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                <input type="hidden" name="room_rate" value="<?php echo htmlspecialchars($room_rate); ?>">
                <input type="hidden" name="checkin_date" value="<?php echo htmlspecialchars($checkin_date); ?>">
                <input type="hidden" name="checkout_date" value="<?php echo htmlspecialchars($checkout_date); ?>">
                <input type="hidden" name="arrival_time" value="<?php echo htmlspecialchars($arrival_time); ?>">
                <input type="hidden" name="adults" value="<?php echo htmlspecialchars($adults); ?>">
                <input type="hidden" name="children" value="<?php echo htmlspecialchars($children); ?>">
                <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($payment_method); ?>">
                <input type="hidden" name="special_requests" value="<?php echo htmlspecialchars($special_requests); ?>">
                
                <!-- STEP 1: ROOM SELECTION -->
                <?php if ($_SESSION['current_step'] === 'room'): ?>
                <div class="form-container">
                    <h3 class="mb-4">Select Your Room</h3>
                    
                    <div class="d-flex flex-wrap justify-content-center mb-4 gap-2">
                        <button type="button" class="btn btn-outline-primary category-btn active" data-category="all">All Rooms</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="deluxe">Deluxe</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="premier">Premier</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="family">Family</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="dorm">Dorm</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="grand-deluxe">Grand Deluxe</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="grand-premier">Grand Premier</button>
                        <button type="button" class="btn btn-outline-primary category-btn" data-category="barkada">Barkada</button>
                    </div>

                    <div class="row g-4" id="roomContainer">
                        <!-- Room cards will be loaded by JavaScript -->
                    </div>

                    <div class="d-flex justify-content-between nav-buttons mt-5">
                        <a href="user_dashboard.php" class="btn btn-outline-secondary px-4">
                            <i class="fa fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" name="next_step" class="btn btn-proposal px-4" id="nextStepBtn" disabled>
                            Next<i class="fa fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- STEP 2: USER INFO -->
                <?php if ($_SESSION['current_step'] === 'info'): ?>
                <div class="form-container">
                    <h3 class="mb-4">Your Information</h3>
                    <p class="text-muted mb-4">Please provide your contact details for the reservation</p>

                    <div class="mb-4">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" placeholder="Enter your full name" required>
                        <div class="form-text">Please enter your full name as it appears on your ID</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email address" required>
                        <div class="form-text">We'll send your reservation confirmation to this email</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="contact" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="contact" name="contact_number" value="<?php echo htmlspecialchars($contact_number); ?>" placeholder="e.g., 09171234567 or +639171234567" required>
                        <div class="form-text">Please provide a valid Philippine mobile number</div>
                    </div>

                    <div class="d-flex justify-content-between nav-buttons">
                        <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4">
                            <i class="fa fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="submit" name="next_step" class="btn btn-proposal px-4">
                            Next <i class="fa fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- STEP 3: RESERVATION DETAILS -->
                <?php if ($_SESSION['current_step'] === 'details'): ?>
                <div class="form-container">
                    <h3 class="mb-4">Reservation Details</h3>
                    <p class="text-muted mb-4">Please provide your stay details and preferences</p>

                    <div class="policy-notice">
                        <h6><i class="fas fa-info-circle me-2"></i>Important Reservation Policies</h6>
                        <ul class="mb-0">
                            <li>Check-in time: 2:00 PM | Check-out time: 12:00 NN</li>
                            <li>20% deposit required to confirm booking</li>
                            <li>Free cancellation up to 24 hours before check-in</li>
                            <li>No-shows will forfeit the deposit amount</li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="checkin-date" class="form-label">Check-In Date</label>
                            <input type="date" id="checkin-date" name="checkin_date" value="<?php echo $checkin_date; ?>" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="checkout-date" class="form-label">Check-Out Date</label>
                            <input type="date" id="checkout-date" name="checkout_date" value="<?php echo $checkout_date; ?>" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="arrival-time" class="form-label">Estimated Arrival Time</label>
                        <input type="time" id="arrival-time" name="arrival_time" class="form-control" value="<?php echo $arrival_time; ?>" required>
                        <div class="form-text">Please select your estimated arrival time. Early check-in is subject to availability.</div>
                    </div>

                    <div class="section-divider"></div>

                    <div class="mb-4">
                        <label class="form-label">Number of Guests</label>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="adults" class="form-label">Adults</label>
                                <input type="number" id="adults" name="adults" class="form-control" min="1" max="10" value="<?php echo $adults; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="children" class="form-label">Children</label>
                                <input type="number" id="children" name="children" class="form-control" min="0" max="10" value="<?php echo $children; ?>">
                                <div class="child-policy">
                                    <small><i class="fas fa-child me-1"></i>Children 7 years old and below stay for free. Please inform us of children's ages during check-in.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <div class="mb-4">
                        <label for="special-requests" class="form-label">Additional Requests / Notes</label>
                        <textarea id="special-requests" name="special_requests" class="form-control" rows="4" placeholder="E.g., Late check-in, special requests, dietary restrictions, etc."><?php echo htmlspecialchars($special_requests); ?></textarea>
                    </div>

                    <div class="section-divider"></div>

                    <div class="summary">
                        <h4 class="mb-3">Reservation Summary</h4>
                        <div class="summary-item">
                            <span>Room Rate:</span>
                            <span>₱<?php echo number_format($room_rate, 2); ?> × <span id="nights-count"><?php echo $nights; ?></span> nights</span>
                        </div>
                        <div class="summary-item">
                            <span>Taxes (12%):</span>
                            <span id="taxes-amount">₱<?php echo number_format($total_amount * 0.12, 2); ?></span>
                        </div>
                        <div class="summary-item total">
                            <span>Total Amount:</span>
                            <span id="total-amount">₱<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Required Deposit (20%):</span>
                            <span class="highlight" id="deposit-amount">₱<?php echo number_format($deposit, 2); ?></span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between nav-buttons">
                        <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4">
                            <i class="fa fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="submit" name="next_step" class="btn btn-proposal px-4">
                            Next <i class="fa fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

               <!-- STEP 4: PAYMENT DETAILS -->
                <?php if ($_SESSION['current_step'] === 'payment'): ?>
                <div class="form-container">
                    <h3 class="mb-4">Payment Details</h3>
                    <p class="text-muted mb-4">Secure your reservation with a deposit</p>

                    <div class="deposit-box">
                        <h2 class="mb-2">₱<?php echo number_format($deposit, 2); ?></h2>
                        <p class="mb-1">20% deposit required to confirm booking</p>
                        <small class="text-light">Balance payable upon check-in</small>
                    </div>

                    <div class="policy-notice">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Cancellation Policy</h6>
                        <ul class="mb-0">
                            <li><strong>48+ hours before check-in:</strong> Full refund</li>
                            <li><strong>24-48 hours before check-in:</strong> 50% refund</li>
                            <li><strong>Less than 24 hours:</strong> No refund</li>
                            <li><strong>No-show:</strong> Deposit forfeited, room released after 2 hours</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <label for="payment-method" class="form-label">Select Payment Option</label>
                        <select id="payment-method" name="payment_method" class="form-select" required>
                            <option value="">-- Choose Payment Option --</option>
                            <option value="gcash_full" <?php echo $payment_method == 'gcash_full' ? 'selected' : ''; ?>>GCash - Full Payment (Instant Confirmation)</option>
                            <option value="gcash_deposit" <?php echo $payment_method == 'gcash_deposit' ? 'selected' : ''; ?>>GCash - 20% Deposit Only (Instant Confirmation)</option>
                            <option value="property_deposit" <?php echo $payment_method == 'property_deposit' ? 'selected' : ''; ?>>Pay at Property - ₱500 Holding Fee</option>
                        </select>
                    </div>

                    <!-- Dynamic info boxes -->
                    <div id="gcash-full-info" class="payment-info">
                        <div class="alert alert-success mb-3">
                            <strong><i class="fas fa-bolt me-2"></i>Instant Confirmation</strong>
                        </div>
                        <h6>Full Payment via GCash</h6>
                        <p class="mb-3">Pay 100% of your total amount for instant booking confirmation and best rate guarantee.</p>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-2"><strong>Payment Amount:</strong> ₱<?php echo number_format($total_amount, 2); ?></p>
                            <p class="mb-2"><strong>Confirmation:</strong> Instant upon payment verification</p>
                            <p class="mb-1"><strong>What to expect:</strong></p>
                            <ul class="mb-0">
                                <li>You'll be redirected to GCash payment page</li>
                                <li>Complete payment using QR code or mobile number</li>
                                <li>Instant reservation confirmation</li>
                                <li>Full payment receipt via email</li>
                            </ul>
                        </div>
                        <div class="alert alert-info mt-3">
                            <small><i class="fas fa-info-circle me-2"></i>Free cancellation up to 24 hours before check-in</small>
                        </div>
                    </div>

                    <div id="gcash-deposit-info" class="payment-info">
                        <div class="alert alert-success mb-3">
                            <strong><i class="fas fa-bolt me-2"></i>Instant Confirmation</strong>
                        </div>
                        <h6>Deposit Only via GCash</h6>
                        <p class="mb-3">Pay 20% deposit to secure your reservation. Balance payable upon check-in.</p>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-2"><strong>Deposit Amount:</strong> ₱<?php echo number_format($deposit, 2); ?></p>
                            <p class="mb-2"><strong>Balance Due:</strong> ₱<?php echo number_format($total_amount - $deposit, 2); ?> (at check-in)</p>
                            <p class="mb-2"><strong>Confirmation:</strong> Instant upon payment verification</p>
                            <p class="mb-1"><strong>What to expect:</strong></p>
                            <ul class="mb-0">
                                <li>You'll be redirected to GCash payment page</li>
                                <li>Pay the 20% deposit amount only</li>
                                <li>Instant reservation confirmation</li>
                                <li>Balance payable upon arrival</li>
                            </ul>
                        </div>
                        <div class="alert alert-info mt-3">
                            <small><i class="fas fa-info-circle me-2"></i>Free cancellation up to 24 hours before check-in. Balance due at check-in.</small>
                        </div>
                    </div>

                    <div id="property-deposit-info" class="payment-info">
                        <div class="alert alert-warning mb-3">
                            <strong><i class="fas fa-clock me-2"></i>24-Hour Hold</strong>
                        </div>
                        <h6>Pay at Property</h6>
                        <p class="mb-3">Secure your reservation with a ₱500 holding fee. You must complete the 20% deposit payment within 24 hours.</p>
                        <div class="bg-light p-3 rounded">
                            <p class="mb-2"><strong>Holding Fee:</strong> ₱500.00</p>
                            <p class="mb-2"><strong>Deposit Due:</strong> ₱<?php echo number_format($deposit - 500, 2); ?> (within 24 hours)</p>
                            <p class="mb-2"><strong>Deadline:</strong> 24 hours from reservation</p>
                            <p class="mb-1"><strong>What to expect:</strong></p>
                            <ul class="mb-0">
                                <li>Reservation held for 24 hours</li>
                                <li>Pay ₱500 holding fee now</li>
                                <li>Complete remaining deposit within 24 hours</li>
                                <li>Payment instructions will be emailed</li>
                            </ul>
                        </div>
                        <div class="alert alert-warning mt-3">
                            <small><i class="fas fa-exclamation-triangle me-2"></i>Reservation will auto-cancel if full deposit not paid within 24 hours</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between nav-buttons">
                        <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4">
                            <i class="fa fa-arrow-left me-2"></i>Back
                        </button>
                        <button type="submit" name="next_step" class="btn btn-proposal px-4">
                            Next <i class="fa fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- STEP 5: CONFIRMATION -->
                <?php if ($_SESSION['current_step'] === 'confirm'): ?>
                <div class="form-container">
                    <h3 class="mb-4">Confirm Your Reservation</h3>
                    <p class="text-muted mb-4">Please review all details before submitting your reservation</p>

                    <div class="policy-notice">
                        <h6><i class="fas fa-check-circle me-2"></i>Final Confirmation</h6>
                        <ul class="mb-0">
                            <li>By submitting, you agree to our cancellation and no-show policies</li>
                            <li>Deposit is required to secure your reservation</li>
                            <li>You will receive a confirmation email within 24 hours</li>
                        </ul>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Room Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-1"><strong>Room Type</strong></p>
                                    <p><?php echo htmlspecialchars($selected_room); ?></p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <p class="mb-1"><strong>Nightly Rate</strong></p>
                                    <p class="highlight">₱<?php echo number_format($room_rate, 2); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p class="mb-1"><strong>Room Number</strong></p>
                                    <p><?php echo htmlspecialchars($room_number); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Your Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Name</strong></p>
                                    <p><?php echo htmlspecialchars($full_name); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Email</strong></p>
                                    <p><?php echo htmlspecialchars($email); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Contact Number</strong></p>
                                    <p><?php echo htmlspecialchars($contact_number); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Reservation Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Check-In Date</strong></p>
                                    <p><?php echo !empty($checkin_date) ? htmlspecialchars($checkin_date) : '<span class="text-danger">Not set</span>'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Check-Out Date</strong></p>
                                    <p><?php echo !empty($checkout_date) ? htmlspecialchars($checkout_date) : '<span class="text-danger">Not set</span>'; ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Arrival Time</strong></p>
                                    <p><?php echo htmlspecialchars($arrival_time); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Number of Guests</strong></p>
                                    <p><?php echo (int)$adults + (int)$children; ?> (<?php echo $adults; ?> adults, <?php echo $children; ?> children)</p>
                                </div>
                            </div>
                            <?php if (!empty($special_requests)): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p class="mb-1"><strong>Additional Notes</strong></p>
                                    <p><?php echo htmlspecialchars($special_requests); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Total Amount</strong></p>
                                    <p>₱<?php echo number_format($total_amount, 2); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Deposit Amount</strong></p>
                                    <p class="highlight">₱<?php echo number_format($deposit, 2); ?></p>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Payment Method</strong></p>
                                    <p>
                                        <?php 
                                        $method_text = [
                                            'gcash_full' => 'GCash - Full Payment',
                                            'gcash_deposit' => 'GCash - Deposit Only', 
                                            'property_deposit' => 'Pay at Property'
                                        ];
                                        echo $method_text[$payment_method] ?? $payment_method;
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Status</strong></p>
                                    <?php
                                    $status_text = 'Pending Payment';
                                    $status_class = 'badge bg-warning';
                                    if ($payment_method === 'gcash_full') {
                                        $status_text = 'Instant Confirmation';
                                        $status_class = 'badge bg-success';
                                    } elseif ($payment_method === 'gcash_deposit') {
                                        $status_text = 'Deposit Paid';
                                        $status_class = 'badge bg-success';
                                    } elseif ($payment_method === 'property_deposit') {
                                        $status_text = 'Pending Deposit';
                                        $status_class = 'badge bg-warning';
                                    }
                                    ?>
                                    <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between nav-buttons">
                        <button type="submit" name="prev_step" class="btn btn-outline-secondary px-4">
                            <i class="fa fa-arrow-left me-2"></i>Back
                        </button>
                            <button type="submit" name="submit_reservation" class="btn btn-proposal px-4">
                            <i class="fa fa-check me-2"></i>Submit Reservation
                            </button>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>

     <!-- STEP 6: GCASH PAYMENT -->
<?php if ($_SESSION['current_step'] === 'gcash_payment'): ?>
<div class="form-container text-center">
    <div class="mb-4">
        <i class="fas fa-mobile-alt text-primary" style="font-size: 4rem;"></i>
    </div>
    <h2 class="text-primary mb-3">Complete Your GCash Payment</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['error']; ?>
        </div>
    <?php endif; ?>
    
    <div class="payment-instructions mb-4">
        <p class="lead">Please complete your payment to confirm your reservation</p>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5>Payment Details</h5>
                        <div class="mb-3">
                            <strong>Reference Number:</strong><br>
                            <span class="highlight"><?php echo $_SESSION['reservation_ref'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>Amount to Pay:</strong><br>
                            <span class="highlight" style="font-size: 1.5rem;">₱<?php echo number_format($_SESSION['payment_amount'] ?? 0, 2); ?></span>
                        </div>
                        <div class="mb-3">
                            <strong>Payment Method:</strong><br>
                            <?php 
                            $method_text = [
                                'gcash_full' => 'GCash - Full Payment',
                                'gcash_deposit' => 'GCash - Deposit Only'
                            ];
                            echo $method_text[$_SESSION['payment_method_selected'] ?? ''] ?? 'GCash';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Section - Only show for GCash payments -->
        <div class="qr-code-container mt-4">
            <h5>Scan to Pay via GCash</h5>
            <div class="qr-placeholder">
                <div class="text-center">
                    <i class="fas fa-qrcode fa-5x mb-3"></i>
                    <p>GCash QR Code</p>
                </div>
            </div>
            <div class="mt-3">
                <p class="mb-2"><strong>GCash Number:</strong> 0917-123-4567</p>
                <p class="mb-2"><strong>Account Name:</strong> Malaruhatan Country Club</p>
                <p class="mb-0"><strong>Reference:</strong> <?php echo $_SESSION['reservation_ref'] ?? 'N/A'; ?></p>
            </div>
        </div>

        <div class="mt-4">
            <h5>How to Pay:</h5>
            <div class="row text-start">
                <div class="col-md-8 mx-auto">
                    <ol>
                        <li>Open your GCash app</li>
                        <li>Tap "Scan QR" or "Send Money"</li>
                        <li>If scanning QR: Point camera at the QR code above</li>
                        <li>If sending manually: Enter mobile number <strong>0917-123-4567</strong></li>
                        <li>Enter amount: <strong>₱<?php echo number_format($_SESSION['payment_amount'] ?? 0, 2); ?></strong></li>
                        <li>Add note: <strong>Reservation <?php echo $_SESSION['reservation_ref'] ?? 'N/A'; ?></strong></li>
                        <li>Complete the transaction</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Your reservation will be confirmed automatically once we verify your payment. This usually takes 2-5 minutes.
    </div>

    <div class="d-flex justify-content-center nav-buttons mt-4 flex-wrap gap-3">
        <a href="user_dashboard.php" class="btn btn-outline-secondary px-4">
            <i class="fas fa-home me-2"></i>Back to Dashboard
        </a>
        <button type="button" class="btn btn-proposal px-4" onclick="checkPaymentStatus()">
            <i class="fas fa-sync-alt me-2"></i>Check Payment Status
        </button>
    </div>
</div>

<script>
function checkPaymentStatus() {
    fetch('../api_reservation/check_payment_status.php?ref=<?php echo $_SESSION['reservation_ref'] ?? ''; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.paid) {
                // Payment successful - redirect to success page
                window.location.href = 'reservation_form.php?payment_success=1';
            } else {
                alert('Payment not yet received. Please try again in a few minutes.');
                console.log('Payment status:', data.payment_status, 'Amount paid:', data.amount_paid);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error checking payment status');
        });
}

// Auto-check every 30 seconds
setInterval(checkPaymentStatus, 30000);
</script>
<?php endif; ?>

        <!-- Room Modal -->
        <div class="modal fade" id="roomModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="roomModalTitle">Select Your Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="roomModalBody">
                        <!-- Room details will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-proposal" id="selectRoomBtn">
                            <i class="fas fa-check me-2"></i>Select Room
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// Room data with real images
const roomData = {
    deluxe: {
        title: "Deluxe Room",
        image: "../assets/rooms/deluxe_room1.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 1 & 2</h6>
                <p class="room-meta">2 Persons – Two Single Beds</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>2 Complimentary Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Free Access to Public Pool</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room1" data-price="2950" data-room="Deluxe Room" data-room-number="Room 1">
                        <label class="form-check-label" for="room1">Room 1 – ₱2,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room2" data-price="2950" data-room="Deluxe Room" data-room-number="Room 2">
                        <label class="form-check-label" for="room2">Room 2 – ₱2,950</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 room-tile">
                <h6>Room 5 & 6</h6>
                <p class="room-meta">2 Persons – Queen Size Bed</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>2 Complimentary Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Free Access to Public Pool</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room5" data-price="2950" data-room="Deluxe Room" data-room-number="Room 5">
                        <label class="form-check-label" for="room5">Room 5 – ₱2,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room6" data-price="2950" data-room="Deluxe Room" data-room-number="Room 6">
                        <label class="form-check-label" for="room6">Room 6 – ₱2,950</label>
                    </div>
                </div>
            </div>
            <div class="col-md-6 room-tile">
                <h6>Room 7</h6>
                <p class="room-meta">3 Persons – Queen Size Bed</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>3 Complimentary Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Free Access to Public Pool</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room7" data-price="3950" data-room="Deluxe Room" data-room-number="Room 7">
                        <label class="form-check-label" for="room7">Room 7 – ₱3,950</label>
                    </div>
                </div>
            </div>
        </div>`
    },
    premier: {
        title: "Premier Room", 
        image: "../assets/rooms/premiere_room.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 3 & 4</h6>
                <p class="room-meta">Good for 10 Persons</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>4 Complimentary Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Free Access to Pool 2</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room3" data-price="9950" data-room="Premier Room" data-room-number="Room 3">
                        <label class="form-check-label" for="room3">Room 3 – ₱9,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room4" data-price="9950" data-room="Premier Room" data-room-number="Room 4">
                        <label class="form-check-label" for="room4">Room 4 – ₱9,950</label>
                    </div>
                </div>
            </div>
        </div>`
    },
    family: {
        title: "Family Room",
        image: "../assets/rooms/family_room.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 8</h6>
                <p class="room-meta">Good for 5 Persons</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>5 Complimentary Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Free Access to Public Pool</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room8" data-price="5950" data-room="Family Room" data-room-number="Room 8">
                        <label class="form-check-label" for="room8">Room 8 – ₱5,950</label>
                    </div>
                </div>
            </div>
        </div>`
    },
    dorm: {
        title: "Dorm Type Room",
        image: "../assets/rooms/dorm_room.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 9 & 10</h6>
                <p class="room-meta">Good for 9 Persons</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>Free Access to Public Pool</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room9" data-price="7056" data-room="Dorm Room" data-room-number="Room 9">
                        <label class="form-check-label" for="room9">Room 9 – ₱7,056</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room10" data-price="7056" data-room="Dorm Room" data-room-number="Room 10">
                        <label class="form-check-label" for="room10">Room 10 – ₱7,056</label>
                    </div>
                </div>
            </div>
        </div>`
    },
    "grand-deluxe": {
        title: "Grand Deluxe Room",
        image: "../assets/rooms/grandeluxe_room.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 17, 18 & 19</h6>
                <p class="room-meta">Premium accommodations</p>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room17" data-price="15000" data-room="Grand Deluxe Room" data-room-number="Room 17">
                        <label class="form-check-label" for="room17">Room 17 – ₱15,000</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room18" data-price="15000" data-room="Grand Deluxe Room" data-room-number="Room 18">
                        <label class="form-check-label" for="room18">Room 18 – ₱15,000</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room19" data-price="15000" data-room="Grand Deluxe Room" data-room-number="Room 19">
                        <label class="form-check-label" for="room19">Room 19 – ₱15,000</label>
                    </div>
                </div>
            </div>
        </div>`
    },
    "grand-premier": {
        title: "Grand Premier Room",
        image: "../assets/rooms/grand_premier_room.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 11-16</h6>
                <p class="room-meta">Premium comfort with excellent amenities</p>
                <div class="d-flex flex-wrap gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room11" data-price="4950" data-room="Grand Premier Room" data-room-number="Room 11">
                        <label class="form-check-label" for="room11">Room 11 – ₱4,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room12" data-price="4950" data-room="Grand Premier Room" data-room-number="Room 12">
                        <label class="form-check-label" for="room12">Room 12 – ₱4,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room13" data-price="4950" data-room="Grand Premier Room" data-room-number="Room 13">
                        <label class="form-check-label" for="room13">Room 13 – ₱4,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room14" data-price="4950" data-room="Grand Premier Room" data-room-number="Room 14">
                        <label class="form-check-label" for="room14">Room 14 – ₱4,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room15" data-price="4950" data-room="Grand Premier Room" data-room-number="Room 15">
                        <label class="form-check-label" for="room15">Room 15 – ₱4,950</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room16" data-price="4950" data-room="Grand Premier Room" data-room-number="Room 16">
                        <label class="form-check-label" for="room16">Room 16 – ₱4,950</label>
                    </div>
                </div>
            </div>
        </div>`
    },
    barkada: {
        title: "Barkada Room",
        image: "../assets/rooms/barkada_room.jpg",
        description: `<div class="row g-3">
            <div class="col-md-6 room-tile">
                <h6>Room 20</h6>
                <p class="room-meta">Good for 10–12 Persons</p>
                <div class="room-features">
                    <div class="feature-item">
                        <i class="fas fa-user-friends"></i>
                        <span>6 Complimentary Breakfasts</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-swimming-pool"></i>
                        <span>Free Access to Pool 2</span>
                    </div>
                </div>
                <div class="d-flex gap-3 mt-2 justify-content-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input room-checkbox" id="room20" data-price="12500" data-room="Barkada Room" data-room-number="Room 20">
                        <label class="form-check-label" for="room20">Room 20 – ₱12,500</label>
                    </div>
                </div>
            </div>
        </div>`
    }
};

// ========== FIX FOR NULL APPENDCHILD ERROR ==========
// Only initialize room selection if we're on the room step AND the container exists
<?php if ($_SESSION['current_step'] === 'room'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const roomContainer = document.getElementById('roomContainer');
    if (roomContainer) {
        // Generate room cards only if container exists
        for(const key in roomData){
            const card = document.createElement('div');
            card.className = 'col-md-6 col-lg-4 room-card-wrapper';
            card.dataset.category = key;
            
            // Create card with real image
            card.innerHTML = `<div class="card room-card h-100">
                <img src="${roomData[key].image}" alt="${roomData[key].title}" class="room-image" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTllY2VmIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzZjNzU3ZCIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='; this.alt='Image not available';">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${roomData[key].title}</h5>
                    <p class="text-muted mb-1">Good for multiple persons</p>
                    <button type="button" class="btn btn-outline-primary btn-sm view-details mt-auto" data-room="${key}" data-bs-toggle="modal" data-bs-target="#roomModal">View Details</button>
                </div>
            </div>`;
            roomContainer.appendChild(card);
        }

        // Category filter
        const categoryButtons = document.querySelectorAll('.category-btn');
        const roomCards = document.querySelectorAll('.room-card-wrapper');
        categoryButtons.forEach(btn => btn.addEventListener('click',()=>{
            categoryButtons.forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
            const category = btn.dataset.category;
            roomCards.forEach(card=>{
                card.style.display = (category==='all' || card.dataset.category===category)?'block':'none';
            });
        }));

        // Enable next button only when a room is selected
        const nextStepBtn = document.getElementById('nextStepBtn');
        function checkRoomSelection() {
            const isRoomSelected = <?php echo !empty($_SESSION['selected_room']) ? 'true' : 'false'; ?>;
            nextStepBtn.disabled = !isRoomSelected;
        }
        checkRoomSelection();
    }
});

// Room selection modal functionality
document.addEventListener('click', function(e) { 
    if(e.target.classList.contains('view-details')){
        e.preventDefault();
        const roomType = e.target.dataset.room;
        const modalTitle = document.getElementById('roomModalTitle');
        const modalBody = document.getElementById('roomModalBody');
        
        modalTitle.textContent = roomData[roomType].title;
        modalBody.innerHTML = `
            <div class="text-center mb-4">
                <img src="${roomData[roomType].image}" alt="${roomData[roomType].title}" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;" onerror="this.style.display='none'">
            </div>
            ${roomData[roomType].description}
        `;

        // Initialize room selection in modal
        const roomCheckboxes = modalBody.querySelectorAll('.room-checkbox');
        roomCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    // Uncheck other room checkboxes
                    roomCheckboxes.forEach(otherCb => {
                        if (otherCb !== this) {
                            otherCb.checked = false;
                            otherCb.parentElement.parentElement.classList.remove('selected');
                        }
                    });
                    this.parentElement.parentElement.classList.add('selected');
                } else {
                    this.parentElement.parentElement.classList.remove('selected');
                }
            });
        });

        // Select room button
        const selectRoomBtn = document.getElementById('selectRoomBtn');
        selectRoomBtn.onclick = function() {
            const selectedRoom = modalBody.querySelector('.room-checkbox:checked');
            if (selectedRoom) {
                const roomName = selectedRoom.dataset.room;
                const roomPrice = selectedRoom.dataset.price;
                const roomNumber = selectedRoom.dataset.roomNumber;
                
                // Submit form with selected room
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                form.style.display = 'none';
                
                const fields = {
                    'step': 'room',
                    'selected_room': roomName,
                    'room_number': roomNumber.replace('Room ', ''),  // Clean "Room 1" → "1"
                    'room_rate': roomPrice,
                    'next_step': '1',
                    'csrf_token': '<?php echo $_SESSION['csrf_token']; ?>'
                };
                
                for (const [name, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Please select a room first');
            }
        };
    }
});
<?php endif; ?>

// ========== DETAILS STEP CALCULATIONS ==========
<?php if ($_SESSION['current_step'] === 'details'): ?>
// Get room rate
let roomRate = parseFloat(<?php echo $room_rate > 0 ? $room_rate : 0; ?>);

function calculateTotal() {
    const checkin = document.getElementById('checkin-date').value;
    const checkout = document.getElementById('checkout-date').value;

    if (!checkin || !checkout || roomRate <= 0) {
        resetSummary();
        return;
    }

    const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / (1000 * 60 * 60 * 24));
    if (nights <= 0) { resetSummary(); return; }

    const subtotal = roomRate * nights;
    const taxes = subtotal * 0.12;
    const total = subtotal + taxes;
    const deposit = total * 0.20;

    document.getElementById('nights-count').textContent = nights;
    document.getElementById('taxes-amount').textContent = '₱' + taxes.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    document.getElementById('total-amount').textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    document.getElementById('deposit-amount').textContent = '₱' + deposit.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function resetSummary() {
    document.getElementById('nights-count').textContent = '0';
    document.getElementById('taxes-amount').textContent = '₱0.00';
    document.getElementById('total-amount').textContent = '₱0.00';
    document.getElementById('deposit-amount').textContent = '₱0.00';
}

// Date change listeners
document.getElementById('checkin-date').addEventListener('change', () => {
    const val = document.getElementById('checkin-date').value;
    document.getElementById('checkout-date').min = val;
    if (document.getElementById('checkout-date').value < val) {
        document.getElementById('checkout-date').value = '';
    }
    calculateTotal();
});

document.getElementById('checkout-date').addEventListener('change', () => {
    calculateTotal();
});

// Run immediately
calculateTotal();
window.addEventListener('load', () => setTimeout(calculateTotal, 100));
<?php endif; ?>

// ========== PAYMENT METHOD TOGGLE ==========
<?php if ($_SESSION['current_step'] === 'payment'): ?>
document.addEventListener('DOMContentLoaded', function() {
    const methodSelect = document.getElementById("payment-method");
    const gcashFullInfo = document.getElementById("gcash-full-info");
    const gcashDepositInfo = document.getElementById("gcash-deposit-info");
    const propertyDepositInfo = document.getElementById("property-deposit-info");

    // Hide all info boxes initially
    [gcashFullInfo, gcashDepositInfo, propertyDepositInfo].forEach(el => {
        el.style.display = 'none';
    });

    methodSelect.addEventListener("change", () => {
        // Hide all first
        [gcashFullInfo, gcashDepositInfo, propertyDepositInfo].forEach(el => {
            el.style.display = 'none';
        });
        
        // Show selected
        if (methodSelect.value === "gcash_full") {
            gcashFullInfo.style.display = 'block';
        } else if (methodSelect.value === "gcash_deposit") {
            gcashDepositInfo.style.display = 'block';
        } else if (methodSelect.value === "property_deposit") {
            propertyDepositInfo.style.display = 'block';
        }
    });

    // Trigger change on page load
    if (methodSelect.value) {
        methodSelect.dispatchEvent(new Event('change'));
    }
});
<?php endif; ?>

// ========== FORM VALIDATION ==========
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reservationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            const emailField = document.getElementById('email');
            if (emailField && emailField.value && !isValidEmail(emailField.value)) {
                isValid = false;
                emailField.classList.add('is-invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                }
            });
        });
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>
</body>
</html>