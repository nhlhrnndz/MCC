<?php
require_once 'email_function.php';

$result = sendReservationEmail(
    'diomarvoc163@gmail.com', // Use a real email to test
    'Test User',
    'TEST123',
    '2024-01-01',
    '2024-01-02',
    'Deluxe Room',
    1500.00,
    '2 adults',
    'GCash Payment'
);

if ($result) {
    echo "Email sent successfully!";
} else {
    echo "Email failed - check error logs";
}
?>