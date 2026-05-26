<?php
// MCC/admin_dashboards/api/email_function.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// ==================== EMAIL CONFIGURATION ====================
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'nhelhernandez679@gmail.com', // Your Gmail
    'smtp_password' => 'exjenlyhzhklxnzt',     // Your Gmail App Password
    'from_email' => 'nhelhernandez679@gmail.com',
    'from_name' => 'Malaruhatan Country Club',
    'reply_to' => 'info@malaruhatan.com'
];
// ==================== END CONFIGURATION ====================

// FIX: Better error handling for PHPMailer autoload
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    error_log("PHPMailer autoload not found at: " . $vendorPath);
}

// ... rest of your functions remain exactly the same ...
function sendReservationEmail($guest_email, $guest_name, $reservation_id, $checkin_date, $checkout_date, $room_type, $total_amount, $guests, $payment_method) {
    global $emailConfig;
    
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $guest_email");
        return false;
    }
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer class not found");
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp_username'];
        $mail->Password   = $emailConfig['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp_port'];
        $mail->SMTPDebug = 0;
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($guest_email, $guest_name);
        $mail->addReplyTo($emailConfig['reply_to'], $emailConfig['from_name']);
        
        // Generate PDF
        $pdfContent = generateReservationPDF(
            $guest_name, 
            $reservation_id, 
            $checkin_date, 
            $checkout_date, 
            $room_type, 
            $total_amount, 
            $guests, 
            $payment_method
        );
        
        // Add PDF attachment
        $mail->addStringAttachment($pdfContent, "Reservation_Voucher_$reservation_id.pdf", 'base64', 'application/pdf');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Reservation Confirmation #$reservation_id - Malaruhatan Country Club";
        
        $email_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2E8B57; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
                .details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { background: #f0f0f0; padding: 15px; text-align: center; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                .voucher-notice { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2E8B57; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Malaruhatan Country Club</h1>
                <h2>Reservation Confirmed! 🎉</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$guest_name</strong>,</p>
                <p>Thank you for choosing Malaruhatan Country Club! Your reservation has been confirmed.</p>
                
                <div class='voucher-notice'>
                    <strong>📎 Your official voucher is attached!</strong><br>
                    Please present the PDF voucher upon arrival.
                </div>
                
                <div class='details'>
                    <h3>Reservation Summary</h3>
                    <table>
                        <tr><td><strong>Reservation ID:</strong></td><td><strong>#$reservation_id</strong></td></tr>
                        <tr><td><strong>Room Type:</strong></td><td>$room_type</td></tr>
                        <tr><td><strong>Check-in:</strong></td><td>$checkin_date</td></tr>
                        <tr><td><strong>Check-out:</strong></td><td>$checkout_date</td></tr>
                        <tr><td><strong>Guests:</strong></td><td>$guests</td></tr>
                        <tr><td><strong>Total Amount:</strong></td><td>₱" . number_format($total_amount, 2) . "</td></tr>
                        <tr><td><strong>Payment Method:</strong></td><td>$payment_method</td></tr>
                    </table>
                </div>
                
                <p>We look forward to welcoming you to Malaruhatan Country Club!</p>
            </div>
            <div class='footer'>
                <p><strong>Malaruhatan Country Club</strong><br>
                Phone: (123) 456-7890 | Email: info@malaruhatan.com</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $email_body;
        
        // Plain text version
        $mail->AltBody = "RESERVATION CONFIRMATION #$reservation_id\n\nDear $guest_name,\n\nYour reservation at Malaruhatan Country Club has been confirmed!\n\nReservation ID: #$reservation_id\nRoom Type: $room_type\nCheck-in: $checkin_date\nCheck-out: $checkout_date\nGuests: $guests\nTotal Amount: ₱" . number_format($total_amount, 2) . "\nPayment Method: $payment_method\n\nYour official voucher is attached as a PDF. Please present it upon arrival.\n\nThank you for your reservation!";
        
        $mail->send();
        error_log("Reservation email with PDF sent successfully to: $guest_email");
        return true;
    } catch (Exception $e) {
        error_log("Reservation email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function sendFacilityBookingEmail($guest_email, $guest_name, $booking_id, $facility_name, $booking_date, $booking_time, $total_amount, $duration, $payment_method) {
    global $emailConfig;
    
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $guest_email");
        return false;
    }
    
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer class not found");
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $emailConfig['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['smtp_username'];
        $mail->Password   = $emailConfig['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $emailConfig['smtp_port'];
        $mail->SMTPDebug = 0;
        
        // Recipients
        $mail->setFrom($emailConfig['from_email'], $emailConfig['from_name']);
        $mail->addAddress($guest_email, $guest_name);
        $mail->addReplyTo($emailConfig['reply_to'], $emailConfig['from_name']);
        
        // Generate PDF for facility booking
        $pdfContent = generateFacilityPDF(
            $guest_name,
            $booking_id,
            $facility_name,
            $booking_date,
            $booking_time,
            $total_amount,
            $duration,
            $payment_method
        );
        
        // Add PDF attachment
        $mail->addStringAttachment($pdfContent, "Facility_Voucher_$booking_id.pdf", 'base64', 'application/pdf');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Facility Booking Confirmation #$booking_id - Malaruhatan Country Club";
        
        $email_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2E8B57; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
                .details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { background: #f0f0f0; padding: 15px; text-align: center; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 8px; border-bottom: 1px solid #ddd; }
                .voucher-notice { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2E8B57; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Malaruhatan Country Club</h1>
                <h2>Facility Booking Confirmed! 🎉</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$guest_name</strong>,</p>
                <p>Thank you for booking with Malaruhatan Country Club! Your facility booking has been confirmed.</p>
                
                <div class='voucher-notice'>
                    <strong>📎 Your official voucher is attached!</strong><br>
                    Please present the PDF voucher when you arrive at the facility.
                </div>
                
                <div class='details'>
                    <h3>Booking Summary</h3>
                    <table>
                        <tr><td><strong>Booking ID:</strong></td><td><strong>#$booking_id</strong></td></tr>
                        <tr><td><strong>Facility:</strong></td><td>$facility_name</td></tr>
                        <tr><td><strong>Date:</strong></td><td>$booking_date</td></tr>
                        <tr><td><strong>Time:</strong></td><td>$booking_time</td></tr>
                        <tr><td><strong>Duration:</strong></td><td>$duration</td></tr>
                        <tr><td><strong>Total Amount:</strong></td><td>₱" . number_format($total_amount, 2) . "</td></tr>
                        <tr><td><strong>Payment Method:</strong></td><td>$payment_method</td></tr>
                    </table>
                </div>
                
                <p>We look forward to seeing you at our facilities!</p>
            </div>
            <div class='footer'>
                <p><strong>Malaruhatan Country Club</strong><br>
                Phone: (123) 456-7890 | Email: info@malaruhatan.com</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $email_body;
        
        // Plain text version
        $mail->AltBody = "FACILITY BOOKING CONFIRMATION #$booking_id\n\nDear $guest_name,\n\nYour facility booking at Malaruhatan Country Club has been confirmed!\n\nBooking ID: #$booking_id\nFacility: $facility_name\nDate: $booking_date\nTime: $booking_time\nDuration: $duration\nTotal Amount: ₱" . number_format($total_amount, 2) . "\nPayment Method: $payment_method\n\nYour official voucher is attached as a PDF. Please present it when you arrive.\n\nThank you for your booking!";
        
        $mail->send();
        error_log("Facility booking email with PDF sent successfully to: $guest_email");
        return true;
    } catch (Exception $e) {
        error_log("Facility booking email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function generateReservationPDF($guest_name, $reservation_id, $checkin_date, $checkout_date, $room_type, $total_amount, $guests, $payment_method) {
    // Create PDF options
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    
    // Create HTML content for PDF
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { background: #2E8B57; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; border: 2px solid #2E8B57; border-top: none; border-radius: 0 0 10px 10px; }
            .details { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .footer { background: #f0f0f0; padding: 20px; text-align: center; margin-top: 30px; border-radius: 8px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            td { padding: 12px; border-bottom: 1px solid #ddd; }
            .voucher-code { background: #2E8B57; color: white; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; border-radius: 8px; margin: 20px 0; }
            .logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='logo'>MCC</div>
            <h1>Malaruhatan Country Club</h1>
            <h2>Official Reservation Voucher</h2>
        </div>
        
        <div class='content'>
            <div class='voucher-code'>
                VOUCHER: #$reservation_id
            </div>
            
            <p>Dear <strong>$guest_name</strong>,</p>
            <p>Thank you for choosing Malaruhatan Country Club! Please present this voucher upon arrival.</p>
            
            <div class='details'>
                <h3>Reservation Details</h3>
                <table>
                    <tr><td><strong>Guest Name:</strong></td><td>$guest_name</td></tr>
                    <tr><td><strong>Reservation ID:</strong></td><td><strong>#$reservation_id</strong></td></tr>
                    <tr><td><strong>Room Type:</strong></td><td>$room_type</td></tr>
                    <tr><td><strong>Check-in Date:</strong></td><td>$checkin_date</td></tr>
                    <tr><td><strong>Check-out Date:</strong></td><td>$checkout_date</td></tr>
                    <tr><td><strong>Guests:</strong></td><td>$guests</td></tr>
                    <tr><td><strong>Total Amount Paid:</strong></td><td>₱" . number_format($total_amount, 2) . "</td></tr>
                    <tr><td><strong>Payment Method:</strong></td><td>$payment_method</td></tr>
                    <tr><td><strong>Status:</strong></td><td><strong style='color: #2E8B57;'>CONFIRMED</strong></td></tr>
                </table>
            </div>
            
            <div class='details'>
                <h3>Check-in Instructions</h3>
                <ul>
                    <li>Please bring a valid government-issued ID</li>
                    <li>Check-in time: 2:00 PM</li>
                    <li>Check-out time: 12:00 PM</li>
                    <li>Early check-in and late check-out subject to availability</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p><strong>Malaruhatan Country Club</strong><br>
                Contact: (123) 456-7890 | Email: info@malaruhatan.com<br>
                Generated on: " . date('F j, Y \a\t g:i A') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    return $dompdf->output();
}

function generateFacilityPDF($guest_name, $booking_id, $facility_name, $booking_date, $booking_time, $total_amount, $duration, $payment_method) {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    $options->set('isRemoteEnabled', true);
    
    $dompdf = new Dompdf($options);
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { background: #2E8B57; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; border: 2px solid #2E8B57; border-top: none; border-radius: 0 0 10px 10px; }
            .details { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .footer { background: #f0f0f0; padding: 20px; text-align: center; margin-top: 30px; border-radius: 8px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            td { padding: 12px; border-bottom: 1px solid #ddd; }
            .voucher-code { background: #2E8B57; color: white; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; border-radius: 8px; margin: 20px 0; }
            .logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <div class='logo'>MCC</div>
            <h1>Malaruhatan Country Club</h1>
            <h2>Facility Booking Voucher</h2>
        </div>
        
        <div class='content'>
            <div class='voucher-code'>
                VOUCHER: #$booking_id
            </div>
            
            <p>Dear <strong>$guest_name</strong>,</p>
            <p>Thank you for booking with Malaruhatan Country Club! Please present this voucher upon arrival.</p>
            
            <div class='details'>
                <h3>Booking Details</h3>
                <table>
                    <tr><td><strong>Guest Name:</strong></td><td>$guest_name</td></tr>
                    <tr><td><strong>Booking ID:</strong></td><td><strong>#$booking_id</strong></td></tr>
                    <tr><td><strong>Facility:</strong></td><td>$facility_name</td></tr>
                    <tr><td><strong>Booking Date:</strong></td><td>$booking_date</td></tr>
                    <tr><td><strong>Booking Time:</strong></td><td>$booking_time</td></tr>
                    <tr><td><strong>Duration:</strong></td><td>$duration</td></tr>
                    <tr><td><strong>Total Amount Paid:</strong></td><td>₱" . number_format($total_amount, 2) . "</td></tr>
                    <tr><td><strong>Payment Method:</strong></td><td>$payment_method</td></tr>
                    <tr><td><strong>Status:</strong></td><td><strong style='color: #2E8B57;'>CONFIRMED</strong></td></tr>
                </table>
            </div>
            
            <div class='details'>
                <h3>Facility Usage Guidelines</h3>
                <ul>
                    <li>Please arrive 15 minutes before your scheduled time</li>
                    <li>Bring proper athletic attire for sports facilities</li>
                    <li>Follow all facility rules and staff instructions</li>
                    <li>Cancellations must be made 24 hours in advance</li>
                </ul>
            </div>
            
            <div class='footer'>
                <p><strong>Malaruhatan Country Club</strong><br>
                Contact: (123) 456-7890 | Email: info@malaruhatan.com<br>
                Generated on: " . date('F j, Y \a\t g:i A') . "</p>
            </div>
        </div>
    </body>
    </html>";
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    return $dompdf->output();
}
?>