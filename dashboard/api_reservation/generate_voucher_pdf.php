<?php
// api_reservation/generate_voucher_pdf.php
require_once '../../vendor/autoload.php';  // ← Correct path from here
use Dompdf\Dompdf;
use Dompdf\Options;

function generateVoucherPDF($reservation_data) {
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new Dompdf($options);

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: "DejaVu Sans", Arial, sans-serif; margin: 40px; color: #333; }
            .voucher { border: 4px solid #2E8B57; padding: 40px; max-width: 800px; margin: 0 auto; background: #fff; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
            .header { background: #2E8B57; color: white; padding: 25px; text-align: center; margin: -40px -40px 40px -40px; border-radius: 8px 8px 0 0; }
            .logo { font-size: 36px; font-weight: bold; letter-spacing: 2px; }
            .confirmed { font-size: 48px; color: #2E8B57; text-align: center; margin: 30px 0; font-weight: bold; }
            table { width: 100%; margin: 30px 0; font-size: 18px; }
            td { padding: 12px 0; border-bottom: 1px solid #ddd; }
            .label { font-weight: bold; width: 40%; color: #2E8B57; }
            .barcode { text-align: center; margin: 40px 0; font-size: 42px; font-weight: bold; color: #2E8B57; letter-spacing: 5px; }
            .footer { margin-top: 60px; text-align: center; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="voucher">
            <div class="header">
                <div class="logo">MALARUHATAN COUNTRY CLUB</div>
                <h2>OFFICIAL RESERVATION VOUCHER</h2>
            </div>
            
            <div class="confirmed">✓ CONFIRMED</div>
            
            <table>
                <tr><td class="label">Reference No:</td><td><strong>' . $reservation_data['reservation_ref'] . '</strong></td></tr>
                <tr><td class="label">Guest Name:</td><td>' . htmlspecialchars($reservation_data['full_name']) . '</td></tr>
                <tr><td class="label">Room:</td><td>' . htmlspecialchars($reservation_data['room_type']) . ' - Room ' . $reservation_data['room_number'] . '</td></tr>
                <tr><td class="label">Check-in:</td><td>' . date('F j, Y', strtotime($reservation_data['checkin_date'])) . ' at ' . $reservation_data['arrival_time'] . '</td></tr>
                <tr><td class="label">Check-out:</td><td>' . date('F j, Y', strtotime($reservation_data['checkout_date'])) . '</td></tr>
                <tr><td class="label">Guests:</td><td>' . $reservation_data['adults'] . ' Adult(s), ' . $reservation_data['children'] . ' Child(ren)</td></tr>
                <tr><td class="label">Total Amount:</td><td>₱' . number_format($reservation_data['total_amount'], 2) . '</td></tr>
                <tr><td class="label">Amount Paid:</td><td>₱' . number_format($reservation_data['amount_paid'], 2) . '</td></tr>
                <tr><td class="label">Remaining Balance:</td><td>₱' . number_format($reservation_data['total_amount'] - $reservation_data['amount_paid'], 2) . '</td></tr>
                <tr><td class="label">Confirmed On:</td><td>' . date('F j, Y \a\t g:i A') . '</td></tr>
            </table>
            
            <div class="barcode">' . $reservation_data['reservation_ref'] . '</div>
            
            <p style="text-align:center; font-size:20px;"><strong>Thank you for choosing Malaruhatan Country Club!</strong></p>
            <div class="footer">
                Present this voucher upon check-in • reservations@malaruhatan.com • (02) 1234-5678
            </div>
        </div>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
}
?>