<?php
// MCC\dashboard\event_manager_sidebar\manager_functions.php

// Prevent duplicate function declarations - wrap ALL functions
if (!function_exists('getPendingProposalsCount')) {

/**
 * Get pending proposals count (for sidebar notification)
 */
function getPendingProposalsCount($managerId = null) {
    global $conn;
    
    if ($managerId) {
        // Count pending proposals that are either unassigned OR assigned to this manager
        $query = "SELECT COUNT(*) as count FROM event_proposals 
                  WHERE status = 'pending' AND (assigned_manager_id IS NULL OR assigned_manager_id = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $managerId);
    } else {
        $query = "SELECT COUNT(*) as count FROM event_proposals WHERE status = 'pending'";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] ?? 0;
}

/**
 * Get proposal statistics for a manager
 */
function getProposalStats($managerId = null) {
    global $conn;
    
    if ($managerId) {
        // Get stats for both assigned proposals AND available pending proposals
        $query = "SELECT status, COUNT(*) as count FROM event_proposals 
                  WHERE assigned_manager_id = ? OR (status = 'pending' AND assigned_manager_id IS NULL)
                  GROUP BY status";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $managerId);
    } else {
        // Get stats for all proposals (for admin view)
        $query = "SELECT status, COUNT(*) as count FROM event_proposals GROUP BY status";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [
        'pending' => 0,
        'under_review' => 0,
        'approved' => 0,
        'rejected' => 0,
        'confirmed' => 0,
        'needs_changes' => 0,
        'payment_pending_verification' => 0,
        'balance_pending_verification' => 0,
        'fully_paid' => 0,
        'completed' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        if (isset($stats[$row['status']])) {
            $stats[$row['status']] = $row['count'];
        }
    }
    
    $stmt->close();
    return $stats;
}

/**
 * Get total proposals count for a manager
 */
function getTotalProposalsCount($managerId = null) {
    global $conn;
    
    if ($managerId) {
        // Count both assigned proposals AND available pending proposals
        $query = "SELECT COUNT(*) as count FROM event_proposals 
                  WHERE assigned_manager_id = ? OR (status = 'pending' AND assigned_manager_id IS NULL)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $managerId);
    } else {
        $query = "SELECT COUNT(*) as count FROM event_proposals";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] ?? 0;
}

/**
 * Get available proposals count for a manager
 */
function getAvailableProposalsCount($managerId = null) {
    global $conn;
    
    if ($managerId) {
        // Count pending proposals that are either unassigned OR assigned to this manager
        $query = "SELECT COUNT(*) as count FROM event_proposals 
                  WHERE status = 'pending' AND (assigned_manager_id IS NULL OR assigned_manager_id = ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $managerId);
    } else {
        $query = "SELECT COUNT(*) as count FROM event_proposals WHERE status = 'pending'";
        $stmt = $conn->prepare($query);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] ?? 0;
}

/**
 * Get recent proposals for a manager
 */
function getRecentProposals($limit = 5, $managerId = null) {
    global $conn;
    
    if ($managerId) {
        // Show recent proposals that are either assigned to this manager OR are pending and unassigned
        // Include ALL statuses including rejected
        $query = "SELECT p.*, p.full_name as client_name 
                  FROM event_proposals p 
                  WHERE p.assigned_manager_id = ? OR (p.status = 'pending' AND p.assigned_manager_id IS NULL)
                  ORDER BY p.submitted DESC LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $managerId, $limit);
    } else {
        $query = "SELECT p.*, p.full_name as client_name 
                  FROM event_proposals p 
                  ORDER BY p.submitted DESC LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $proposals = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $proposals;
}

/**
 * Get upcoming events for a manager
 */
function getUpcomingEvents($limit = 5, $managerId = null) {
    global $conn;
    
    if ($managerId) {
        // Show upcoming events assigned to this manager (only confirmed and approved)
        $query = "SELECT p.*, p.full_name as client_name 
                  FROM event_proposals p 
                  WHERE (p.event_date >= CURDATE() OR p.arrival_date >= CURDATE())
                  AND p.status IN ('confirmed', 'approved', 'fully_paid')
                  AND p.assigned_manager_id = ?
                  ORDER BY COALESCE(p.event_date, p.arrival_date) ASC LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $managerId, $limit);
    } else {
        $query = "SELECT p.*, p.full_name as client_name 
                  FROM event_proposals p 
                  WHERE (p.event_date >= CURDATE() OR p.arrival_date >= CURDATE())
                  AND p.status IN ('confirmed', 'approved', 'fully_paid')
                  ORDER BY COALESCE(p.event_date, p.arrival_date) ASC LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $events = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $events;
}

/**
 * Get recent activities (optional - if needed elsewhere)
 */
function getRecentActivities($limit = 5) {
    global $conn;
    $query = "SELECT CONCAT('Proposal #', id, ' - ', event_title, ' - Status: ', status) as description, submitted as created_at 
              FROM event_proposals 
              ORDER BY submitted DESC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $activities;
}

} // End of if (!function_exists('getPendingProposalsCount')) - ALL functions are now protected
?>