<?php
// C:\xampp\htdocs\MCC\admin_dashboards\api\export_reports.php
session_start();
include '../../db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized access');
}

// Get export format
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

try {
    // ====== GET DATA FOR EXPORT ======
    
    // Total metrics
    $total_bookings_stmt = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM reservations WHERE status = 'confirmed') +
            (SELECT COUNT(*) FROM facility_bookings WHERE status = 'confirmed') as total_bookings
    ");
    $total_bookings = $total_bookings_stmt->fetch_assoc()['total_bookings'] ?? 0;

    $avg_booking_stmt = $conn->query("
        SELECT AVG(total_amount) as avg_booking_value FROM (
            SELECT total_amount FROM reservations WHERE status = 'confirmed'
            UNION ALL
            SELECT total_amount FROM facility_bookings WHERE status = 'confirmed'
        ) as combined_bookings
    ");
    $avg_booking_value = $avg_booking_stmt->fetch_assoc()['avg_booking_value'] ?? 0;

    // Monthly revenue data
    $monthly_revenue_stmt = $conn->query("
        SELECT 
            MONTHNAME(STR_TO_DATE(CONCAT('2025-', MONTH(created_at), '-01'), '%Y-%M-%d')) as month_name,
            SUM(total_amount) as revenue
        FROM (
            SELECT created_at, total_amount FROM reservations WHERE status = 'confirmed'
            UNION ALL
            SELECT created_at, total_amount FROM facility_bookings WHERE status = 'confirmed'
        ) as combined_bookings
        WHERE YEAR(created_at) = YEAR(CURDATE())
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at)
    ");

    // Facility bookings data
    $facility_bookings_stmt = $conn->query("
        SELECT 
            facility_name,
            COUNT(*) as bookings,
            SUM(total_amount) as total_revenue
        FROM facility_bookings 
        WHERE status = 'confirmed'
        GROUP BY facility_name
        ORDER BY bookings DESC
    ");

    // Recent reservations
    $recent_reservations_stmt = $conn->query("
        SELECT 
            reservation_ref,
            full_name,
            room_type,
            checkin_date,
            checkout_date,
            total_amount,
            status
        FROM reservations 
        WHERE status = 'confirmed'
        ORDER BY created_at DESC
        LIMIT 50
    ");

    // Recent facility bookings
    $recent_facility_stmt = $conn->query("
        SELECT 
            facility_name,
            booking_date,
            total_amount,
            status
        FROM facility_bookings 
        WHERE status = 'confirmed'
        ORDER BY created_at DESC
        LIMIT 50
    ");

    // Event types data
    $event_types_stmt = $conn->query("
        SELECT 
            event_type,
            COUNT(*) as count,
            ROUND((COUNT(*) / (SELECT COUNT(*) FROM event_proposals WHERE status IN ('confirmed', 'approved'))) * 100, 1) as percentage
        FROM event_proposals 
        WHERE status IN ('confirmed', 'approved') AND event_type IS NOT NULL AND event_type != ''
        GROUP BY event_type
        ORDER BY count DESC
    ");

    // ====== GENERATE CSV CONTENT ======
    $csv_content = "";
    
    // Header
    $csv_content .= "MCC ADMIN REPORTS - " . date('F Y') . "\n";
    $csv_content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Key Metrics
    $csv_content .= "KEY METRICS\n";
    $csv_content .= "Total Bookings," . $total_bookings . "\n";
    $csv_content .= "Average Booking Value,₱" . number_format($avg_booking_value, 2) . "\n";
    $csv_content .= "\n";
    
    // Monthly Revenue
    $csv_content .= "MONTHLY REVENUE " . date('Y') . "\n";
    $csv_content .= "Month,Revenue\n";
    if ($monthly_revenue_stmt) {
        while ($row = $monthly_revenue_stmt->fetch_assoc()) {
            $csv_content .= $row['month_name'] . ",₱" . number_format($row['revenue'], 2) . "\n";
        }
    }
    $csv_content .= "\n";
    
    // Facility Performance
    $csv_content .= "FACILITY PERFORMANCE\n";
    $csv_content .= "Facility Name,Bookings,Total Revenue\n";
    if ($facility_bookings_stmt) {
        while ($row = $facility_bookings_stmt->fetch_assoc()) {
            $csv_content .= $row['facility_name'] . "," . $row['bookings'] . ",₱" . number_format($row['total_revenue'], 2) . "\n";
        }
    }
    $csv_content .= "\n";
    
    // Event Types
    $csv_content .= "EVENT TYPES DISTRIBUTION\n";
    $csv_content .= "Event Type,Percentage,Count\n";
    if ($event_types_stmt) {
        while ($row = $event_types_stmt->fetch_assoc()) {
            $csv_content .= $row['event_type'] . "," . $row['percentage'] . "%," . $row['count'] . "\n";
        }
    }
    $csv_content .= "\n";
    
    // Recent Reservations
    $csv_content .= "RECENT RESERVATIONS (Last 50)\n";
    $csv_content .= "Reference,Name,Room Type,Check-in,Check-out,Amount,Status\n";
    if ($recent_reservations_stmt) {
        while ($row = $recent_reservations_stmt->fetch_assoc()) {
            $csv_content .= '"' . $row['reservation_ref'] . '",' . 
                           '"' . $row['full_name'] . '",' . 
                           '"' . $row['room_type'] . '",' . 
                           $row['checkin_date'] . ',' . 
                           $row['checkout_date'] . ',' . 
                           '₱' . number_format($row['total_amount'], 2) . ',' . 
                           $row['status'] . "\n";
        }
    }
    $csv_content .= "\n";
    
    // Recent Facility Bookings
    $csv_content .= "RECENT FACILITY BOOKINGS (Last 50)\n";
    $csv_content .= "Facility,Booking Date,Amount,Status\n";
    if ($recent_facility_stmt) {
        while ($row = $recent_facility_stmt->fetch_assoc()) {
            $csv_content .= '"' . $row['facility_name'] . '",' . 
                           $row['booking_date'] . ',' . 
                           '₱' . number_format($row['total_amount'], 2) . ',' . 
                           $row['status'] . "\n";
        }
    }

    // ====== SET HEADERS FOR DOWNLOAD ======
    $filename = "mcc_reports_" . date('Y_m_d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV content
    echo $csv_content;
    
} catch (Exception $e) {
    // Log error and return error message
    error_log("Export error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo "Error generating report: " . $e->getMessage();
}
?>