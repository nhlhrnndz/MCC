<?php
// MCC/admin_dashboards/api/confirm_gcash_payment.php

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$post = json_decode(file_get_contents('php://input'), true);

$id   = $post['id'] ?? null;
$type = $post['type'] ?? null;
$ref  = $post['ref'] ?? null;

if (!$id || !$type || !$ref) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    $conn->autocommit(false);

    if ($type === 'facility') {
        $stmt = $conn->prepare("
            SELECT fb.*, u.full_name, u.email, u.contact_number 
            FROM facility_bookings fb 
            JOIN users u ON fb.user_id = u.id 
            WHERE fb.booking_id = ? AND fb.payment_reference = ? AND fb.status = 'pending'
        ");
        $stmt->bind_param("is", $id, $ref);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("Facility booking not found or already confirmed");
        }

        $conn->query("UPDATE facility_bookings SET status = 'confirmed' WHERE booking_id = " . (int)$id);

        $guest_name   = $booking['full_name'];
        $guest_email  = $booking['email'];
        $reference    = $booking['payment_reference'];
        $amount_paid  = $booking['total_amount'];
        $payment_mode = 'GCash Payment';

        $desc = $booking['facility_name'] . " on " . date('F j, Y', strtotime($booking['booking_date'])) . " at " . date('g:i A', strtotime($booking['booking_time']));
        if ($booking['hours'])       $desc .= " • {$booking['hours']} hour(s)";
        if ($booking['guest_count']) $desc .= " • {$booking['guest_count']} guest(s)";

    } else {
        // Room booking
        $stmt = $conn->prepare("
            SELECT r.*, u.full_name, u.email, u.contact_number 
            FROM reservations r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ? AND r.reservation_ref = ? AND r.status = 'pending'
        ");
        $stmt->bind_param("is", $id, $ref);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("Room reservation not found or already confirmed");
        }

        $conn->query("UPDATE reservations SET status = 'confirmed' WHERE id = " . (int)$id);

        $guest_name   = $booking['full_name'];
        $guest_email  = $booking['email'];
        $reference    = $booking['reservation_ref'];
        $amount_paid  = $booking['total_amount'];
        $payment_mode = 'GCash Payment';

        $desc = $booking['room_type'] . " Room • Check-in: " . date('M j, Y', strtotime($booking['checkin_date'])) . " – Check-out: " . date('M j, Y', strtotime($booking['checkout_date']));
    }

    $conn->commit();

    // Generate PDF Voucher (same as above)
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $html = '<html><head><meta charset="utf-8"><style>
        body { font-family: Arial, sans-serif; margin:40px; background:#f9f9fa; color:#333; }
        .voucher { max-width:600px; margin:auto; background:white; border:4px solid #30b661; border-radius:15px; padding:30px; box-shadow:0 10px 30px rgba(0,0,0,0.1); }
        .header { background:linear-gradient(135deg,#30b661,#00b578); color:white; padding:20px; text-align:center; border-radius:12px; margin:-30px -30px 25px -30px; }
        .paid { background:#d4edda; color:#155724; padding:15px; border-radius:10px; text-align:center; font-size:1.8rem; font-weight:bold; margin-bottom:20px; }
        table { width:100%; }
        td { padding:10px 0; border-bottom:1px solid #eee; }
        .label { font-weight:bold; width:160px; }
        .footer { text-align:center; margin-top:40px; color:#666; }
    </style></head><body>
    <div class="voucher">
        <div class="header">
            <h1>Malaruhatan Country Club</h1>
            <p>Official Booking Voucher</p>
        </div>
        <div class="paid">✓ PAYMENT CONFIRMED</div>
        <h3 style="text-align:center;color:#30b661">Thank you, ' . htmlspecialchars($guest_name) . '!</h3>
        <table>
            <tr><td class="label">Reference</td><td>' . htmlspecialchars($reference) . '</td></tr>
            <tr><td class="label">Booking</td><td>' . htmlspecialchars($desc) . '</td></tr>
            <tr><td class="label">Amount Paid</td><td><strong>₱' . number_format($amount_paid, 2) . '</strong></td></tr>
            <tr><td class="label">Payment Method</td><td>' . $payment_mode . '</td></tr>
            <tr><td class="label">Confirmed</td><td>' . date('F j, Y \\a\\t g:i A') . '</td></tr>
        </table>
        <div class="footer">
            <p>See you soon at Malaruhatan! 🏊‍♂️🏸<br>questions@mcc.com • +63 917 123 4567</p>
        </div>
    </div></body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();
    $pdfFile = __DIR__ . '/../../vouchers/voucher_' . $reference . '.pdf';
    file_put_contents($pdfFile, $pdf);

    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('no-reply@mcc.com', 'Malaruhatan CC');
        $mail->addAddress($guest_email, $guest_name);
        $mail->Subject = '✅ Your MCC Booking is Confirmed!';
        $mail->Body = "<h2>Hi {$guest_name}!</h2><p>Your GCash payment of ₱" . number_format($amount_paid, 2) . " has been confirmed!</p><p><strong>Ref:</strong> {$reference}</p><p>See attached voucher. See you soon!</p>";
        $mail->addAttachment($pdfFile, 'MCC_Voucher_' . $reference . '.pdf');
        $mail->send();
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
    }

    echo json_encode(['success' => true, 'message' => 'GCash payment confirmed + voucher sent!']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>