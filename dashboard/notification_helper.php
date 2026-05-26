<?php
// notification_helper.php
class NotificationHelper {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Create a new notification
    public function createNotification($user_id, $title, $message, $type = 'system', $related_id = null) {
        $sql = "INSERT INTO notifications (user_id, title, message, type, related_id) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isssi", $user_id, $title, $message, $type, $related_id);
        
        return $stmt->execute();
    }
    
    // Get user notifications
    public function getUserNotifications($user_id, $limit = 20) {
        $sql = "SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        
        return $stmt->get_result();
    }
    
    // Get unread count
    public function getUnreadCount($user_id) {
        $sql = "SELECT COUNT(*) as count FROM notifications 
                WHERE user_id = ? AND status = 'unread'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'];
    }
    
    // Mark as read
    public function markAsRead($notification_id, $user_id) {
        $sql = "UPDATE notifications SET status = 'read' 
                WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);
        
        return $stmt->execute();
    }
    
    // ==================== NOTIFICATION TRIGGERS ====================
    
    // Reservation status changes
    public function notifyReservationStatus($reservation_id, $new_status) {
        // Get reservation details
        $sql = "SELECT user_id, reservation_ref, room_type FROM reservations WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();
        
        if (!$reservation) return false;
        
        $user_id = $reservation['user_id'];
        $ref = $reservation['reservation_ref'];
        $room_type = $reservation['room_type'];
        
        $messages = [
            'confirmed' => [
                'title' => 'Reservation Confirmed!',
                'message' => "Your {$room_type} reservation (#{$ref}) has been confirmed. Your room is now booked!"
            ],
            'cancelled' => [
                'title' => 'Reservation Cancelled',
                'message' => "Your {$room_type} reservation (#{$ref}) has been cancelled."
            ]
        ];
        
        if (isset($messages[$new_status])) {
            return $this->createNotification(
                $user_id,
                $messages[$new_status]['title'],
                $messages[$new_status]['message'],
                'reservation',
                $reservation_id
            );
        }
        
        return false;
    }
    
    // Payment status changes
    public function notifyPaymentStatus($reservation_ref, $status, $amount) {
        // Get reservation details
        $sql = "SELECT r.user_id, r.room_type, r.reservation_ref 
                FROM reservations r 
                WHERE r.reservation_ref = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $reservation_ref);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();
        
        if (!$reservation) return false;
        
        $user_id = $reservation['user_id'];
        $room_type = $reservation['room_type'];
        $ref = $reservation['reservation_ref'];
        
        $messages = [
            'paid' => [
                'title' => 'Payment Received!',
                'message' => "Payment of ₱{$amount} for your {$room_type} reservation (#{$ref}) has been confirmed."
            ],
            'failed' => [
                'title' => 'Payment Failed',
                'message' => "Payment for your {$room_type} reservation (#{$ref}) failed. Please try again."
            ]
        ];
        
        if (isset($messages[$status])) {
            return $this->createNotification(
                $user_id,
                $messages[$status]['title'],
                $messages[$status]['message'],
                'reservation',
                null
            );
        }
        
        return false;
    }
    
    // Facility booking status
    public function notifyFacilityBookingStatus($booking_id, $new_status) {
        $sql = "SELECT user_id, facility_name, facility_type FROM facility_bookings WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        
        if (!$booking) return false;
        
        $user_id = $booking['user_id'];
        $facility_name = $booking['facility_name'];
        $facility_type = $booking['facility_type'];
        
        $messages = [
            'confirmed' => [
                'title' => 'Facility Booking Confirmed!',
                'message' => "Your {$facility_name} booking has been confirmed and is now scheduled."
            ],
            'cancelled' => [
                'title' => 'Facility Booking Cancelled',
                'message' => "Your {$facility_name} booking has been cancelled."
            ]
        ];
        
        if (isset($messages[$new_status])) {
            return $this->createNotification(
                $user_id,
                $messages[$new_status]['title'],
                $messages[$new_status]['message'],
                'facility',
                $booking_id
            );
        }
        
        return false;
    }
    
    // Event proposal status
    public function notifyEventProposalStatus($proposal_id, $new_status) {
        $sql = "SELECT user_id, event_title, proposal_id FROM event_proposals WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $proposal = $stmt->get_result()->fetch_assoc();
        
        if (!$proposal) return false;
        
        $user_id = $proposal['user_id'];
        $event_title = $proposal['event_title'];
        $ref = $proposal['proposal_id'];
        
        $messages = [
            'approved' => [
                'title' => 'Event Proposal Approved!',
                'message' => "Your event '{$event_title}' (#{$ref}) has been approved by management."
            ],
            'rejected' => [
                'title' => 'Event Proposal Update',
                'message' => "Your event '{$event_title}' (#{$ref}) requires some changes. Please check the manager's feedback."
            ],
            'confirmed' => [
                'title' => 'Event Confirmed!',
                'message' => "Your event '{$event_title}' (#{$ref}) is now confirmed and scheduled!"
            ]
        ];
        
        if (isset($messages[$new_status])) {
            return $this->createNotification(
                $user_id,
                $messages[$new_status]['title'],
                $messages[$new_status]['message'],
                'event',
                $proposal_id
            );
        }
        
        return false;
    }
    
    // New booking created
    public function notifyNewBooking($user_id, $type, $item_name, $reference) {
        $titles = [
            'reservation' => 'New Room Reservation',
            'facility' => 'New Facility Booking',
            'event' => 'New Event Proposal'
        ];
        
        $messages = [
            'reservation' => "Your {$item_name} reservation (#{$reference}) has been received and is pending confirmation.",
            'facility' => "Your {$item_name} booking has been received and is pending confirmation.",
            'event' => "Your event '{$item_name}' (#{$reference}) has been submitted for review."
        ];
        
        if (isset($titles[$type]) && isset($messages[$type])) {
            return $this->createNotification(
                $user_id,
                $titles[$type],
                $messages[$type],
                $type,
                null
            );
        }
        
        return false;
    }
}
?>