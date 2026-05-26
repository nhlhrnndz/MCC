<?php
// MCC\dashboard\event_api\create_proposal.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input or form data
$input = json_decode(file_get_contents("php://input"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST;
}

try {
    $user_id = $_SESSION['user_id'];
    $user_data = [
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['admin_name'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'contact_number' => $_SESSION['contact_number'] ?? ''
    ];
    
    // Enhanced session validation
    if (empty($user_data['full_name']) || empty($user_data['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'User profile incomplete. Please update your profile first.'
        ]);
        exit();
    }
    
    // Required fields
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
    
    // Validate contact number
    if (empty($user_data['contact_number']) && empty($input['contactNumber'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Contact number is required'
        ]);
        exit();
    }
    
    // Generate proposal ID
    $proposal_id = "EP" . date('YmdHis') . mt_rand(1000, 9999);
    
    // Calculate costs
    $costs = calculateCosts($input);
    
    // ✅ UPDATED: Match your exact table structure
    $query = "INSERT INTO event_proposals (
        proposal_id, user_id, full_name, email, contact_number, event_title, 
        event_type, arrival_date, arrival_time, venue_preference, expected_guests,
        theme, description, catering_request, catering_details, decorations,
        custom_decorations, equipment_needed, special_requests, addon_aircon,
        addon_corkage, estimated_budget, payment_method, venue_cost, catering_cost,
        additional_services_cost, total_estimated_cost, deposit_amount, balance_amount,
        status, submitted
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
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
    
    // Use contact number from input (preferred) or session
    $contact_number = !empty($input['contactNumber']) ? $input['contactNumber'] : $user_data['contact_number'];
    
    // ✅ UPDATED: Bind parameters to match your table
    $stmt->bind_param(
        "sissssssssissssssiiidssddddddd",
        $proposal_id,
        $user_id,
        $user_data['full_name'],
        $user_data['email'],
        $contact_number,
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
        $costs['balance_amount']
    );
    
    if ($stmt->execute()) {
        // Use direct session for success message
        $_SESSION['success_message'] = 'Proposal submitted successfully! Reference ID: ' . $proposal_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Event proposal submitted successfully',
            'proposal_id' => $proposal_id,
            'costs' => $costs // Optional: return cost breakdown for debugging
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Create proposal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create event proposal: ' . $e->getMessage()
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