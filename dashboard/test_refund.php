<?php
session_start();
require_once '../db_connect.php';

echo "<h3>All Reservations (Current User)</h3>";

// Check your session user_id
$user_id = $_SESSION['user_id'] ?? 0;
echo "Your User ID: " . $user_id . "<br><br>";

// Show ALL your reservations
$sql = "SELECT 
    id,
    reservation_ref,
    status,
    amount_paid,
    refund_status,
    DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as created,
    DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i') as updated
    FROM reservations 
    WHERE user_id = $user_id
    ORDER BY created_at DESC";
    
$result = $conn->query($sql);

echo "<table border='1' cellpadding='5'>";
echo "<tr>
    <th>ID</th>
    <th>Ref</th>
    <th>Status</th>
    <th>Amount Paid</th>
    <th>Refund Status</th>
    <th>Created</th>
</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['reservation_ref'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td>₱" . number_format($row['amount_paid'], 2) . "</td>";
    echo "<td>" . $row['refund_status'] . "</td>";
    echo "<td>" . $row['created'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>Facility Bookings</h3>";

$sql2 = "SELECT 
    booking_id,
    facility_name,
    status,
    total_amount as amount_paid,
    refund_status,
    DATE_FORMAT(booking_date, '%Y-%m-%d %H:%i') as booking_date
    FROM facility_bookings 
    WHERE user_id = $user_id
    ORDER BY booking_date DESC";
    
$result2 = $conn->query($sql2);

echo "<table border='1' cellpadding='5'>";
echo "<tr>
    <th>ID</th>
    <th>Facility</th>
    <th>Status</th>
    <th>Amount Paid</th>
    <th>Refund Status</th>
    <th>Booking Date</th>
</tr>";

while ($row = $result2->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['booking_id'] . "</td>";
    echo "<td>" . $row['facility_name'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td>₱" . number_format($row['amount_paid'], 2) . "</td>";
    echo "<td>" . $row['refund_status'] . "</td>";
    echo "<td>" . $row['booking_date'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>