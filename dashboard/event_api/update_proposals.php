<?php
// MCC\dashboard\event_api\update_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include your existing db_connect
require_once '../../db_connect.php';

// Direct session handling
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Proposal ID required']);
        exit();
    }
    
    $required_fields = [
        'contactNumber', 'eventTitle', 'eventType', 'arrivalDate', 
        'arrivalTime', 'venuePreference', 'expectedGuests', 'paymentMethod'
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit();
    }
    
    // First, check if proposal exists and belongs to user with editable status
    $check_query = "SELECT id, status FROM event_proposals WHERE id = ? AND user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    
    if (!$check_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $check_stmt->bind_param("ii", $input['id'], $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Proposal not found']);
        exit();
    }
    
    $existing_proposal = $check_result->fetch_assoc();
    
    // Verify proposal is in editable status
    $editable_statuses = ['pending', 'needs_changes'];
    if (!in_array($existing_proposal['status'], $editable_statuses)) {
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Only pending or needs_changes proposals can be edited'
        ]);
        exit();
    }
    
    // Calculate costs
    $costs = calculateCosts($input);
    
    // Determine new status
    $new_status = $existing_proposal['status'] === 'needs_changes' ? 'under_review' : $existing_proposal['status'];
    
    // Build update query
    $query = "UPDATE event_proposals SET 
                contact_number = ?,
                event_title = ?,
                event_type = ?,
                arrival_date = ?,
                arrival_time = ?,
                venue_preference = ?,
                expected_guests = ?,
                theme = ?,
                description = ?,
                catering_request = ?,
                catering_details = ?,
                decorations = ?,
                custom_decorations = ?,
                equipment_needed = ?,
                special_requests = ?,
                addon_aircon = ?,
                addon_corkage = ?,
                estimated_budget = ?,
                payment_method = ?,
                venue_cost = ?,
                catering_cost = ?,
                additional_services_cost = ?,
                total_estimated_cost = ?,
                deposit_amount = ?,
                balance_amount = ?,
                status = ?,
                updated_at = NOW()
              WHERE id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Handle event type
    $event_type = ($input['eventType'] === 'Other' && !empty($input['otherEventType'])) 
        ? $input['otherEventType'] 
        : $input['eventType'];
    
    // Handle theme
    $theme = null;
    if (!empty($input['theme']) && $input['theme'] !== 'Other') {
        $theme = $input['theme'];
    } elseif (!empty($input['otherTheme'])) {
        $theme = $input['otherTheme'];
    }
    
    // Handle decorations
    $decorations = '[]';
    if (!empty($input['decorations'])) {
        $decorations = is_array($input['decorations']) 
            ? json_encode($input['decorations']) 
            : $input['decorations'];
    }
    
    $stmt->bind_param(
        "ssssssissssssiiidssddddddii",
        $input['contactNumber'],
        $input['eventTitle'],
        $event_type,
        $input['arrivalDate'],
        $input['arrivalTime'],
        $input['venuePreference'],
        $input['expectedGuests'],
        $theme,
        $input['description'] ?? null,
        $input['cateringRequest'] ?? 'no',
        $input['cateringDetails'] ?? null,
        $decorations,
        $input['customDecorations'] ?? null,
        $input['equipmentNeeded'] ?? null,
        $input['specialRequests'] ?? null,
        isset($input['addon_aircon']) ? 1 : 0,
        isset($input['addon_corkage']) ? 1 : 0,
        !empty($input['estimatedBudget']) ? (float)$input['estimatedBudget'] : null,
        $input['paymentMethod'],
        $costs['venue_cost'],
        $costs['catering_cost'],
        $costs['additional_services_cost'],
        $costs['total_estimated_cost'],
        $costs['deposit_amount'],
        $costs['balance_amount'],
        $new_status,
        $input['id'],
        $user_id
    );
    
    if ($stmt->execute()) {
        // Get updated proposal data
        $updated_query = "SELECT proposal_id FROM event_proposals WHERE id = ?";
        $updated_stmt = $conn->prepare($updated_query);
        $updated_stmt->bind_param("i", $input['id']);
        $updated_stmt->execute();
        $updated_result = $updated_stmt->get_result();
        $updated_proposal = $updated_result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Proposal updated successfully',
            'proposal_id' => $updated_proposal['proposal_id']
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Update proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update proposal: ' . $e->getMessage()
    ]);
}

function calculateCosts($data) {
    $venueCosts = [
        "Pavillion Old" => ['day' => 8000, 'night' => 12000],
        "Pavillion Anex" => ['day' => 14000, 'night' => 16000],
        "Pavillion Whole" => ['day' => 18000, 'night' => 20000],
        "Mabuhay Lounge" => ['aircon' => 9500, 'non_aircon' => 7500]
    ];

    $costs = [
        'venue_cost' => 0.00,
        'catering_cost' => 0.00,
        'additional_services_cost' => 0.00,
        'total_estimated_cost' => 0.00,
        'deposit_amount' => 0.00,
        'balance_amount' => 0.00
    ];

    // Venue cost
    if (isset($data['venuePreference']) && isset($venueCosts[$data['venuePreference']])) {
        if ($data['venuePreference'] === "Mabuhay Lounge") {
            $costs['venue_cost'] = (isset($data['addon_aircon']) && $data['addon_aircon']) ? 9500 : 7500;
        } else {
            $time = isset($data['arrivalTime']) ? strtotime($data['arrivalTime']) : false;
            $hour = $time ? date('H', $time) : 12;
            $is_night = ($hour >= 18 || $hour < 6);
            $time_key = $is_night ? 'night' : 'day';
            $costs['venue_cost'] = $venueCosts[$data['venuePreference']][$time_key] ?? 0;
        }
    }

    // Catering cost
    if (isset($data['cateringRequest']) && $data['cateringRequest'] === 'yes' && !empty($data['expectedGuests'])) {
        $costs['catering_cost'] = $data['expectedGuests'] * 500;
    }

    // Additional services cost
    if (isset($data['addon_aircon']) && $data['addon_aircon'] && $data['venuePreference'] !== "Mabuhay Lounge") {
        $costs['additional_services_cost'] += 2500;
    }
    if (isset($data['addon_corkage']) && $data['addon_corkage']) {
        $costs['additional_services_cost'] += 5000;
    }

    // Total costs
    $costs['total_estimated_cost'] = $costs['venue_cost'] + $costs['catering_cost'] + $costs['additional_services_cost'];
    $costs['deposit_amount'] = $costs['total_estimated_cost'] * 0.5;
    $costs['balance_amount'] = $costs['total_estimated_cost'] * 0.5;

    return $costs;
}
?>