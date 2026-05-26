<?php
// MCC/dashboard/api_reservation/payment_screenshot.php - FINAL WORKING VERSION
header('Content-Type: application/json');
session_start();

require_once '../../db_connect.php';

// ================= CONFIGURATION =================
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/MCC/upload/payment_screenshots/';
$webPath = '/MCC/upload/payment_screenshots/';

$allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$maxSize     = 5 * 1024 * 1024; // 5MB

// Create folder if not exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ================= VALIDATE REQUEST =================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['payment_screenshot'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    exit;
}

$reservation_ref = trim($_POST['reservation_ref'] ?? '');
$amount_sent     = floatval($_POST['amount'] ?? 0);

if (empty($reservation_ref)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reservation reference.']);
    exit;
}

$file   = $_FILES['payment_screenshot'];
$name   = $file['name'];
$tmp    = $file['tmp_name'];
$size   = $file['size'];
$error  = $file['error'];

if ($error !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error. Please try again.']);
    exit;
}

if ($size > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 5MB).']);
    exit;
}

// Validate extension
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.']);
    exit;
}

// ================= GENERATE UNIQUE & SAFE FILENAME =================
$newFilename = $reservation_ref . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$serverPath  = $uploadDir . $newFilename;
$dbPath = $webPath . $newFilename;

// ================= SAVE FILE =================
if (!move_uploaded_file($tmp, $serverPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save file. Check folder permissions.']);
    exit;
}

chmod($serverPath, 0644);

// ================= UPDATE DATABASE =================
try {
    // Verify the gcash_payments record exists
    $stmt = $conn->prepare("SELECT id, status FROM gcash_payments WHERE reservation_ref = ?");
    $stmt->bind_param("s", $reservation_ref);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        unlink($serverPath);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Payment record not found.']);
        exit;
    }

    if ($payment['status'] === 'paid') {
        unlink($serverPath);
        echo json_encode(['success' => false, 'message' => 'Payment already confirmed.']);
        exit;
    }

    // Update with screenshot_uploaded status
    $newStatus = 'screenshot_uploaded';
    
    $stmt = $conn->prepare("
        UPDATE gcash_payments 
        SET payment_screenshot = ?, status = ?, updated_at = NOW()
        WHERE reservation_ref = ?
    ");
    $stmt->bind_param("sss", $dbPath, $newStatus, $reservation_ref);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Screenshot uploaded successfully! Our team will verify your payment within 5–30 minutes.'
        ]);
    } else {
        unlink($serverPath);
        echo json_encode(['success' => false, 'message' => 'Database update failed. Please try again.']);
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("payment_screenshot.php error: " . $e->getMessage());
    if (file_exists($serverPath)) unlink($serverPath);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
}
?>