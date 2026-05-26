<?php
// dashboard/payment_cancel.php
session_start();

// Clear payment session data
$keys = ['gcash_payment_id','payment_amount','payment_method_selected','reservation_ref'];
foreach ($keys as $k) unset($_SESSION[$k]);

$_SESSION['info'] = 'Payment was cancelled. You can try again.';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Cancelled</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container text-center mt-5">
        <div class="mb-4">
            <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
        </div>
        <h2 class="text-danger mb-3">Payment Cancelled</h2>
        <p class="lead mb-4">Your payment was cancelled. You can try again.</p>
        <div class="d-grid gap-2 d-md-block">
            <a href="reservation_form.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reservation
            </a>
            <a href="user_dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>