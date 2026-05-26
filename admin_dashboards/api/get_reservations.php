<?php
// MCC/admin_dashboards/api/get_reservations.php

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';

$all = [];

try {
    // ====== FACILITY BOOKINGS ======
    $fac = $conn->query("
        SELECT 
            fb.booking_id AS id,
            fb.payment_reference,
            fb.payment_reference AS reservation_ref,
            fb.facility_name AS facility,
            fb.facility_type,
            fb.booking_date,
            DATE_FORMAT(fb.booking_date, '%b %e, %Y') AS date,
            DATE_FORMAT(fb.booking_time, '%l:%i %p') AS time,
            fb.hours,
            fb.guest_count AS guests,
            fb.status,
            fb.total_amount AS total,
            fb.payment_type,
            fb.payment_type AS payment_method,  -- Use payment_type as payment_method
            u.full_name AS user_name,
            u.email AS user_email,
            'facility' AS type
        FROM facility_bookings fb
        LEFT JOIN users u ON fb.user_id = u.id
        ORDER BY fb.created_at DESC
        LIMIT 100
    ");

    if (!$fac) throw new Exception("Facility query failed: " . $conn->error);

    while ($row = $fac->fetch_assoc()) {
        $row['total'] = '₱' . number_format($row['total'], 2);
        $row['guests'] = $row['guests'] ?: ($row['hours'] . ' hrs');
        $all[] = $row;
    }

    // ====== ROOM RESERVATIONS ======
    $rooms = $conn->query("
        SELECT 
            r.id,
            r.reservation_ref AS payment_reference,
            r.reservation_ref,
            CONCAT(r.room_type, ' Room') AS facility,
            DATE_FORMAT(r.checkin_date, '%b %e, %Y') AS date,
            DATE_FORMAT(r.checkout_date, '%b %e, %Y') AS end_date,
            NULL AS time,
            (COALESCE(r.adults, 0) + COALESCE(r.children, 0)) AS guests,
            r.status,
            r.total_amount AS total,
            r.payment_method AS payment_type,  -- Use payment_method as payment_type
            r.payment_method,
            COALESCE(r.full_name, 'Walk-in') AS user_name,
            COALESCE(r.email, '') AS user_email,
            'room' AS type
        FROM reservations r
        ORDER BY r.created_at DESC
        LIMIT 100
    ");

    if (!$rooms) throw new Exception("Room query failed: " . $conn->error);

    while ($row = $rooms->fetch_assoc()) {
        $row['total'] = '₱' . number_format($row['total'], 2);
        $row['guests'] = $row['guests'] . ' guests';
        $all[] = $row;
    }

    // Sort and calculate stats
    usort($all, function($a, $b) {
        $dateA = strtotime($a['booking_date'] ?? $a['date']);
        $dateB = strtotime($b['booking_date'] ?? $b['date']);
        return $dateB - $dateA;
    });

    $stats = ['pending' => 0, 'pending_payment' => 0, 'confirmed' => 0, 'cancelled' => 0, 'revenue' => 0];

    foreach ($all as $r) {
        if ($r['status'] === 'pending') $stats['pending']++;
        if ($r['status'] === 'pending_payment') $stats['pending_payment']++;
        if ($r['status'] === 'confirmed') {
            $stats['confirmed']++;
            $amount = str_replace(['₱', ','], '', $r['total']);
            $stats['revenue'] += floatval($amount);
        }
        if ($r['status'] === 'cancelled') $stats['cancelled']++;
    }

    $stats['revenue'] = number_format($stats['revenue'], 2);

    echo json_encode([
        'success' => true,
        'reservations' => $all,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>