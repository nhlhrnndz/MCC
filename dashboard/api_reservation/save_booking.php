<?php
// dashboard/api_reservation/save_booking.php - WITH NOTIFICATIONS

session_start();
$db_connect_path = __DIR__ . '/../../db_connect.php';
if (file_exists($db_connect_path)) {
    include $db_connect_path;
    include __DIR__ . '/../notification_helper.php'; // ADD NOTIFICATION HELPER
} else {
    die("Database connection file not found");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed");
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.history.back();</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$facility_name = trim($_POST["facility_name"] ?? '');
$facility_type = trim($_POST["facility_type"] ?? '');
$booking_date = $_POST["booking_date"] ?? '';
$booking_time = $_POST["booking_time"] ?? '';
$total_amount = floatval($_POST["total_amount"] ?? 0);
$payment_type = $_POST["payment_type"] ?? 'full'; // 'full' or 'deposit'
$hours = isset($_POST["hours"]) ? intval($_POST["hours"]) : NULL;
$guest_count = isset($_POST["guest_count"]) ? intval($_POST["guest_count"]) : NULL;
$addons = NULL;
if (!empty($_POST["addons"]) && is_array($_POST["addons"])) {
    $addons = implode(", ", $_POST["addons"]);
}

// Validation (same as before)
if (empty($facility_name) || empty($booking_date) || empty($booking_time) || $total_amount <= 0) {
    echo "<script>alert('Invalid booking data.'); window.history.back();</script>";
    exit;
}

// Check conflict
$check_sql = "SELECT booking_id FROM facility_bookings WHERE facility_name = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'";
$stmt_check = $conn->prepare($check_sql);
$stmt_check->bind_param("sss", $facility_name, $booking_date, $booking_time);
$stmt_check->execute();
if ($stmt_check->get_result()->num_rows > 0) {
    echo "<script>alert('Slot already booked!'); window.history.back();</script>";
    exit;
}
$stmt_check->close();

// Calculate amount to pay now
$amount_to_pay = $payment_type === 'deposit' ? $total_amount * 0.20 : $total_amount;
$amount_to_pay = round($amount_to_pay, 2);

// Generate unique reference
$reference = "FAC-" . strtoupper(uniqid());

// FIX: Change status from 'pending_payment' to 'pending'
$stmt = $conn->prepare("
    INSERT INTO facility_bookings 
    (user_id, facility_name, facility_type, booking_date, booking_time, hours, guest_count, addons, total_amount, status, payment_type, payment_reference, amount_due_now, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
");

$stmt->bind_param(
    "issssissdssd",
    $user_id, $facility_name, $facility_type, $booking_date, $booking_time,
    $hours, $guest_count, $addons, $total_amount,
    $payment_type, $reference, $amount_to_pay
);

if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;

    // ==================== NOTIFICATION TRIGGER ====================
    try {
        $notificationHelper = new NotificationHelper($conn);
        
        // Create new booking notification
        $notificationHelper->notifyNewBooking(
            $user_id, 
            'facility', 
            $facility_name, 
            $reference
        );
        
        // Create detailed facility booking notification
        $time_display = date('g:i A', strtotime($booking_time));
        $date_display = date('M d, Y', strtotime($booking_date));
        
        $notificationHelper->createNotification(
            $user_id,
            'Facility Booking Submitted',
            "Your {$facility_name} booking for {$date_display} at {$time_display} has been received and is pending confirmation.",
            'facility',
            $booking_id
        );
        
        error_log("✅ Facility booking notifications created for #{$reference}");
        
    } catch (Exception $notificationError) {
        // Don't fail the booking if notifications fail, just log it
        error_log("⚠️ Facility booking notification failed: " . $notificationError->getMessage());
    }
    // ==================== END NOTIFICATION TRIGGER ====================

    // Redirect to GCash checkout
    $redirect_url = "../gcash_checkout.php?" . http_build_query([
        'type'          => 'facility',
        'id'            => $booking_id,
        'ref'           => $reference,
        'amount'        => $amount_to_pay,
        'description'   => "$facility_name • " . date('M d, Y', strtotime($booking_date)) . " " . date('g:i A', strtotime($booking_time)),
        'payment_type'  => $payment_type
    ]);

    echo "<script>window.location.href = '$redirect_url';</script>";
    exit;
} else {
    echo "<script>alert('Booking failed. Try again.'); window.history.back();</script>";
}
$stmt->close();
$conn->close();
?>