<?php
// MCC/dashboard/gcash_checkout.php
session_start();
require_once '../db_connect.php';

// GET DATA FROM URL
$type         = $_GET['type'] ?? 'room';
$id           = $_GET['id'] ?? null;
$ref          = $_GET['ref'] ?? null;
$amount       = floatval($_GET['amount'] ?? 0);
$payment_type = $_GET['payment_type'] ?? 'full';

if (!$ref || $amount <= 0 || !$type) {
    die("Invalid payment link.");
}

// ——————————————————————————————————————
// 1. DETERMINE THE CORRECT REFERENCE (room or facility)
// ——————————————————————————————————————
$reservation_ref = null;

if ($type === 'facility') {
    $stmt = $conn->prepare("SELECT payment_reference FROM facility_bookings WHERE booking_id = ? AND payment_reference = ?");
    $stmt->bind_param("is", $id, $ref);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reservation_ref = $row['payment_reference'];
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT reservation_ref FROM reservations WHERE reservation_ref = ?");
    $stmt->bind_param("s", $ref);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $reservation_ref = $row['reservation_ref'];
    }
    $stmt->close();
}

if (!$reservation_ref) {
    die("Booking not found or invalid reference.");
}

// ——————————————————————————————————————
// 2. CREATE gcash_payments RECORD ONLY FOR ROOM BOOKINGS (safe from FK error)
// ——————————————————————————————————————
if ($type === 'room') {
    $stmt = $conn->prepare("SELECT id FROM gcash_payments WHERE reservation_ref = ?");
    $stmt->bind_param("s", $reservation_ref);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $payment_id = 'GCASH-' . strtoupper(substr($reservation_ref, 0, 8)) . '-' . time();
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $gcash_type = ($payment_type === 'deposit') ? 'gcash_deposit' : 'gcash_full';

        $insert = $conn->prepare("
            INSERT INTO gcash_payments 
                (payment_id, reservation_ref, amount, payment_type, status, expires_at, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?, NOW())
        ");
        $insert->bind_param("ssdss", $payment_id, $reservation_ref, $amount, $gcash_type, $expires_at);
        $insert->execute();
        $insert->close();
    }
    $stmt->close();
}

// ——————————————————————————————————————
// 3. FETCH FULL BOOKING DETAILS
// ——————————————————————————————————————
$customer_name = "Guest";
$customer_email = "";
$display_details = [];
$booking_status = '';

if ($type === 'facility') {
    $stmt = $conn->prepare("
        SELECT fb.*, u.full_name, u.email 
        FROM facility_bookings fb 
        JOIN users u ON fb.user_id = u.id
        WHERE fb.booking_id = ? AND fb.payment_reference = ?
    ");
    $stmt->bind_param("is", $id, $ref);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) die("Facility booking not found.");

    $booking_status = $booking['status'];

    if ($booking_status === 'confirmed') {
        include 'success_animation.html'; // or paste your full success HTML below
        exit();
    }
    if ($booking_status === 'cancelled') {
        die("Booking was cancelled.");
    }

    $customer_name   = $booking['full_name'];
    $customer_email  = $booking['email'];
    $reservation_ref = $booking['payment_reference']; // final correct ref

    $display_details = [
        'Type'     => ucfirst($booking['facility_type']),
        'Facility' => $booking['facility_name'],
        'Date'     => date('M d, Y', strtotime($booking['booking_date'])),
        'Time'     => date('g:i A', strtotime($booking['booking_time'])),
        'Duration' => $booking['guest_count'] ? $booking['guest_count'] . " guests" : $booking['hours'] . " hrs"
    ];

} else {
    // ROOM BOOKING
    $stmt = $conn->prepare("
        SELECT r.*, u.full_name, u.email 
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.reservation_ref = ?
    ");
    $stmt->bind_param("s", $reservation_ref);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) die("Reservation not found.");

    $booking_status = $booking['status'];

    if ($booking_status === 'confirmed') {
        include 'success_animation.html';
        exit();
    }
    if ($booking_status === 'cancelled') {
        die("Reservation was cancelled.");
    }

    $customer_name   = $booking['full_name'];
    $customer_email  = $booking['email'];
    $reservation_ref = $booking['reservation_ref'];

    $display_details = [
        'Room'      => $booking['room_type'] . ($booking['room_number'] ? " (#{$booking['room_number']})" : ""),
        'Check-in'  => date('M d, Y', strtotime($booking['checkin_date'])),
        'Check-out' => date('M d, Y', strtotime($booking['checkout_date'])),
        'Guests'    => ($booking['adults'] ?? 0) + ($booking['children'] ?? 0) . " total"
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay with GCash — Malaruhatan Country Club</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .gcash-theme { background: linear-gradient(135deg, #00a859, #007c43); color: white; }
        .payment-container { max-width: 500px; margin: 40px auto; border-radius: 16px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
        .qr-container { background: white; padding: 40px 30px; text-align: center; }
        .countdown { font-size: 1.2rem; color: #dc3545; font-weight: bold; margin: 20px 0; }
        .info-box { background: #e3f2fd; border-left: 6px solid #2196F3; padding: 18px; border-radius: 10px; }
        .upload-area { border: 2px dashed #dee2e6; border-radius: 10px; padding: 20px; text-align: center; background: #f8f9fa; cursor: pointer; transition: all 0.3s ease; }
        .upload-area:hover { border-color: #007c43; background: #e8f5e8; }
        .upload-area.dragover { border-color: #007c43; background: #d4edda; }
        .file-preview { max-width: 200px; max-height: 200px; margin: 10px auto; display: none; }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="gcash p-4 text-center">
            <h2 class="mb-2">GCash Payment</h2>
            <p class="mb-0 fs-5">Complete your payment securely</p>
        </div>

        <div class="qr-container">
            <h4 class="mb-4 text-success">Scan QR Code to Pay</h4>
            <div class="mb-4" style="width:260px;height:260px;margin:0 auto;background:#f8f9fa;border:4px dashed #dee2e6;border-radius:16px;display:flex;align-items:center;justify-content:center;">
                <div>
                    <i class="fas fa-qrcode fa-6x text-secondary mb-3"></i>
                    <p class="text-muted fw-bold">GCash QR Code</p>
                </div>
            </div>

            <div class="countdown" id="countdown">Expires in: 29:59</div>

            <div class="info-box mb-4">
                <strong>Send exactly:</strong><br>
                <h3 class="text-primary mb-1">₱<?= number_format($amount, 2) ?></h3>
                <small>to <strong>0917-XXX-XXXX</strong> (Malaruhatan Country Club)</small><br>
                <small class="text-danger">Reference/Note: <strong><?= htmlspecialchars($reservation_ref) ?></strong></small>
            </div>

            <!-- Upload Section -->
            <div class="upload-section mb-4">
                <h5 class="mb-3 text-success">Upload Payment Screenshot</h5>
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <div class="upload-area" id="uploadArea">
                        <input type="file" id="paymentScreenshot" name="payment_screenshot" accept="image/*" style="display:none;">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <p class="mb-2"><strong>Click or drag & drop</strong></p>
                        <p class="small text-muted mb-0">JPG, PNG, GIF, WebP (Max 5MB)</p>
                    </div>
                    <img id="filePreview" class="file-preview img-thumbnail rounded" alt="Preview">
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success" id="uploadBtn" disabled>
                            Upload Screenshot
                        </button>
                    </div>
                </form>
            </div>

            <div class="alert alert-success small">
                <strong>Payment detected automatically in 5–30 seconds!</strong>
            </div>

            <div class="mt-4 text-center">
                <ol class="text-start small mx-auto" style="max-width:340px;">
                    <li>Open GCash App → Scan QR or Send Money</li>
                    <li>Enter amount: <strong>₱<?= number_format($amount, 2) ?></strong></li>
                    <li>In notes put: <strong><?= htmlspecialchars($reservation_ref) ?></strong></li>
                    <li>Take screenshot → Upload above</li>
                </ol>
            </div>
        </div>

        <div class="p-4 bg-light border-top small">
            <h6 class="mb-3">Booking Details</h6>
            <div class="row text-start">
                <div class="col-6"><strong>Reference:</strong></div>
                <div class="col-6 fw-bold"><?= htmlspecialchars($reservation_ref) ?></div>
                <div class="col-6"><strong>Guest:</strong></div>
                <div class="col-6"><?= htmlspecialchars($customer_name) ?></div>
                <?php foreach ($display_details as $label => $value): ?>
                    <div class="col-6"><strong><?= $label ?>:</strong></div>
                    <div class="col-6"><?= htmlspecialchars($value) ?></div>
                <?php endforeach; ?>
                <div class="col-6"><strong>Amount:</strong></div>
                <div class="col-6 text-success fw-bold">₱<?= number_format($amount, 2) ?></div>
            </div>
            <hr>
            <div class="d-grid gap-2">
                <button id="refreshBtn" class="btn btn-success btn-lg">Check Payment Status</button>
                <a href="user_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
<script>

// File upload with AJAX — FINAL WORKING VERSION
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('paymentScreenshot');
    const filePreview = document.getElementById('filePreview');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');

    uploadArea.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', e => handleFileSelection(e.target.files[0]));

    // Drag & drop
    ['dragover', 'dragleave', 'drop'].forEach(event => {
        uploadArea.addEventListener(event, e => {
             e.preventDefault();
            if (event === 'dragover') uploadArea.classList.add('dragover');
            if (event === 'dragleave') uploadArea.classList.remove('dragover');
            if (event === 'drop' && e.dataTransfer.files[0]) handleFileSelection(e.dataTransfer.files[0]);
        });
    });

    function handleFileSelection(file) {
        if (!file) return;
        const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!allowed.includes(file.type)) return showUploadMessage('Invalid file type', 'error');
        if (file.size > 5*1024*1024) return showUploadMessage('File too large (max 5MB)', 'error');

        const reader = new FileReader();
        reader.onload = e => {
            filePreview.src = e.target.result;
            filePreview.style.display = 'block';
            uploadBtn.disabled = false;
            uploadArea.innerHTML = `<i class="fas fa-file-image fa-3x text-success mb-3"></i>
                <p class="mb-2"><strong>${file.name}</strong></p>
                <p class="small text-muted mb-0">Click to change</p>`;
        };
        reader.readAsDataURL(file);
    }

    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!fileInput.files[0]) return showUploadMessage('Select a file first', 'error');

        const original = uploadBtn.innerHTML;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
        uploadBtn.disabled = true;

        const formData = new FormData();
        formData.append('payment_screenshot', fileInput.files[0]);
        formData.append('reservation_ref', '<?= $reservation_ref ?>');
        formData.append('amount', <?= $amount ?>);   // clean float from PHP
        formData.append('type', '<?= $type ?>');   // Add this line - you were missing this!

        fetch('api_reservation/payment_screenshot.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json().catch(() => { throw new Error('Invalid JSON'); }))
        .then(data => {
            if (data.success) {
                showUploadMessage(data.message || 'Upload successful!', 'success');
                filePreview.style.display = 'none';
                uploadArea.innerHTML = `<i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                    <p class="mb-2"><strong>Click or drag & drop to upload</strong></p>
                    <p class="small text-muted mb-0">JPG, PNG, GIF, WebP (Max 5MB)</p>`;
                uploadBtn.disabled = true;
            } else {
                showUploadMessage(data.message || 'Upload failed', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showUploadMessage('Upload failed – server error. Check your server logs.', 'error');
        })
        .finally(() => {
            uploadBtn.innerHTML = original;
            uploadBtn.disabled = false;
        });
    });

    function showUploadMessage(message, type) {
        // Remove existing messages
        const existingAlerts = document.querySelectorAll('.upload-alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show upload-alert mt-3" role="alert">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert after the upload form
        const uploadSection = document.querySelector('.upload-section');
        uploadSection.insertAdjacentHTML('beforeend', alertHTML);
        
        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const alert = document.querySelector('.upload-alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    }
});

// Payment checking functionality
let timeLeft = 30 * 60; // 30 minutes
let countdownInterval, checkInterval;
let checkAttempts = 0;
const maxAttempts = 360; // 30 minutes of checking (360 attempts * 5 seconds)

function updateCountdown() {
    const minutes = String(Math.floor(timeLeft / 60)).padStart(2, '0');
    const seconds = String(timeLeft % 60).padStart(2, '0');
    document.getElementById('countdown').textContent = `Expires in: ${minutes}:${seconds}`;
    if (timeLeft-- <= 0) {
        clearIntervals();
        showExpiredMessage();
    }
}

function clearIntervals() {
    if (countdownInterval) clearInterval(countdownInterval);
    if (checkInterval) clearInterval(checkInterval);
}

function showExpiredMessage() {
    const expiredHTML = `
    <div class="container mt-5">
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
            <h2>Session Expired</h2>
            <p>This payment session has expired. Please initiate a new payment.</p>
            <a href="user_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>`;
    document.body.innerHTML = expiredHTML;
}

async function checkPaymentStatus() {
    if (checkAttempts >= maxAttempts) {
        clearIntervals();
        showExpiredMessage();
        return;
    }
    
    checkAttempts++;
    
    const btn = document.getElementById('refreshBtn');
    const original = btn?.innerHTML || '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    }

    try {
        // Add random parameter to prevent caching
        const random = Math.random().toString(36).substring(7);
        const url = `api_reservation/check_payment_status.php?type=<?= $type ?>&id=<?= urlencode($id ?? '') ?>&ref=<?= urlencode($ref) ?>&t=${Date.now()}&rand=${random}`;
        
        console.log(`[Attempt ${checkAttempts}] Checking payment status...`);
        
        const res = await fetch(url, { 
            method: 'GET',
            cache: 'no-cache',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const data = await res.json();

        console.log(`[Attempt ${checkAttempts}] Payment check result:`, data);

        if (data.success && data.paid === true) {
            console.log('🎉 PAYMENT CONFIRMED! Showing success animation...');
            clearIntervals();
            showSuccessAnimation();
            return;
        } else if (data.success) {
            console.log(`⏳ Payment not confirmed yet. Status: ${data.status}`);
            // Show status update to user
            updateStatusMessage(data.status);
        }
        
        if (data.success && data.status === 'cancelled') {
            console.log('❌ Booking was cancelled');
            clearIntervals();
            showCancelledMessage();
            return;
        }
        
    } catch (error) {
        console.error(`❌ Payment check failed (Attempt ${checkAttempts}):`, error);
        // Show error message to user temporarily
        showTemporaryError('Connection issue - retrying...');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    }
}

function updateStatusMessage(status) {
    // Update the alert message based on status
    const alertElement = document.querySelector('.alert.alert-success');
    if (alertElement) {
        let message = '';
        switch(status) {
            case 'pending':
                message = '<i class="fas fa-clock me-2"></i><strong>Waiting for payment confirmation...</strong>';
                break;
            case 'processing':
                message = '<i class="fas fa-cog me-2"></i><strong>Payment is being processed...</strong>';
                break;
            case 'confirmed':
                message = '<i class="fas fa-check-circle me-2"></i><strong>Payment confirmed! Redirecting...</strong>';
                break;
            default:
                message = '<i class="fas fa-check-circle me-2"></i><strong>Payment detected automatically in 5–30 seconds!</strong>';
        }
        alertElement.innerHTML = message;
    }
}

function showTemporaryError(message) {
    const alertElement = document.querySelector('.alert.alert-success');
    if (alertElement) {
        const originalHTML = alertElement.innerHTML;
        alertElement.className = 'alert alert-warning small';
        alertElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${message}`;
        
        // Revert after 3 seconds
        setTimeout(() => {
            alertElement.className = 'alert alert-success small';
            alertElement.innerHTML = originalHTML;
        }, 3000);
    }
}

function showSuccessAnimation() {
    console.log('🚀 Payment confirmed! Creating success animation...');
    
    // Get the reference from PHP
    const reservationRef = '<?= $reservation_ref ?>';
    console.log('Redirect reference:', reservationRef);
    
    // Create the success animation HTML
    const successHTML = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body, html {
                height: 100%; 
                margin: 0;
                background: linear-gradient(135deg, #d4edda, #c3e6cb);
                font-family: 'Segoe UI', sans-serif;
            }
            .success-container {
                height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                padding: 20px;
            }
            .checkmark {
                font-size: 8rem;
                color: #28a745;
                animation: bounce 1s ease-in-out infinite alternate;
                margin-bottom: 2rem;
                text-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }
            @keyframes bounce {
                0% { transform: scale(1); }
                100% { transform: scale(1.15); }
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            h1 {
                font-size: 3.5rem;
                font-weight: 800;
                color: #155724;
                animation: fadeIn 1s ease-out;
                margin-bottom: 1rem;
            }
            .lead {
                font-size: 1.8rem;
                color: #155724;
                animation: fadeIn 1s ease-out 0.3s both;
                margin-bottom: 0.5rem;
            }
            .redirect-text {
                font-size: 1.2rem;
                color: #155724;
                opacity: 0.8;
                animation: fadeIn 1s ease-out 0.6s both;
            }
            .progress-bar {
                width: 300px;
                height: 6px;
                background: rgba(255,255,255,0.3);
                border-radius: 3px;
                margin-top: 2rem;
                overflow: hidden;
            }
            .progress-fill {
                height: 100%;
                background: #28a745;
                width: 0%;
                animation: progress 3.5s linear forwards;
            }
            @keyframes progress {
                to { width: 100%; }
            }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="checkmark"><i class="fas fa-check-circle"></i></div>
            <h1>Payment Confirmed!</h1>
            <p class="lead">Your <?= $type === 'facility' ? 'facility booking' : 'room reservation' ?> is now secured!</p>
            <p class="redirect-text">Redirecting you to your booking details...</p>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
    </body>
    </html>`;
    
    // Replace the document content
    document.documentElement.innerHTML = successHTML;
    
    // Start the redirect timer AFTER the HTML is loaded
    console.log('✅ Success animation loaded, starting redirect timer...');
    setTimeout(() => {
        console.log('🔄 Redirecting to payment success page...');
        const redirectUrl = `payment_success.php?ref=${encodeURIComponent(reservationRef)}`;
        console.log('Redirect URL:', redirectUrl);
        window.location.href = redirectUrl;
    }, 3500);
}

function showCancelledMessage() {
    const cancelledHTML = `
    <div class="container mt-5">
        <div class="alert alert-warning text-center">
            <i class="fas fa-times-circle fa-3x mb-3 text-danger"></i>
            <h2>Booking Cancelled</h2>
            <p>This booking has been cancelled. Please contact support if this is an error.</p>
            <a href="user_dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>`;
    document.body.innerHTML = cancelledHTML;
}

// Enhanced initialization with multiple strategies
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Payment checker initialized');
    console.log('Booking details:', {
        type: '<?= $type ?>',
        ref: '<?= $ref ?>',
        id: '<?= $id ?>',
        reservation_ref: '<?= $reservation_ref ?>'
    });
    
    updateCountdown();
    countdownInterval = setInterval(updateCountdown, 1000);
    
    // STRATEGY 1: Check immediately and every 3 seconds (faster)
    checkPaymentStatus();
    checkInterval = setInterval(checkPaymentStatus, 3000);
    
    // STRATEGY 2: Manual refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.onclick = function() {
            console.log('🔄 Manual refresh triggered');
            checkPaymentStatus();
        };
    }
    
    // STRATEGY 3: Check when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('📱 Page became visible, checking payment status...');
            checkPaymentStatus();
        }
    });
    
    // STRATEGY 4: Check when user interacts with the page
    document.addEventListener('click', function() {
        console.log('🖱️ User interaction detected, checking payment status...');
        checkPaymentStatus();
    }, { once: true }); // Only trigger once to avoid spam
    
    // STRATEGY 5: Check when network comes online
    window.addEventListener('online', function() {
        console.log('📶 Network online, checking payment status...');
        checkPaymentStatus();
    });
});
</script>
</body>
</html>