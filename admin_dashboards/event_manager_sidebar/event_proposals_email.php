<?php
// event_proposals_email.php - Located in same directory as event_proposals.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// ==================== EMAIL CONFIGURATION ====================
$emailConfig = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'nhelhernandez679@gmail.com',
    'smtp_password' => 'exjenlyhzhklxnzt',
    'from_email' => 'nhelhernandez679@gmail.com',
    'from_name' => 'Malaruhatan Country Club',
    'reply_to' => 'events@malaruhatan.com'
];
// ==================== END CONFIGURATION ====================

// Autoload PHPMailer and DomPDF
$vendorPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require_once $vendorPath;
} else {
    // Fallback: Try to include manually if autoload fails
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Dompdf.php';
    require_once __DIR__ . '/../../vendor/dompdf/dompdf/src/Options.php';
}

/**
 * Send Event Proposal Status Email to Client
 */
function sendProposalStatusEmail($client_email, $client_name, $proposal_id, $event_title, $status, $manager_notes = '', $final_quote = null, $deposit_amount = null, $deposit_due_date = null) {
    global $emailConfig;
    
    if (!filter_var($client_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $client_email");
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
        $mail->addAddress($client_email, $client_name);
        $mail->addReplyTo($emailConfig['reply_to'], $emailConfig['from_name']);
        
        // Subject based on status
        $subject = getProposalEmailSubject($status, $proposal_id);
        $mail->Subject = $subject;
        
        // Generate email body
        $email_body = generateProposalEmailBody($client_name, $proposal_id, $event_title, $status, $manager_notes, $final_quote, $deposit_amount, $deposit_due_date);
        
        $mail->isHTML(true);
        $mail->Body = $email_body;
        $mail->AltBody = generateProposalPlainText($client_name, $proposal_id, $event_title, $status, $manager_notes, $final_quote, $deposit_amount, $deposit_due_date);
        
        // Add PDF for approved proposals
        if ($status === 'approved' && $final_quote) {
            $pdfContent = generateProposalPDF($client_name, $proposal_id, $event_title, $final_quote, $deposit_amount, $deposit_due_date, $manager_notes);
            $mail->addStringAttachment($pdfContent, "Proposal_Approval_$proposal_id.pdf", 'base64', 'application/pdf');
        }
        
        $mail->send();
        error_log("Proposal status email sent to: $client_email - Status: $status");
        return true;
        
    } catch (Exception $e) {
        error_log("Proposal email failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Get email subject based on proposal status
 */
function getProposalEmailSubject($status, $proposal_id) {
    $subjects = [
        'approved' => "🎉 Your Event Proposal #$proposal_id Has Been Approved!",
        'needs_changes' => "📝 Changes Requested for Your Event Proposal #$proposal_id",
        'rejected' => "❌ Update Regarding Your Event Proposal #$proposal_id",
        'under_review' => "🔍 Your Event Proposal #$proposal_id is Under Review",
        'confirmed' => "✅ Event Confirmed! Proposal #$proposal_id",
        'completed' => "🎊 Event Completed Successfully! Proposal #$proposal_id",
        'payment_pending_verification' => "💳 Payment Received - Verification Needed #$proposal_id",
        'balance_pending_verification' => "💳 Balance Payment Received - Verification Needed #$proposal_id",
        'fully_paid' => "💰 Payment Complete! Event #$proposal_id"
    ];
    
    return $subjects[$status] ?? "Update on Your Event Proposal #$proposal_id";
}

/**
 * Generate HTML email body for proposal status
 */
function generateProposalEmailBody($client_name, $proposal_id, $event_title, $status, $manager_notes, $final_quote, $deposit_amount, $deposit_due_date) {
    $status_info = getProposalStatusInfo($status);
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2E8B57; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px; }
            .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin: 10px 0; }
            .details { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .footer { background: #f0f0f0; padding: 15px; text-align: center; border-radius: 5px; margin-top: 20px; }
            .notes { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2E8B57; }
            .financial { background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
            table { width: 100%; border-collapse: collapse; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Malaruhatan Country Club</h1>
            <h2>Event Proposal Update</h2>
        </div>
        
        <div class='content'>
            <p>Dear <strong>$client_name</strong>,</p>
            
            <div class='status-badge' style='background: {$status_info['color']}; color: {$status_info['text_color']};'>
                {$status_info['icon']} {$status_info['title']}
            </div>
            
            <p>{$status_info['message']}</p>
            
            <div class='details'>
                <h3>Proposal Summary</h3>
                <table>
                    <tr><td><strong>Proposal ID:</strong></td><td><strong>#$proposal_id</strong></td></tr>
                    <tr><td><strong>Event Title:</strong></td><td>$event_title</td></tr>
                    <tr><td><strong>Current Status:</strong></td><td>{$status_info['title']}</td></tr>
                </table>
            </div>
            
            " . ($final_quote ? "
            <div class='financial'>
                <h3>Financial Details</h3>
                <table>
                    <tr><td><strong>Final Quote:</strong></td><td>₱" . number_format($final_quote, 2) . "</td></tr>
                    <tr><td><strong>Deposit Required (50%):</strong></td><td>₱" . number_format($deposit_amount, 2) . "</td></tr>
                    <tr><td><strong>Deposit Due Date:</strong></td><td>$deposit_due_date</td></tr>
                </table>
            </div>
            " : "") . "
            
            " . ($manager_notes ? "
            <div class='notes'>
                <h3>Manager's Notes</h3>
                <p>$manager_notes</p>
            </div>
            " : "") . "
            
            <div class='details'>
                <h3>Next Steps</h3>
                <p>{$status_info['next_steps']}</p>
                <p>You can view your proposal details and status anytime by logging into your account.</p>
            </div>
            
            <p>Thank you for considering Malaruhatan Country Club for your event!</p>
        </div>
        
        <div class='footer'>
            <p><strong>Malaruhatan Country Club</strong><br>
            Event Planning Department<br>
            Phone: (123) 456-7890 | Email: events@malaruhatan.com</p>
        </div>
    </body>
    </html>";
}

/**
 * Get status information for email templates
 */
function getProposalStatusInfo($status) {
    $info = [
        'approved' => [
            'title' => 'Proposal Approved!',
            'icon' => '✅',
            'color' => '#d4edda',
            'text_color' => '#155724',
            'message' => 'Great news! Your event proposal has been approved by our team.',
            'next_steps' => 'Please proceed with the deposit payment to confirm your booking. The deposit is 50% of the total amount and is due by the specified date.'
        ],
        'needs_changes' => [
            'title' => 'Changes Requested',
            'icon' => '📝',
            'color' => '#fff3cd',
            'text_color' => '#856404',
            'message' => 'We\'ve reviewed your proposal and have some suggestions for improvement.',
            'next_steps' => 'Please review the manager\'s notes above and submit an updated proposal.'
        ],
        'rejected' => [
            'title' => 'Proposal Not Approved',
            'icon' => '❌',
            'color' => '#f8d7da',
            'text_color' => '#721c24',
            'message' => 'After careful review, we\'re unable to proceed with your event proposal at this time.',
            'next_steps' => 'Please contact our events team if you\'d like to discuss alternative options.'
        ],
        'under_review' => [
            'title' => 'Under Review',
            'icon' => '🔍',
            'color' => '#cce7ff',
            'text_color' => '#004085',
            'message' => 'Your event proposal is currently being reviewed by our team.',
            'next_steps' => 'We will notify you once the review is complete. This typically takes 1-2 business days.'
        ],
        'confirmed' => [
            'title' => 'Event Confirmed!',
            'icon' => '🎉',
            'color' => '#d1ecf1',
            'text_color' => '#0c5460',
            'message' => 'Your deposit has been received and your event is officially confirmed!',
            'next_steps' => 'We\'ll be in touch closer to your event date with final details. The remaining balance is due 7 days before the event.'
        ],
        'completed' => [
            'title' => 'Event Completed Successfully!',
            'icon' => '🏆',
            'color' => '#d4edda',
            'text_color' => '#155724',
            'message' => 'Your event has been successfully completed. Thank you for choosing Malaruhatan Country Club!',
            'next_steps' => 'We hope you had a wonderful experience. We\'d love to hear your feedback!'
        ],
        'payment_pending_verification' => [
            'title' => 'Payment Received - Verification Needed',
            'icon' => '💳',
            'color' => '#fff3cd',
            'text_color' => '#856404',
            'message' => 'We have received your payment proof and it is currently being verified.',
            'next_steps' => 'Our team will verify your payment within 24 hours. You will receive a confirmation email once verified.'
        ],
        'fully_paid' => [
            'title' => 'Payment Complete!',
            'icon' => '💰',
            'color' => '#d4edda',
            'text_color' => '#155724',
            'message' => 'All payments have been received and your event is fully confirmed!',
            'next_steps' => 'We look forward to hosting your event. Our team will contact you for final preparations.'
        ]
    ];
    
    return $info[$status] ?? [
        'title' => 'Status Updated',
        'icon' => '📋',
        'color' => '#e2e3e5',
        'text_color' => '#383d41',
        'message' => 'Your event proposal status has been updated.',
        'next_steps' => 'Please check your account for the latest updates.'
    ];
}

/**
 * Generate plain text version of email
 */
function generateProposalPlainText($client_name, $proposal_id, $event_title, $status, $manager_notes, $final_quote, $deposit_amount, $deposit_due_date) {
    $status_info = getProposalStatusInfo($status);
    
    $text = "EVENT PROPOSAL UPDATE\n\n";
    $text .= "Dear $client_name,\n\n";
    $text .= "{$status_info['icon']} {$status_info['title']}\n\n";
    $text .= "{$status_info['message']}\n\n";
    $text .= "PROPOSAL SUMMARY:\n";
    $text .= "Proposal ID: #$proposal_id\n";
    $text .= "Event Title: $event_title\n";
    $text .= "Status: {$status_info['title']}\n\n";
    
    if ($final_quote) {
        $text .= "FINANCIAL DETAILS:\n";
        $text .= "Final Quote: ₱" . number_format($final_quote, 2) . "\n";
        $text .= "Deposit Required: ₱" . number_format($deposit_amount, 2) . "\n";
        $text .= "Deposit Due: $deposit_due_date\n\n";
    }
    
    if ($manager_notes) {
        $text .= "MANAGER'S NOTES:\n$manager_notes\n\n";
    }
    
    $text .= "NEXT STEPS:\n{$status_info['next_steps']}\n\n";
    $text .= "Thank you for considering Malaruhatan Country Club!\n\n";
    $text .= "Malaruhatan Country Club Events Team\n";
    $text .= "Phone: (123) 456-7890 | Email: events@malaruhatan.com";
    
    return $text;
}

/**
 * Generate PDF for approved proposals
 */
function generateProposalPDF($client_name, $proposal_id, $event_title, $final_quote, $deposit_amount, $deposit_due_date, $manager_notes) {
    $options = new Options();
    $options->set('defaultFont', 'Arial');
    
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
            .approval-stamp { text-align: center; margin: 30px 0; }
            .stamp { display: inline-block; padding: 20px; border: 3px solid #2E8B57; border-radius: 10px; font-size: 24px; font-weight: bold; color: #2E8B57; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Malaruhatan Country Club</h1>
            <h2>Event Proposal Approval</h2>
        </div>
        
        <div class='content'>
            <div class='approval-stamp'>
                <div class='stamp'>APPROVED</div>
            </div>
            
            <p>Dear <strong>$client_name</strong>,</p>
            <p>We are pleased to inform you that your event proposal has been officially approved!</p>
            
            <div class='details'>
                <h3>Approval Details</h3>
                <table>
                    <tr><td><strong>Client Name:</strong></td><td>$client_name</td></tr>
                    <tr><td><strong>Proposal ID:</strong></td><td><strong>#$proposal_id</strong></td></tr>
                    <tr><td><strong>Event Title:</strong></td><td>$event_title</td></tr>
                    <tr><td><strong>Approval Date:</strong></td><td>" . date('F j, Y') . "</td></tr>
                </table>
            </div>
            
            <div class='details'>
                <h3>Financial Summary</h3>
                <table>
                    <tr><td><strong>Final Approved Quote:</strong></td><td>₱" . number_format($final_quote, 2) . "</td></tr>
                    <tr><td><strong>Deposit Required (50%):</strong></td><td>₱" . number_format($deposit_amount, 2) . "</td></tr>
                    <tr><td><strong>Deposit Due Date:</strong></td><td>$deposit_due_date</td></tr>
                    <tr><td><strong>Balance Due:</strong></td><td>₱" . number_format($final_quote - $deposit_amount, 2) . "</td></tr>
                </table>
            </div>
            
            " . ($manager_notes ? "
            <div class='details'>
                <h3>Special Notes</h3>
                <p>$manager_notes</p>
            </div>
            " : "") . "
            
            <div class='details'>
                <h3>Next Steps</h3>
                <ol>
                    <li>Pay the deposit amount by $deposit_due_date to confirm your booking</li>
                    <li>Submit payment proof through your client portal</li>
                    <li>Once deposit is verified, your event will be confirmed</li>
                    <li>Pay the remaining balance 7 days before your event</li>
                </ol>
            </div>
            
            <div class='footer'>
                <p><strong>Malaruhatan Country Club</strong><br>
                Event Planning Department<br>
                Contact: (123) 456-7890 | Email: events@malaruhatan.com<br>
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