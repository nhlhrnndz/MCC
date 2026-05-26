<?php
// Use SessionManager to handle session
require_once 'event_api/helpers/SessionManager.php';
SessionManager::startSession();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: user_login.php');
    exit();
}

// Check if proposal ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: user_dashboard.php?page=event_proposal');
    exit();
}

$proposal_id = intval($_GET['id']);

// Load the proposal data
require_once 'event_api/config/database.php';
require_once 'event_api/models/EventProposal.php';

$database = new DatabaseConfig();
$db = $database->getConnection();
$eventProposal = new EventProposal($db);

// Get proposal details
$proposal = $eventProposal->getProposalDataById($proposal_id);

// Check if proposal exists and belongs to current user
$user_data = SessionManager::getUserData();
if (!$proposal || $proposal['user_id'] != $user_data['user_id']) {
    header('Location: user_dashboard.php?page=event_proposal');
    exit();
}

// Check if proposal can be edited (both pending and needs_changes proposals can be edited)
$editableStatuses = ['pending', 'needs_changes'];
if (!in_array($proposal['status'], $editableStatuses)) {
    $_SESSION['error_message'] = 'Only pending and needs changes proposals can be edited.';
    header('Location: user_dashboard.php?page=event_proposal');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== EDIT FORM SUBMISSION STARTED ===");
    
    // Set updated values
    $eventProposal->id = $proposal_id;
    $eventProposal->user_id = $user_data['user_id'];
    $eventProposal->contact_number = trim($_POST['contactNumber']);
    $eventProposal->event_title = trim($_POST['eventTitle']);
    $eventProposal->event_type = ($_POST['eventType'] === 'Other' && !empty($_POST['otherEventType'])) 
        ? trim($_POST['otherEventType']) 
        : trim($_POST['eventType']);
    $eventProposal->arrival_date = $_POST['arrivalDate'];
    $eventProposal->arrival_time = $_POST['arrivalTime'];
    $eventProposal->venue_preference = $_POST['venuePreference'];
    $eventProposal->expected_guests = (int)$_POST['expectedGuests'];
    $eventProposal->payment_method = $_POST['paymentMethod'];
    
    // Set optional fields
    if (!empty($_POST['theme']) && $_POST['theme'] !== 'Other') {
        $eventProposal->theme = $_POST['theme'];
    } elseif (!empty($_POST['otherTheme'])) {
        $eventProposal->theme = $_POST['otherTheme'];
    } else {
        $eventProposal->theme = null;
    }
    
    $eventProposal->description = $_POST['description'] ?? null;
    $eventProposal->catering_request = $_POST['cateringRequest'] ?? 'no';
    $eventProposal->catering_details = $_POST['cateringDetails'] ?? null;
    $eventProposal->decorations = $_POST['decorations'] ?? '[]';
    $eventProposal->custom_decorations = $_POST['customDecorations'] ?? null;
    $eventProposal->equipment_needed = $_POST['equipmentNeeded'] ?? null;
    $eventProposal->special_requests = $_POST['specialRequests'] ?? null;
    $eventProposal->addon_aircon = isset($_POST['addon_aircon']) ? 1 : 0;
    $eventProposal->addon_corkage = isset($_POST['addon_corkage']) ? 1 : 0;
    $eventProposal->estimated_budget = !empty($_POST['estimatedBudget']) ? (float)$_POST['estimatedBudget'] : null;
    
    error_log("All fields set, attempting to update proposal...");
    
    // Try to update the proposal
    $result = $eventProposal->update();
    
    if ($result) {
    error_log("✓ PROPOSAL UPDATED SUCCESSFULLY: " . $proposal_id);
    
    // Show appropriate success message based on original status
    if ($proposal['status'] === 'needs_changes') {
        SessionManager::setSuccessMessage('Proposal updated successfully! Your changes have been submitted for review.');
    } else {
        SessionManager::setSuccessMessage('Proposal updated successfully! Your proposal remains pending review.');
    }
    
    header('Location: user_dashboard.php?page=event_proposal');
    exit();
} else {
        $error_message = 'Failed to update event proposal. Please check that all required fields are filled correctly.';
        error_log("✗ PROPOSAL UPDATE FAILED");
    }
    
    if (isset($error_message)) {
        error_log("FINAL ERROR: " . $error_message);
        echo "<script>
                alert('Error: " . addslashes($error_message) . "');
                console.error('Form submission error:', '" . addslashes($error_message) . "');
              </script>";
    }
    
    error_log("=== EDIT FORM SUBMISSION ENDED ===");
    
    // Reload proposal data after submission attempt
    $proposal = $eventProposal->getProposalDataById($proposal_id);
}
?>

<!-- ========================================= -->
<!-- EDIT EVENT PROPOSAL FORM PAGE             -->
<!-- ========================================= -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Event Proposal</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- ========================================= -->
  <!-- EXTERNAL STYLESHEET                       -->
  <!-- ========================================= -->
  <style>
<?php include 'event_form_proposal.css'; ?>
</style>
</head>
<body>

<!-- ========================================= -->
<!-- MAIN CONTAINER: Proposal Form Wrapper     -->
<!-- ========================================= -->
<div class="proposal-container">
  <div class="px-2">

<!-- ========================================= -->
<!-- BACK BUTTON                               -->
<!-- ========================================= -->
<button class="back-to-dashboard" onclick="window.location.href='user_dashboard.php?page=event_proposal'">
  <i class="fas fa-arrow-left me-2"></i> Back to Proposals
</button>

    <!-- ========================================= -->
    <!-- PAGE HEADER                               -->
    <!-- ========================================= -->
    <h2 class="fw-bold mb-2 text-success">Edit Event Proposal</h2>
    <p class="text-muted mb-4">Update your event proposal details</p>

    <!-- ========================================= -->
    <!-- PROGRESS BAR WITH STEP INDICATORS         -->
    <!-- ========================================= -->
    <div class="progress-container mb-4">
      <div class="progress-bar" id="progressBar" style="width: 100%"></div>
      
      <!-- Step Circles -->
      <div class="progress-steps">
        <span class="step-circle completed" data-step="basic">
          <i class="fas fa-check step-check"></i>
        </span>
        <span class="step-circle completed" data-step="details">
          <i class="fas fa-check step-check"></i>
        </span>
        <span class="step-circle completed" data-step="requirements">
          <i class="fas fa-check step-check"></i>
        </span>
        <span class="step-circle completed" data-step="budget">
          <i class="fas fa-check step-check"></i>
        </span>
        <span class="step-circle active" data-step="review">
          <span class="step-number"></span>
        </span>
      </div>
      
      <!-- Step Labels -->
      <div class="progress-labels d-flex justify-content-between text-xs md-text-sm font-medium mt-2">
        <span class="text-center text-success">Basic Info</span>
        <span class="text-center text-success">Event Details</span>
        <span class="text-center text-success">Requirements</span>
        <span class="text-center text-success">Budget</span>
        <span class="text-center text-success">Review & Update</span>
      </div>
    </div>

    <!-- ========================================= -->
    <!-- MAIN CONTENT GRID                         -->
    <!-- ========================================= -->
    <div class="row">
      <!-- ========================================= -->
      <!-- FORM SECTION                              -->
      <!-- ========================================= -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body">
            <!-- Step Title & Description -->
            <h4 class="card-title text-success mb-3">Review & Update Proposal</h4>
            <p class="text-muted mb-4">Update your proposal details and submit changes</p>

            <!-- ========================================= -->
            <!-- EDIT FORM                                 -->
            <!-- ========================================= -->
            <form id="editEventForm" action="" method="POST">
              <!-- Proposal ID Display -->
              <div class="alert alert-info d-flex align-items-center">
                <i class="fas fa-info-circle me-2 fs-5"></i>
                <div>
                  <strong>Proposal ID:</strong> <?php echo htmlspecialchars($proposal['proposal_id']); ?><br>
                  <small>Status: 
                    <span class="badge <?php echo $proposal['status'] === 'needs_changes' ? 'bg-warning' : 'bg-info'; ?>">
                      <?php 
                      echo $proposal['status'] === 'needs_changes' ? 'Needs Changes' : 'Pending Review';
                      ?>
                    </span>
                  </small>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- BASIC INFO SECTION                        -->
              <!-- ========================================= -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h5 class="mb-0"><i class="fas fa-user me-2 text-success"></i>Contact Information</h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Full Name</label>
                      <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($proposal['full_name']); ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label fw-semibold">Email Address</label>
                      <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($proposal['email']); ?>" readonly>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                    <input type="tel" name="contactNumber" class="form-control" 
                           value="<?php echo htmlspecialchars($proposal['contact_number']); ?>"
                           placeholder="e.g., 09171234567" required 
                           pattern="[0-9+]{10,15}" title="Please enter a valid phone number (10-15 digits)">
                  </div>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- EVENT DETAILS SECTION                    -->
              <!-- ========================================= -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h5 class="mb-0"><i class="fas fa-calendar-alt me-2 text-success"></i>Event Details</h5>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
                    <input type="text" name="eventTitle" class="form-control" 
                           value="<?php echo htmlspecialchars($proposal['event_title']); ?>"
                           placeholder="e.g., Maria's Wedding, Company Year-End Party" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Event Type <span class="text-danger">*</span></label>
                    <select name="eventType" class="form-select" id="eventTypeSelect" required>
                      <option value="">Select event type</option>
                      <option value="Wedding" <?php echo $proposal['event_type'] == 'Wedding' ? 'selected' : ''; ?>>Wedding</option>
                      <option value="Birthday" <?php echo $proposal['event_type'] == 'Birthday' ? 'selected' : ''; ?>>Birthday</option>
                      <option value="Corporate" <?php echo $proposal['event_type'] == 'Corporate' ? 'selected' : ''; ?>>Corporate</option>
                      <option value="Anniversary" <?php echo $proposal['event_type'] == 'Anniversary' ? 'selected' : ''; ?>>Anniversary</option>
                      <option value="Reunion" <?php echo $proposal['event_type'] == 'Reunion' ? 'selected' : ''; ?>>Reunion</option>
                      <option value="Graduation" <?php echo $proposal['event_type'] == 'Graduation' ? 'selected' : ''; ?>>Graduation</option>
                      <option value="Other" <?php echo !in_array($proposal['event_type'], ['Wedding', 'Birthday', 'Corporate', 'Anniversary', 'Reunion', 'Graduation']) ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <div id="otherEventTypeContainer" class="mt-2 <?php echo !in_array($proposal['event_type'], ['Wedding', 'Birthday', 'Corporate', 'Anniversary', 'Reunion', 'Graduation']) ? '' : 'd-none'; ?>">
                      <input type="text" name="otherEventType" class="form-control" 
                             placeholder="Please specify event type" 
                             value="<?php echo !in_array($proposal['event_type'], ['Wedding', 'Birthday', 'Corporate', 'Anniversary', 'Reunion', 'Graduation']) ? htmlspecialchars($proposal['event_type']) : ''; ?>"
                             id="otherEventTypeInput">
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Arrival Date <span class="text-danger">*</span></label>
                      <input type="date" name="arrivalDate" class="form-control" 
                             value="<?php echo htmlspecialchars($proposal['arrival_date']); ?>" 
                             min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-semibold">Arrival Time <span class="text-danger">*</span></label>
                      <input type="time" name="arrivalTime" class="form-control" 
                             value="<?php echo htmlspecialchars($proposal['arrival_time']); ?>" required>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Venue Preference <span class="text-danger">*</span></label>
                    <select name="venuePreference" class="form-select" required>
                      <option value="">Select venue</option>
                      <option value="Pavillion Old" <?php echo $proposal['venue_preference'] == 'Pavillion Old' ? 'selected' : ''; ?>>Pavillion Old</option>
                      <option value="Pavillion Anex" <?php echo $proposal['venue_preference'] == 'Pavillion Anex' ? 'selected' : ''; ?>>Pavillion Anex</option>
                      <option value="Pavillion Whole" <?php echo $proposal['venue_preference'] == 'Pavillion Whole' ? 'selected' : ''; ?>>Pavillion Whole</option>
                      <option value="Mabuhay Lounge" <?php echo $proposal['venue_preference'] == 'Mabuhay Lounge' ? 'selected' : ''; ?>>Mabuhay Lounge</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Expected Number of Guests <span class="text-danger">*</span></label>
                    <input type="number" name="expectedGuests" class="form-control" 
                           value="<?php echo htmlspecialchars($proposal['expected_guests']); ?>"
                           placeholder="e.g., 150" min="1" max="500" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Theme or Setup Style</label>
                    <select name="theme" class="form-select" id="themeSelect">
                      <option value="">Select theme (optional)</option>
                      <option value="Classic" <?php echo $proposal['theme'] == 'Classic' ? 'selected' : ''; ?>>Classic</option>
                      <option value="Modern" <?php echo $proposal['theme'] == 'Modern' ? 'selected' : ''; ?>>Modern</option>
                      <option value="Rustic" <?php echo $proposal['theme'] == 'Rustic' ? 'selected' : ''; ?>>Rustic</option>
                      <option value="Elegant" <?php echo $proposal['theme'] == 'Elegant' ? 'selected' : ''; ?>>Elegant</option>
                      <option value="Casual" <?php echo $proposal['theme'] == 'Casual' ? 'selected' : ''; ?>>Casual</option>
                      <option value="Tropical" <?php echo $proposal['theme'] == 'Tropical' ? 'selected' : ''; ?>>Tropical</option>
                      <option value="Other" <?php echo !in_array($proposal['theme'], ['Classic', 'Modern', 'Rustic', 'Elegant', 'Casual', 'Tropical', '']) ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <div id="otherThemeContainer" class="mt-2 <?php echo !in_array($proposal['theme'], ['Classic', 'Modern', 'Rustic', 'Elegant', 'Casual', 'Tropical', '']) ? '' : 'd-none'; ?>">
                      <input type="text" name="otherTheme" class="form-control" 
                             placeholder="Please specify theme"
                             value="<?php echo !in_array($proposal['theme'], ['Classic', 'Modern', 'Rustic', 'Elegant', 'Casual', 'Tropical', '']) ? htmlspecialchars($proposal['theme']) : ''; ?>"
                             id="otherThemeInput">
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Event Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Describe your event vision, special requirements, or any additional details..."><?php echo htmlspecialchars($proposal['description'] ?? ''); ?></textarea>
                  </div>
                </div>
              </div>

              <!-- Catering Section -->
<div class="mb-3">
    <label class="form-label fw-semibold">Catering Request</label>
    <select name="cateringRequest" class="form-select">
        <option value="no" <?php echo ($proposal['catering_request'] ?? 'no') === 'no' ? 'selected' : ''; ?>>No Catering Needed</option>
        <option value="yes" <?php echo ($proposal['catering_request'] ?? 'no') === 'yes' ? 'selected' : ''; ?>>Yes, I need catering</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Catering Details</label>
    <textarea name="cateringDetails" class="form-control" rows="3" placeholder="Please specify your catering requirements, menu preferences, dietary restrictions, etc."><?php echo htmlspecialchars($proposal['catering_details'] ?? ''); ?></textarea>
</div>

              <!-- ========================================= -->
              <!-- REQUIREMENTS SECTION                      -->
              <!-- ========================================= -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h5 class="mb-0"><i class="fas fa-concierge-bell me-2 text-success"></i>Services & Requirements</h5>
                </div>
                <div class="card-body">
                  <!-- Optional Add-ons -->
                  <div class="mb-4">
                    <h6 class="text-success mb-3">Optional Add-ons</h6>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <div class="addon-option border rounded p-3 h-100">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="addon_aircon" id="addonAircon" value="1" <?php echo $proposal['addon_aircon'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium" for="addonAircon">
                              <div class="d-flex justify-content-between align-items-center">
                                <span>
                                  <i class="fas fa-snowflake me-2 text-info"></i>
                                  Air Conditioning
                                </span>
                                <span class="badge bg-success">₱2,500</span>
                              </div>
                            </label>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="addon-option border rounded p-3 h-100">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="addon_corkage" id="addonCorkage" value="1" <?php echo $proposal['addon_corkage'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium" for="addonCorkage">
                              <div class="d-flex justify-content-between align-items-center">
                                <span>
                                  <i class="fas fa-truck me-2 text-warning"></i>
                                  Outside Catering Corkage
                                </span>
                                <span class="badge bg-success">₱5,000</span>
                              </div>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Decorations -->
                  <div class="mb-4">
                    <label class="form-label fw-semibold">Decorations & Setup</label>
                    <?php
                    $selectedDecorations = json_decode($proposal['decorations'] ?? '[]', true) ?? [];
                    ?>
                    <div class="row g-2">
                      <?php
                      $decorationOptions = [
                        'Stage Setup' => 'theater-masks',
                        'Sound System' => 'volume-up',
                        'Lighting System' => 'lightbulb',
                        'Flowers & Centerpieces' => 'spa',
                        'Balloons & Arches' => 'compass',
                        'Entrance Arch' => 'archway',
                        'Backdrop & Drapes' => 'images',
                        'Table Linens' => 'table',
                        'Chair Covers' => 'chair'
                      ];
                      $counter = 1;
                      foreach ($decorationOptions as $option => $icon): ?>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" 
                                   value="<?php echo $option; ?>" 
                                   id="decoration<?php echo $counter; ?>"
                                   <?php echo in_array($option, $selectedDecorations) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="decoration<?php echo $counter; ?>">
                              <i class="fas fa-<?php echo $icon; ?> me-2 text-primary"></i><?php echo $option; ?>
                            </label>
                          </div>
                        </div>
                      <?php $counter++; endforeach; ?>
                    </div>
                    <input type="hidden" name="decorations" id="selectedDecorations" value='<?php echo $proposal['decorations'] ?? '[]'; ?>'>
                    
                    <div class="mt-3">
                      <label class="form-label fw-semibold">Custom Decoration Requests</label>
                      <textarea name="customDecorations" class="form-control" rows="2" placeholder="Any specific decoration themes, colors, or special setup requests..."><?php echo htmlspecialchars($proposal['custom_decorations'] ?? ''); ?></textarea>
                    </div>
                  </div>

                  <!-- Equipment & Special Requests -->
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Equipment Needed</label>
                    <textarea name="equipmentNeeded" class="form-control" rows="2" placeholder="List any specific equipment: projectors, microphones, screens, etc."><?php echo htmlspecialchars($proposal['equipment_needed'] ?? ''); ?></textarea>
                  </div>
                  
                  <div>
                    <label class="form-label fw-semibold">Special Requests / Additional Notes</label>
                    <textarea name="specialRequests" class="form-control" rows="3" placeholder="Any special arrangements, accessibility needs, timing considerations, or other important details..."><?php echo htmlspecialchars($proposal['special_requests'] ?? ''); ?></textarea>
                  </div>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- BUDGET & PAYMENT SECTION                  -->
              <!-- ========================================= -->
              <div class="card mb-4">
                <div class="card-header bg-light">
                  <h5 class="mb-0"><i class="fas fa-calculator me-2 text-success"></i>Budget & Payment</h5>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label fw-semibold">Estimated Budget (PHP)</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light">₱</span>
                      <input type="number" name="estimatedBudget" class="form-control" id="estimatedBudgetInput"
                             value="<?php echo htmlspecialchars($proposal['estimated_budget'] ?? ''); ?>"
                             placeholder="e.g., 50000" min="0" step="1000">
                    </div>
                    <div class="form-text" id="budgetHelpText">
                      <div class="mb-2">
                        <strong>Calculated Estimate:</strong> <span id="calculatedEstimateDisplay" class="text-success">₱0</span>
                      </div>
                      <div class="text-muted">
                        Your estimated budget helps our manager understand your spending expectations. 
                        Final pricing may vary based on specific requirements.
                      </div>
                    </div>
                  </div>

                  <!-- Budget Comparison -->
                  <div id="budgetComparison" class="mt-3 p-3 rounded d-none">
                    <h6 class="text-success mb-3"><i class="fas fa-chart-line me-2"></i>Budget Comparison</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="small">Your Budget:</span>
                      <strong id="yourBudgetAmount" class="text-success">₱0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="small">Estimated Cost:</span>
                      <strong id="estimatedCostAmount" class="text-info">₱0</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="small">Difference:</span>
                      <strong id="budgetDifference" class="text-success">₱0</strong>
                    </div>
                    <div id="budgetWarning" class="mt-2 small text-danger d-none">
                      <i class="fas fa-exclamation-triangle me-1"></i>
                      Your budget is lower than the estimated cost. Our manager will contact you to discuss options.
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label fw-semibold">Preferred Payment Method <span class="text-danger">*</span></label>
                    <select name="paymentMethod" class="form-select" required>
                      <option value="">Select payment method</option>
                      <option value="Cash" <?php echo $proposal['payment_method'] == 'Cash' ? 'selected' : ''; ?>>💵 Cash</option>
                      <option value="GCash" <?php echo $proposal['payment_method'] == 'GCash' ? 'selected' : ''; ?>>📱 GCash</option>
                    </select>
                  </div>

                  <!-- Live Cost Summary -->
                  <div class="card border-success border-2 mt-4">
                    <div class="card-header bg-success bg-opacity-10 border-success">
                      <h6 class="mb-0 text-success"><i class="fas fa-calculator me-2"></i>Live Cost Summary</h6>
                    </div>
                    <div class="card-body">
                      <div class="row small mb-3">
                        <div class="col-7">
                          <span class="text-muted">Venue Rental:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownVenue">₱0.00</strong>
                        </div>
                        
                        <div class="col-7">
                          <span class="text-muted">Air Conditioning:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownAircon">₱0.00</strong>
                        </div>
                        
                        <div class="col-7">
                          <span class="text-muted">Corkage Fee:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownCorkage">₱0.00</strong>
                        </div>
                        
                        <div class="col-12 border-top mt-2 pt-2">
                          <div class="row">
                            <div class="col-7">
                              <strong>Total Estimated Cost:</strong>
                            </div>
                            <div class="col-5 text-end">
                              <strong class="text-success fs-6" id="costBreakdownTotal">₱0.00</strong>
                            </div>
                          </div>
                        </div>
                        
                        <div class="col-12 mt-2">
                          <div class="row">
                            <div class="col-7">
                              <strong>Required Deposit (50%):</strong>
                            </div>
                            <div class="col-5 text-end">
                              <strong class="text-warning" id="costBreakdownDeposit">₱0.00</strong>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="alert alert-info border-0 bg-info bg-opacity-10 small">
                        <i class="fas fa-sync-alt me-2"></i>
                        <strong>Live Updates:</strong> Costs update automatically as you make changes
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- SUBMIT BUTTONS                            -->
              <!-- ========================================= -->
              <div class="d-flex gap-3 mt-4">
                <button type="button" class="btn btn-outline-secondary flex-1" onclick="window.location.href='user_dashboard.php?page=event_proposal'">
                  <i class="fas fa-times me-2"></i>Cancel
                </button>
                <button type="submit" class="btn btn-success flex-1">
                  <i class="fas fa-save me-2"></i>Update Proposal
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ========================================= -->
      <!-- SUMMARY SIDEBAR (Right)                   -->
      <!-- ========================================= -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
          <div class="card-body">
            <h5 class="card-title d-flex align-items-center gap-2">
              <i class="fas fa-clipboard-list text-success"></i>
              Proposal Summary
            </h5>
            <div class="space-y-3 small">
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <small>Editing this proposal will update all costs automatically.</small>
              </div>
              <div><p class="text-muted mb-1">Proposal ID</p><p class="fw-semibold"><?php echo htmlspecialchars($proposal['proposal_id']); ?></p></div>
              <div><p class="text-muted mb-1">Current Status</p><p class="fw-semibold">
                <span class="badge <?php echo $proposal['status'] === 'needs_changes' ? 'bg-warning' : 'bg-info'; ?>">
                  <?php 
                  echo $proposal['status'] === 'needs_changes' ? 'Needs Changes' : 'Pending Review';
                  ?>
                </span>
              </p></div>
              <div><p class="text-muted mb-1">Submitted</p><p class="fw-semibold"><?php echo date('M j, Y', strtotime($proposal['submitted'])); ?></p></div>
              
              <!-- Live Summary Fields -->
              <div>
                <p class="text-muted mb-1">Event Title</p>
                <p class="fw-semibold text-truncate" id="summaryEventTitle"><?php echo htmlspecialchars($proposal['event_title']); ?></p>
              </div>
              <div>
                <p class="text-muted mb-1">Type</p>
                <p class="fw-semibold" id="summaryEventType"><?php echo htmlspecialchars($proposal['event_type']); ?></p>
              </div>
              <div>
                <p class="text-muted mb-1">Guests</p>
                <p class="fw-semibold" id="summaryGuests"><?php echo htmlspecialchars($proposal['expected_guests']); ?> pax</p>
              </div>
              
              <div class="pt-2 border-top">
                <p class="text-muted mb-1">Current Total Estimate</p>
                <p class="fw-bold text-info fs-6" id="summaryPreliminaryCost">₱<?php echo number_format($proposal['total_estimated_cost'], 2); ?></p>
              </div>
              <div>
                <p class="text-muted mb-1">Required Deposit</p>
                <p class="fw-bold text-warning fs-6" id="summaryDeposit">₱<?php echo number_format($proposal['deposit_amount'], 2); ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all event listeners
    initializeEventListeners();
    
    // Calculate initial costs
    updateAllCosts();
    
    // Setup real-time updates
    setupRealTimeUpdates();
});

function initializeEventListeners() {
    // Dynamic "Other" field handling
    const eventTypeSelect = document.getElementById('eventTypeSelect');
    const otherEventTypeContainer = document.getElementById('otherEventTypeContainer');
    
    if (eventTypeSelect) {
        eventTypeSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                otherEventTypeContainer.classList.remove('d-none');
            } else {
                otherEventTypeContainer.classList.add('d-none');
            }
            updateAllCosts();
        });
    }

    const themeSelect = document.getElementById('themeSelect');
    const otherThemeContainer = document.getElementById('otherThemeContainer');
    
    if (themeSelect) {
        themeSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                otherThemeContainer.classList.remove('d-none');
            } else {
                otherThemeContainer.classList.add('d-none');
            }
        });
    }

    // Live cost calculation listeners
    const venueSelect = document.querySelector('select[name="venuePreference"]');
    const guestsInput = document.querySelector('input[name="expectedGuests"]');
    const timeInput = document.querySelector('input[name="arrivalTime"]');
    
    if (venueSelect) venueSelect.addEventListener('change', updateAllCosts);
    if (guestsInput) guestsInput.addEventListener('input', updateAllCosts);
    if (timeInput) timeInput.addEventListener('change', updateAllCosts);
    
    // Add-ons cost updates
    const airconCheckbox = document.getElementById('addonAircon');
    const corkageCheckbox = document.getElementById('addonCorkage');
    
    if (airconCheckbox) airconCheckbox.addEventListener('change', updateAllCosts);
    if (corkageCheckbox) corkageCheckbox.addEventListener('change', updateAllCosts);

    // Decorations handling
    document.querySelectorAll('.decoration-check').forEach(cb => {
        cb.addEventListener('change', updateDecorations);
    });

    // Budget comparison
    const budgetInput = document.getElementById('estimatedBudgetInput');
    if (budgetInput) {
        budgetInput.addEventListener('input', updateBudgetComparison);
    }

    // Form validation
    const editForm = document.getElementById('editEventForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Check required fields
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
}

function updateAllCosts() {
    const venueCost = calculateVenueCost();
    const additionalServicesCost = calculateAdditionalServicesCost();
    const totalCost = venueCost + additionalServicesCost;
    const depositAmount = totalCost * 0.5;

    // Update cost display
    updateCostDisplay(venueCost, additionalServicesCost, totalCost, depositAmount);
    
    // Update budget comparison
    updateBudgetComparison();
    
    // Update summary sidebar
    updateSummarySidebar(totalCost, depositAmount);
}

function calculateVenueCost() {
    const venueSelect = document.querySelector('select[name="venuePreference"]');
    const timeInput = document.querySelector('input[name="arrivalTime"]');
    const airconCheckbox = document.getElementById('addonAircon');
    
    if (!venueSelect || !venueSelect.value) return 0;
    
    const venue = venueSelect.value;
    const arrivalTime = timeInput ? timeInput.value : '';
    const addonAircon = airconCheckbox ? airconCheckbox.checked : false;
    
    const venueCosts = {
        "Pavillion Old": { day: 8000, night: 12000 },
        "Pavillion Anex": { day: 14000, night: 16000 },
        "Pavillion Whole": { day: 18000, night: 20000 },
        "Mabuhay Lounge": { aircon: 9500, non_aircon: 7500 }
    };

    if (!venueCosts[venue]) return 0;

    if (venue === "Mabuhay Lounge") {
        return addonAircon ? venueCosts[venue].aircon : venueCosts[venue].non_aircon;
    } else {
        if (arrivalTime) {
            const hour = parseInt(arrivalTime.split(':')[0]);
            const isNight = (hour >= 18 || hour < 6);
            return isNight ? venueCosts[venue].night : venueCosts[venue].day;
        }
        return venueCosts[venue].day;
    }
}

function calculateAdditionalServicesCost() {
    let total = 0;
    const venueSelect = document.querySelector('select[name="venuePreference"]');
    const venue = venueSelect ? venueSelect.value : '';
    const airconCheckbox = document.getElementById('addonAircon');
    const corkageCheckbox = document.getElementById('addonCorkage');
    
    // Air conditioning (only for non-Mabuhay Lounge venues)
    if (airconCheckbox && airconCheckbox.checked && venue !== "Mabuhay Lounge") {
        total += 2500;
    }
    
    // Corkage fee
    if (corkageCheckbox && corkageCheckbox.checked) {
        total += 5000;
    }
    
    return total;
}

function updateCostDisplay(venueCost, additionalServicesCost, totalCost, depositAmount) {
    // Format currency helper
    const formatCurrency = (amount) => {
        return '₱' + amount.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    // Update main cost breakdown
    updateElementText('costBreakdownVenue', formatCurrency(venueCost));
    updateElementText('costBreakdownAircon', formatCurrency(
        document.getElementById('addonAircon') && 
        document.getElementById('addonAircon').checked && 
        document.querySelector('select[name="venuePreference"]') &&
        document.querySelector('select[name="venuePreference"]').value !== "Mabuhay Lounge" ? 2500 : 0
    ));
    updateElementText('costBreakdownCorkage', formatCurrency(
        document.getElementById('addonCorkage') && 
        document.getElementById('addonCorkage').checked ? 5000 : 0
    ));
    updateElementText('costBreakdownTotal', formatCurrency(totalCost));
    updateElementText('costBreakdownDeposit', formatCurrency(depositAmount));

    // Update calculated estimate
    updateElementText('calculatedEstimateDisplay', formatCurrency(totalCost));
}

function updateElementText(elementId, text) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
    }
}

function updateBudgetComparison() {
    const budgetInput = document.getElementById('estimatedBudgetInput');
    const budgetComparison = document.getElementById('budgetComparison');
    const budgetWarning = document.getElementById('budgetWarning');
    
    if (!budgetInput || !budgetComparison) return;
    
    const totalCost = calculateTotalCost();
    const userBudget = parseInt(budgetInput.value) || 0;
    
    if (userBudget > 0) {
        budgetComparison.classList.remove('d-none');
        
        updateElementText('yourBudgetAmount', '₱' + userBudget.toLocaleString());
        updateElementText('estimatedCostAmount', '₱' + totalCost.toLocaleString());
        
        const difference = userBudget - totalCost;
        const differenceElement = document.getElementById('budgetDifference');
        
        if (differenceElement) {
            if (difference >= 0) {
                differenceElement.textContent = '₱+' + difference.toLocaleString();
                differenceElement.className = 'text-success';
                if (budgetWarning) budgetWarning.classList.add('d-none');
            } else {
                differenceElement.textContent = '₱' + difference.toLocaleString();
                differenceElement.className = 'text-danger';
                if (budgetWarning) budgetWarning.classList.remove('d-none');
            }
        }
    } else {
        budgetComparison.classList.add('d-none');
    }
}

function calculateTotalCost() {
    return calculateVenueCost() + calculateAdditionalServicesCost();
}

function updateSummarySidebar(totalCost, depositAmount) {
    const formatCurrency = (amount) => {
        return '₱' + amount.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    updateElementText('summaryPreliminaryCost', formatCurrency(totalCost));
    updateElementText('summaryDeposit', formatCurrency(depositAmount));
}

function updateDecorations() {
    const selected = Array.from(document.querySelectorAll('.decoration-check:checked')).map(cb => cb.value);
    const decorationsInput = document.getElementById('selectedDecorations');
    if (decorationsInput) {
        decorationsInput.value = JSON.stringify(selected);
    }
}

// Real-time form field updates for better UX
function setupRealTimeUpdates() {
    // Update summary as user types
    const formFields = document.querySelectorAll('#editEventForm input, #editEventForm select, #editEventForm textarea');
    formFields.forEach(field => {
        field.addEventListener('input', updateFormSummary);
        field.addEventListener('change', updateFormSummary);
    });
}

function updateFormSummary() {
    // Update any real-time summary displays here
    const eventTitle = document.querySelector('input[name="eventTitle"]');
    const eventType = document.querySelector('select[name="eventType"]');
    const guests = document.querySelector('input[name="expectedGuests"]');
    
    if (eventTitle && eventTitle.value) {
        updateElementText('summaryEventTitle', eventTitle.value);
    }
    if (eventType && eventType.value) {
        updateElementText('summaryEventType', eventType.value);
    }
    if (guests && guests.value) {
        updateElementText('summaryGuests', guests.value + ' pax');
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>