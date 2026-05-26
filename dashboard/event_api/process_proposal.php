<?php
require_once 'config/database.php';
require_once 'models/EventProposal.php';
require_once 'helpers/SessionManager.php';

SessionManager::startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new DatabaseConfig();
    $db = $database->getConnection();
    
    if ($db) {
        $eventProposal = new EventProposal($db);
        
        // Populate from POST data
        $user_data = SessionManager::getUserData();
        
        $eventProposal->user_id = $user_data['user_id'];
        $eventProposal->full_name = $user_data['full_name'];
        $eventProposal->email = $user_data['email'];
        $eventProposal->contact_number = $_POST['contactNumber'];
        $eventProposal->event_title = $_POST['eventTitle'];
        $eventProposal->event_type = ($_POST['eventType'] === 'Other' && !empty($_POST['otherEventType'])) ? $_POST['otherEventType'] : $_POST['eventType'];
        $eventProposal->arrival_date = $_POST['arrivalDate'];
        $eventProposal->arrival_time = $_POST['arrivalTime'];
        $eventProposal->venue_preference = $_POST['venuePreference'];
        $eventProposal->expected_guests = (int)$_POST['expectedGuests'];
        $eventProposal->theme = ($_POST['theme'] === 'Other' && !empty($_POST['otherTheme'])) ? $_POST['otherTheme'] : $_POST['theme'];
        $eventProposal->description = $_POST['description'] ?? '';
        $eventProposal->catering_request = $_POST['cateringRequest'] ?? 'no';
        $eventProposal->catering_details = $_POST['cateringDetails'] ?? '';
        $eventProposal->decorations = $_POST['decorations'] ?? '[]';
        $eventProposal->custom_decorations = $_POST['customDecorations'] ?? '';
        $eventProposal->equipment_needed = $_POST['equipmentNeeded'] ?? '';
        $eventProposal->special_requests = $_POST['specialRequests'] ?? '';
        $eventProposal->addon_aircon = isset($_POST['addon_aircon']) ? 1 : 0;
        $eventProposal->addon_corkage = isset($_POST['addon_corkage']) ? 1 : 0;
        $eventProposal->estimated_budget = !empty($_POST['estimatedBudget']) ? (float)$_POST['estimatedBudget'] : null;
        $eventProposal->payment_method = $_POST['paymentMethod'];
        
        $proposal_id = $eventProposal->create();
        
        if ($proposal_id) {
            echo json_encode([
                'success' => true,
                'message' => 'Event proposal submitted successfully',
                'proposal_id' => $proposal_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create event proposal'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>