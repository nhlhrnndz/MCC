<?php
// event_api/submit_proposal_handler.php - FINAL CORRECTED VERSION
ob_start();
header("Content-Type: application/json; charset=UTF-8");
require_once '../../db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sendJsonResponse($success, $message = '', $data = []) {
    while (ob_get_level()) ob_end_clean();
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if (!empty($data)) $response = array_merge($response, $data);
    echo json_encode($response);
    exit();
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'You must be logged in');
}

// Validate required fields
$required_fields = [
    'contactNumber', 'eventTitle', 'eventType', 'arrivalDate', 
    'arrivalTime', 'venuePreference', 'expectedGuests', 'paymentMethod'
];

$missing_fields = [];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    sendJsonResponse(false, 'Please fill in all required fields: ' . implode(', ', $missing_fields));
}

try {
    // Generate proposal ID
    $proposal_id = "EP" . date('YmdHis') . mt_rand(1000, 9999);
    
    // Calculate costs
    $venue = $_POST['venuePreference'];
    $venue_cost = 0;
    $venueCosts = [
        "Pavillion Old" => ['day' => 8000, 'night' => 12000],
        "Pavillion Anex" => ['day' => 14000, 'night' => 16000],
        "Pavillion Whole" => ['day' => 18000, 'night' => 20000],
        "Mabuhay Lounge" => ['aircon' => 9500, 'non_aircon' => 7500]
    ];
    
    $time = $_POST['eventTime'] ?? 'day';
    if (isset($venueCosts[$venue])) {
        if ($venue === "Mabuhay Lounge") {
            $venue_cost = (isset($_POST['addon_aircon']) && $_POST['addon_aircon']) ? 9500 : 7500;
        } else {
            $venue_cost = $venueCosts[$venue][$time] ?? $venueCosts[$venue]['day'];
        }
    }

    // Calculate additional costs
    $catering_cost = 0;
    if (isset($_POST['cateringRequest']) && $_POST['cateringRequest'] === 'yes') {
        $catering_cost = intval($_POST['expectedGuests']) * 500;
    }

    $additional_services_cost = 0;
    if (isset($_POST['addon_aircon']) && $_POST['addon_aircon'] && $venue !== "Mabuhay Lounge") {
        $additional_services_cost += 2500;
    }
    if (isset($_POST['addon_corkage']) && $_POST['addon_corkage']) {
        $additional_services_cost += 5000;
    }

    $total_estimated_cost = $venue_cost + $catering_cost + $additional_services_cost;
    $deposit_amount = $total_estimated_cost * 0.5;
    $balance_amount = $total_estimated_cost * 0.5;

    // Prepare SQL - EXACTLY MATCHING YOUR TABLE COLUMNS
    $query = "INSERT INTO event_proposals (
        proposal_id, user_id, full_name, email, contact_number, event_title, 
        event_type, arrival_date, arrival_time, venue_preference, expected_guests,
        theme, description, catering_request, catering_details, decorations,
        custom_decorations, equipment_needed, special_requests, addon_aircon, addon_corkage, 
        estimated_budget, payment_method, venue_cost, catering_cost,
        additional_services_cost, total_estimated_cost, deposit_amount, balance_amount,
        status
        -- submitted column is automatically handled by DEFAULT CURRENT_TIMESTAMP
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Prepare all variables with proper defaults
    $user_id = $_SESSION['user_id'];
    $full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
    $email = $_SESSION['email'] ?? 'unknown@example.com';
    
    $event_type = ($_POST['eventType'] === 'Other' && !empty($_POST['otherEventType'])) 
        ? $_POST['otherEventType'] 
        : $_POST['eventType'];

    // Optional fields with defaults
    $theme = '';
    if (!empty($_POST['theme']) && $_POST['theme'] !== 'Other') {
        $theme = $_POST['theme'];
    } elseif (!empty($_POST['otherTheme'])) {
        $theme = $_POST['otherTheme'];
    }

    $description = $_POST['description'] ?? '';
    $catering_request = $_POST['cateringRequest'] ?? 'no';
    $catering_details = $_POST['cateringDetails'] ?? '';
    
    // Handle decorations
    $decorations = '[]';
    if (!empty($_POST['decorations'])) {
        if (is_string($_POST['decorations'])) {
            $decorations = $_POST['decorations'];
        } else {
            $decorations = json_encode($_POST['decorations']);
        }
    }
    
    $custom_decorations = $_POST['customDecorations'] ?? '';
    $equipment_needed = $_POST['equipmentNeeded'] ?? '';
    $special_requests = $_POST['specialRequests'] ?? '';
    $addon_aircon = isset($_POST['addon_aircon']) ? 1 : 0;
    $addon_corkage = isset($_POST['addon_corkage']) ? 1 : 0;
    $estimated_budget = !empty($_POST['estimatedBudget']) ? floatval($_POST['estimatedBudget']) : 0.00;

    // COUNT THE PARAMETERS: We have 29 placeholders in SQL and 29 variables to bind
    // Type definition string should have exactly 29 characters
    $bind_result = $stmt->bind_param(
        "sissssssssisssssssiiidssddddd", // 29 characters = 29 parameters
        $proposal_id,              // 1 - string
        $user_id,                  // 2 - integer
        $full_name,                // 3 - string
        $email,                    // 4 - string
        $_POST['contactNumber'],   // 5 - string
        $_POST['eventTitle'],      // 6 - string
        $event_type,               // 7 - string
        $_POST['arrivalDate'],     // 8 - string
        $_POST['arrivalTime'],     // 9 - string
        $venue,                    // 10 - string
        $_POST['expectedGuests'],  // 11 - integer
        $theme,                    // 12 - string
        $description,              // 13 - string
        $catering_request,         // 14 - string
        $catering_details,         // 15 - string
        $decorations,              // 16 - string
        $custom_decorations,       // 17 - string
        $equipment_needed,         // 18 - string
        $special_requests,         // 19 - string
        $addon_aircon,             // 20 - integer
        $addon_corkage,            // 21 - integer
        $estimated_budget,         // 22 - double
        $_POST['paymentMethod'],   // 23 - string
        $venue_cost,               // 24 - double
        $catering_cost,            // 25 - double
        $additional_services_cost, // 26 - double
        $total_estimated_cost,     // 27 - double
        $deposit_amount,           // 28 - double
        $balance_amount            // 29 - double
        // status is hardcoded as 'pending' in SQL, so no parameter needed
    );

    if (!$bind_result) {
        throw new Exception("Bind failed: " . $stmt->error);
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Proposal submitted successfully! Reference ID: ' . $proposal_id;
        sendJsonResponse(true, 'Event proposal submitted successfully', [
            'proposal_id' => $proposal_id,
            'costs' => [
                'venue_cost' => $venue_cost,
                'catering_cost' => $catering_cost,
                'additional_services_cost' => $additional_services_cost,
                'total_estimated_cost' => $total_estimated_cost,
                'deposit_amount' => $deposit_amount,
                'balance_amount' => $balance_amount
            ]
        ]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

} catch (Exception $e) {
    sendJsonResponse(false, 'Failed to create event proposal: ' . $e->getMessage());
}
?>