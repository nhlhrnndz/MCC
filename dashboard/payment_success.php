<?php
// payment_success.php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['ref'])) {
    header('Location: user_login.php');
    exit();
}

$ref = $_GET['ref'];
$user_id = $_SESSION['user_id'];
$booking_type = '';
$booking_data = null;

// First try to find as ROOM reservation
$stmt = $conn->prepare("
    SELECT 
        r.*, 
        gp.amount as paid_amount, 
        gp.payment_type,
        'room' as booking_type
    FROM reservations r
    LEFT JOIN gcash_payments gp ON r.reservation_ref = gp.reservation_ref
    WHERE r.reservation_ref = ? AND r.user_id = ?
");
$stmt->bind_param("si", $ref, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$room_booking = $result->fetch_assoc();
$stmt->close();

if ($room_booking) {
    $booking_type = 'room';
    $booking_data = $room_booking;
} else {
    // If not found as room, try as FACILITY booking
    $stmt = $conn->prepare("
        SELECT 
            fb.*, 
            gp.amount as paid_amount, 
            gp.payment_type,
            'facility' as booking_type,
            u.email
        FROM facility_bookings fb
        LEFT JOIN gcash_payments gp ON fb.payment_reference = gp.reservation_ref
        LEFT JOIN users u ON fb.user_id = u.id
        WHERE fb.payment_reference = ? AND fb.user_id = ?
    ");
    $stmt->bind_param("si", $ref, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $facility_booking = $result->fetch_assoc();
    $stmt->close();

    if ($facility_booking) {
        $booking_type = 'facility';
        $booking_data = $facility_booking;
    }
}

// Check if booking exists and is confirmed
if (!$booking_data) {
    $_SESSION['error'] = 'Booking not found.';
    header('Location: user_dashboard.php');
    exit();
}

if ($booking_data['status'] !== 'confirmed') {
    $_SESSION['error'] = 'Booking is not yet confirmed. Please wait for payment verification.';
    header('Location: user_dashboard.php');
    exit();
}

// Clear GCash session data
unset($_SESSION['gcash_payment_id'], $_SESSION['payment_amount'], $_SESSION['reservation_ref']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful! - Malaruhatan Country Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #d4edda, #c3e6cb); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
        }
        .success-card { 
            max-width: 600px; 
            margin: 0 auto; 
            text-align: center; 
            padding: 3rem; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            background: white;
        }
        .checkmark {
            font-size: 5rem;
            color: #28a745;
            animation: bounce 1s infinite alternate;
        }
        @keyframes bounce {
            from { transform: scale(1); }
            to { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="checkmark mb-4">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="text-success mb-3">Payment Successful!</h1>
            <h3 class="mb-4">Your <?php echo $booking_type === 'facility' ? 'facility booking' : 'reservation'; ?> is now <strong class="text-success">CONFIRMED</strong></h3>
            
            <div class="alert alert-success mt-4">
                <h5 class="alert-heading">Booking Details</h5>
                <strong>Reference #:</strong> <?php echo $ref; ?><br>
                <strong>Amount Paid:</strong> ₱<?php 
                    if (isset($booking_data['payment_type']) && $booking_data['payment_type'] === 'gcash_full') {
                        echo number_format($booking_data['total_amount'], 2);
                    } else {
                        echo number_format($booking_data['paid_amount'] ?? $booking_data['total_amount'], 2);
                    }
                ?><br>
                <?php if (isset($booking_data['payment_type']) && $booking_data['payment_type'] === 'gcash_deposit'): ?>
                    <small class="text-muted">(20% deposit – balance of ₱<?php echo number_format($booking_data['total_amount'] * 0.80, 2); ?> due on arrival)</small>
                <?php else: ?>
                    <small class="text-muted">(Full payment completed)</small>
                <?php endif; ?>
            </div>

            <div class="booking-info bg-light p-3 rounded mb-4 text-start">
                <?php if ($booking_type === 'room'): ?>
                    <p class="mb-2"><strong>Room:</strong> <?php echo htmlspecialchars($booking_data['room_type']); ?> - <?php echo htmlspecialchars($booking_data['room_number']); ?></p>
                    <p class="mb-2"><strong>Check-in:</strong> <?php echo date('F j, Y', strtotime($booking_data['checkin_date'])); ?></p>
                    <p class="mb-2"><strong>Check-out:</strong> <?php echo date('F j, Y', strtotime($booking_data['checkout_date'])); ?></p>
                    <p class="mb-0"><strong>Guests:</strong> <?php echo $booking_data['adults']; ?> adults, <?php echo $booking_data['children']; ?> children</p>
                <?php else: ?>
                    <p class="mb-2"><strong>Facility:</strong> <?php echo htmlspecialchars($booking_data['facility_name']); ?></p>
                    <p class="mb-2"><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking_data['booking_date'])); ?></p>
                    <p class="mb-2"><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking_data['booking_time'])); ?></p>
                    <p class="mb-2"><strong>Duration:</strong> <?php echo $booking_data['hours'] ?? 'N/A'; ?> hours</p>
                    <p class="mb-0"><strong>Guests:</strong> <?php echo $booking_data['guest_count'] ?? 'N/A'; ?> guests</p>
                <?php endif; ?>
            </div>

            <p class="lead">We have sent a confirmation email to <strong><?php echo htmlspecialchars($booking_data['email']); ?></strong></p>
            
            <div class="mt-4 d-grid gap-2 d-md-flex justify-content-center">
                <a href="user_dashboard.php" class="btn btn-success btn-lg px-5">
                    <i class="fas fa-home me-2"></i> Go to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>