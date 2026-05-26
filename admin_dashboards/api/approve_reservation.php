<?php
// MCC/admin_dashboards/api/approve_reservation.php

ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connect.php';

// FIX: Correct the file name and path
$emailFunctionsPath = __DIR__ . '/email_function.php';
if (!file_exists($emailFunctionsPath)) {
    error_log("Email functions file not found at: " . $emailFunctionsPath);
    // Continue without email functionality
} else {
    require_once $emailFunctionsPath;
}

// ADD NOTIFICATION HELPER
$notificationHelperPath = __DIR__ . '/../../dashboard/notification_helper.php';
if (!file_exists($notificationHelperPath)) {
    error_log("Notification helper file not found at: " . $notificationHelperPath);
} else {
    require_once $notificationHelperPath;
}

try {
    $input = file_get_contents('php://input');
    $post = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $id = $post['id'] ?? null;
    $type = $post['type'] ?? null;

    if (!$id || !$type) {
        throw new Exception('Missing required data');
    }

    $conn->autocommit(false);
    $emailSent = false; // Initialize variable
    $notificationSent = false; // Track notification status

    if ($type === 'facility') {
        // Check if facility booking exists and is pending
        $stmt = $conn->prepare("
            SELECT fb.*, u.full_name, u.email, u.contact_number 
            FROM facility_bookings fb 
            JOIN users u ON fb.user_id = u.id 
            WHERE fb.booking_id = ? AND fb.status = 'pending'
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("Facility booking not found or already processed");
        }

        // Update to confirmed
        $updateStmt = $conn->prepare("UPDATE facility_bookings SET status = 'confirmed' WHERE booking_id = ?");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();
        $updateStmt->close();

        $payment_mode = $booking['payment_type'] === 'gcash' ? 'GCash Payment' : 'Approved';

        // ==================== NOTIFICATION TRIGGER ====================
        try {
            if (class_exists('NotificationHelper')) {
                $notificationHelper = new NotificationHelper($conn);
                $notificationSent = $notificationHelper->notifyFacilityBookingStatus($id, 'confirmed');
                error_log("✅ Facility booking notification sent for booking_id: {$id}");
            } else {
                error_log("❌ NotificationHelper class not found");
            }
        } catch (Exception $notificationError) {
            error_log("⚠️ Facility booking notification failed: " . $notificationError->getMessage());
        }
        // ==================== END NOTIFICATION TRIGGER ====================

        // Send facility booking confirmation email
        $duration = $booking['hours'] ? $booking['hours'] . ' hours' : ($booking['guest_count'] ? $booking['guest_count'] . ' guests' : 'N/A');
        
        // FIX: Check if function exists before calling
        if (function_exists('sendFacilityBookingEmail')) {
            $emailSent = sendFacilityBookingEmail(
                $booking['email'],
                $booking['full_name'],
                $booking['booking_id'],
                $booking['facility_name'],
                $booking['booking_date'],
                $booking['booking_time'],
                $booking['total_amount'],
                $duration,
                $payment_mode
            );
        } else {
            error_log("sendFacilityBookingEmail function not found");
        }

    } else {
        // Room booking
        $stmt = $conn->prepare("
            SELECT r.*, u.full_name, u.email, u.contact_number 
            FROM reservations r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.id = ? AND r.status = 'pending'
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new Exception("Room reservation not found or already processed");
        }

        $updateStmt = $conn->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
        $updateStmt->bind_param("i", $id);
        $updateStmt->execute();
        $updateStmt->close();

        $payment_mode = $booking['payment_method'] === 'gcash' ? 'GCash Payment' : 'Approved';

        // ==================== NOTIFICATION TRIGGER ====================
        try {
            if (class_exists('NotificationHelper')) {
                $notificationHelper = new NotificationHelper($conn);
                $notificationSent = $notificationHelper->notifyReservationStatus($id, 'confirmed');
                error_log("✅ Room reservation notification sent for reservation_id: {$id}");
            } else {
                error_log("❌ NotificationHelper class not found");
            }
        } catch (Exception $notificationError) {
            error_log("⚠️ Room reservation notification failed: " . $notificationError->getMessage());
        }
        // ==================== END NOTIFICATION TRIGGER ====================

        // Send room reservation confirmation email
        $guests = $booking['adults'] . ' adults, ' . $booking['children'] . ' children';
        
        // FIX: Check if function exists before calling
        if (function_exists('sendReservationEmail')) {
            $emailSent = sendReservationEmail(
                $booking['email'],
                $booking['full_name'],
                $booking['reservation_ref'],
                $booking['checkin_date'],
                $booking['checkout_date'],
                $booking['room_type'],
                $booking['total_amount'],
                $guests,
                $payment_mode
            );
        } else {
            error_log("sendReservationEmail function not found");
        }
    }

    $conn->commit();

    $response = [
        'success' => true, 
        'message' => 'Booking approved successfully! ' . ($payment_mode === 'GCash Payment' ? 'GCash payment confirmed.' : '')
    ];

    // Add email and notification status to response
    $statusMessages = [];
    
    if ($emailSent) {
        $statusMessages[] = 'Confirmation email sent to customer.';
    } else {
        $statusMessages[] = 'Email notification failed.';
    }
    
    if ($notificationSent) {
        $statusMessages[] = 'In-app notification sent.';
    } else {
        $statusMessages[] = 'In-app notification failed.';
    }

    $response['message'] .= ' ' . implode(' ', $statusMessages);
    $response['email_sent'] = $emailSent;
    $response['notification_sent'] = $notificationSent;

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    $conn->rollback();
    ob_end_clean();
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>