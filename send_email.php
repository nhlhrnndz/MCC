<?php
// send_email.php (STANDALONE VERSION)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ==================== EMAIL CONFIGURATION ====================
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'nhelhernandez679@gmail.com',
    'smtp_password' => 'exjenlyhzhklxnzt', // Your Gmail App Password
    'from_email' => 'nhelhernandez679@gmail.com',
    'from_name' => 'Malaruhatan Country Club',
    'reply_to' => 'info@malaruhatan.com'
];
// ==================== END CONFIGURATION ====================

// Include PHPMailer manually
require_once 'vendor/autoload.php'; // Adjust this path if needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $full_name = filter_var(trim($_POST['full_name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $subject = filter_var(trim($_POST['subject']), FILTER_SANITIZE_STRING);
    $message = filter_var(trim($_POST['message']), FILTER_SANITIZE_STRING);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: contact.php?status=validation_error");
        exit;
    }
    
    // Validate required fields
    if (empty($full_name) || empty($subject) || empty($message)) {
        header("Location: contact.php?status=validation_error");
        exit;
    }
    
    // Send email
    if (sendContactFormEmail($full_name, $email, $subject, $message, $emailConfig)) {
        header("Location: contact.php?status=success");
        exit;
    } else {
        header("Location: contact.php?status=error");
        exit;
    }
} else {
    header("Location: contact.php");
    exit;
}

function sendContactFormEmail($full_name, $guest_email, $subject, $message, $emailConfig) {
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
        $mail->addAddress($emailConfig['from_email']); // Send to your club email
        $mail->addReplyTo($guest_email, $full_name); // Reply to the person who filled the form
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Contact Form: " . $subject;
        
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
                .label { font-weight: bold; color: #2E8B57; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Malaruhatan Country Club</h1>
                <h2>New Contact Form Message</h2>
            </div>
            <div class='content'>
                <p>You have received a new message through the website contact form.</p>
                
                <div class='details'>
                    <h3>Contact Details</h3>
                    <table>
                        <tr>
                            <td class='label'>Name:</td>
                            <td>$full_name</td>
                        </tr>
                        <tr>
                            <td class='label'>Email:</td>
                            <td><a href='mailto:$guest_email'>$guest_email</a></td>
                        </tr>
                        <tr>
                            <td class='label'>Subject:</td>
                            <td>$subject</td>
                        </tr>
                        <tr>
                            <td class='label'>Date:</td>
                            <td>" . date('F j, Y \a\t g:i A') . "</td>
                        </tr>
                    </table>
                </div>
                
                <div class='details'>
                    <h3>Message</h3>
                    <p>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
            </div>
            <div class='footer'>
                <p><strong>Malaruhatan Country Club Website</strong><br>
                This email was sent automatically from your website contact form.</p>
            </div>
        </body>
        </html>";
        
        $mail->Body = $email_body;
        
        // Plain text version
        $mail->AltBody = "NEW CONTACT FORM MESSAGE\n\n
        Name: $full_name\n
        Email: $guest_email\n
        Subject: $subject\n
        Date: " . date('F j, Y \a\t g:i A') . "\n\n
        Message:\n$message\n\n
        ---\nMalaruhatan Country Club Website";
        
        $mail->send();
        error_log("Contact form email sent successfully from: $guest_email");
        return true;
        
    } catch (Exception $e) {
        error_log("Contact form email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>