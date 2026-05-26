<?php
// dashboard\event_form_proposal.php - FIXED VERSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../user_login.php');
    exit();
}

// Get user data from session - FIXED VERSION
$user_data = [
    'user_id' => $_SESSION['user_id'] ?? 0,
    'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
    'email' => $_SESSION['email'] ?? 'unknown@example.com',
    'contact_number' => $_SESSION['contact_number'] ?? '',
    'username' => $_SESSION['username'] ?? 'user'
];

// Only show success message from session (set by handler)
if (isset($_SESSION['success_message'])) {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("proposalId").textContent = "' . htmlspecialchars($_SESSION['success_message']) . '".match(/EP\\w+/)[0];
            new bootstrap.Modal(document.getElementById("confirmationModal")).show();
        });
    </script>';
    unset($_SESSION['success_message']);
}


?>

<!-- ========================================= -->
<!-- EVENT PROPOSAL FORM PAGE                  -->
<!-- ========================================= -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Proposal Form</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- ========================================= -->
  <!-- EXTERNAL STYLESHEET                       -->
  <!-- ========================================= -->
  <link rel="stylesheet" href="../dashboard/event_form_proposal.css">
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
  <i class="fas fa-arrow-left me-2"></i> Menu
</button>

    <!-- ========================================= -->
    <!-- PAGE HEADER                               -->
    <!-- ========================================= -->
    <h2 class="fw-bold mb-2 text-success">Event Proposal Form</h2>
    <p class="text-muted mb-4">Plan your perfect event with our comprehensive proposal form</p>

    <!-- ========================================= -->
    <!-- PROGRESS BAR WITH STEP INDICATORS         -->
    <!-- ========================================= -->
    <div class="progress-container mb-4">
      <div class="progress-bar" id="progressBar"></div>
      
      <!-- Step Circles -->
      <div class="progress-steps">
        <span class="step-circle active" data-step="basic">
          <span class="step-number"></span>
          <i class="fas fa-check step-check d-none"></i>
        </span>
        <span class="step-circle" data-step="details">
          <span class="step-number"></span>
          <i class="fas fa-check step-check d-none"></i>
        </span>
        <span class="step-circle" data-step="requirements">
          <span class="step-number"></span>
          <i class="fas fa-check step-check d-none"></i>
        </span>
        <span class="step-circle" data-step="budget">
          <span class="step-number"></span>
          <i class="fas fa-check step-check d-none"></i>
        </span>
        <span class="step-circle" data-step="review">
          <span class="step-number"></span>
          <i class="fas fa-check step-check d-none"></i>
        </span>
      </div>
      
      <!-- Step Labels -->
      <div class="progress-labels d-flex justify-content-between text-xs md-text-sm font-medium mt-2">
        <span class="text-center text-success">Basic Info</span>
        <span class="text-center text-muted">Event Details</span>
        <span class="text-center text-muted">Requirements</span>
        <span class="text-center text-muted">Budget</span>
        <span class="text-center text-muted">Review & Submit</span>
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
            <h4 class="card-title text-success mb-3" id="stepTitle">Basic Info</h4>
            <p class="text-muted mb-4" id="stepDescription">Complete this section to proceed</p>

            <!-- ========================================= -->
            <!-- MULTI-STEP FORM                           -->
            <!-- ========================================= -->
            <form id="eventForm" action="" method="POST">

             <!-- ========================================= -->
<!-- STEP 1: BASIC INFO                        -->
<!-- ========================================= -->
<div class="form-step step-active" id="step-basic">
  <div class="space-y-3">
    <div class="text-center mb-4">
      <div class="w-16 h-16 bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
        <i class="fas fa-user text-success fs-4"></i>
      </div>
      <h5 class="text-success mb-2">Tell us who you are</h5>
      <p class="text-muted">We'll use this information to contact you about your event</p>
    </div>
    
    <div>
      <label class="form-label fw-semibold">Full Name</label>
      <input type="text" name="fullName" class="form-control bg-light" 
             value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required readonly>
      <div class="form-text text-success">Pre-filled from your account</div>
    </div>
    
    <div>
      <label class="form-label fw-semibold">Email Address</label>
      <input type="email" name="email" class="form-control bg-light" 
             value="<?php echo htmlspecialchars($user_data['email']); ?>" required readonly>
      <div class="form-text text-success">Pre-filled from your account</div>
    </div>
    
    <div>
      <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
      <input type="tel" name="contactNumber" class="form-control" 
             value="<?php echo htmlspecialchars($user_data['contact_number']); ?>"
             placeholder="e.g., 09171234567" required 
             pattern="[0-9+]{10,15}" title="Please enter a valid phone number (10-15 digits)">
      <div class="form-text">
        <?php if (!empty($user_data['contact_number'])): ?>
          <span class="text-success">Pre-filled from your account - you can update if needed</span>
        <?php else: ?>
          We'll use this to contact you about your event details
        <?php endif; ?>
      </div>
    </div>
    
    <div>
      <label class="form-label fw-semibold">Username</label>
      <input type="text" class="form-control bg-light" 
             value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
      <div class="form-text text-muted">Username cannot be changed</div>
    </div>
  </div>
</div>

              <!-- ========================================= -->
              <!-- STEP 2: EVENT DETAILS                     -->
              <!-- ========================================= -->
              <div class="form-step" id="step-details">
                <div class="space-y-4">
                  
                  <!-- Step Header -->
                  <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                      <i class="fas fa-calendar-alt text-success fs-4"></i>
                    </div>
                    <h5 class="text-success mb-2">Plan your event basics</h5>
                    <p class="text-muted">Choose your venue and provide event details</p>
                  </div>

                  <!-- Enhanced guidance section -->
                  <div class="venue-section-guide">
                    <div class="venue-step-guide">
                      <span class="venue-step-number">1</span>
                      <span>First, choose your event time preference (affects pricing)</span>
                    </div>
                    <div class="venue-step-guide">
                      <span class="venue-step-number">2</span>
                      <span>Then select your venue by clicking on any venue card below</span>
                    </div>
                    <div class="venue-step-guide">
                      <span class="venue-step-number">3</span>
                      <span>Finally, fill in your event information</span>
                    </div>
                  </div>

                  <!-- ========================================= -->
                  <!-- VENUE & TIME PREFERENCE SELECTION -->
                  <!-- ========================================= -->
                  <div>
                    <label class="form-label fw-semibold">Event Time Preference</label>
                    
                    <!-- Daytime / Nighttime Toggle -->
                    <div class="time-toggle mb-4">
                      <div class="d-flex gap-3">
                        <div class="flex-fill">
                          <input type="radio" class="btn-check" name="eventTime" id="daytime" value="day" checked>
                          <label class="btn btn-outline-success w-100 rounded-pill py-3" for="daytime">
                            <i class="fas fa-sun me-2"></i>Day Time
                          </label>
                        </div>
                        <div class="flex-fill">
                          <input type="radio" class="btn-check" name="eventTime" id="nighttime" value="night">
                          <label class="btn btn-outline-success w-100 rounded-pill py-3" for="nighttime">
                            <i class="fas fa-moon me-2"></i>Night Time
                          </label>
                        </div>
                      </div>
                      <div class="form-text text-center mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Time selection affects venue pricing
                      </div>
                    </div>

                    <!-- Venue Options Grid -->
                    <div class="venue-selector mt-2">
                      <div class="row g-3">
                        
                        <!-- Venue: Pavillion Old -->
                        <div class="col-md-6">
                          <div class="venue-option border rounded p-0 overflow-hidden cursor-pointer" data-venue="Pavillion Old" onclick="selectVenue(this, 'Pavillion Old')">
                            <div class="row g-0 h-100">
                              <div class="col-5 venue-image">
                                <img src="https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=200&h=300&fit=crop" alt="Pavillion Old" class="img-fluid w-100 h-100" style="object-fit: cover; min-height: 250px;">
                              </div>
                              <div class="col-7">
                                <div class="p-3 venue-content h-100 d-flex flex-column">
                                  <h6 class="mb-2 text-success">Pavillion Old</h6>
                                  <div class="row small mb-2">
                                    <div class="col-6"><strong>Capacity</strong><br>50-80 pax</div>
                                    <div class="col-6"><strong>Duration</strong><br>4 Hours</div>
                                  </div>
                                  <div class="value-rates mb-3">
                                    <div class="d-flex justify-content-between small">
                                      <span>Day Time Rate:</span>
                                      <strong class="text-success">₱8,000</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                      <span>Night Time Rate:</span>
                                      <strong class="text-success">₱12,000</strong>
                                    </div>
                                  </div>
                                  <div class="included-features mt-auto">
                                    <small class="text-muted">
                                      <i class="fas fa-check text-success me-1"></i>
                                      Includes: Basic tables, chairs, fans, power, setup & cleanup
                                    </small>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Venue: Pavillion Anex -->
                        <div class="col-md-6">
                          <div class="venue-option border rounded p-0 overflow-hidden cursor-pointer" data-venue="Pavillion Anex" onclick="selectVenue(this, 'Pavillion Anex')">
                            <div class="row g-0 h-100">
                              <div class="col-5 venue-image">
                                <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=200&h=300&fit=crop" alt="Pavillion Anex" class="img-fluid w-100 h-100" style="object-fit: cover; min-height: 250px;">
                              </div>
                              <div class="col-7">
                                <div class="p-3 venue-content h-100 d-flex flex-column">
                                  <h6 class="mb-2 text-success">Pavillion Anex</h6>
                                  <div class="row small mb-2">
                                    <div class="col-6"><strong>Capacity</strong><br>100-150 pax</div>
                                    <div class="col-6"><strong>Duration</strong><br>4 hours</div>
                                  </div>
                                  <div class="value-rates mb-3">
                                    <div class="d-flex justify-content-between small">
                                      <span>Day Time Rate:</span>
                                      <strong class="text-success">₱14,000</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                      <span>Night Time Rate:</span>
                                      <strong class="text-success">₱16,000</strong>
                                    </div>
                                  </div>
                                  <div class="included-features mt-auto">
                                    <small class="text-muted">
                                      <i class="fas fa-check text-success me-1"></i>
                                      Includes: Basic tables, chairs, fans, power, setup & cleanup
                                    </small>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Venue: Pavillion Whole -->
                        <div class="col-md-6">
                          <div class="venue-option border rounded p-0 overflow-hidden cursor-pointer" data-venue="Pavillion Whole" onclick="selectVenue(this, 'Pavillion Whole')">
                            <div class="row g-0 h-100">
                              <div class="col-5 venue-image">
                                <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=200&h=300&fit=crop" alt="Pavillion Whole" class="img-fluid w-100 h-100" style="object-fit: cover; min-height: 250px;">
                              </div>
                              <div class="col-7">
                                <div class="p-3 venue-content h-100 d-flex flex-column">
                                  <h6 class="mb-2 text-success">Pavillion Whole</h6>
                                  <div class="row small mb-2">
                                    <div class="col-6"><strong>Capacity</strong><br>200-250 pax</div>
                                    <div class="col-6"><strong>Duration</strong><br>4 Hours</div>
                                  </div>
                                  <div class="value-rates mb-3">
                                    <div class="d-flex justify-content-between small">
                                      <span>Day Time Rate:</span>
                                      <strong class="text-success">₱18,000</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                      <span>Night Time Rate:</span>
                                      <strong class="text-success">₱20,000</strong>
                                    </div>
                                  </div>
                                  <div class="included-features mt-auto">
                                    <small class="text-muted">
                                      <i class="fas fa-check text-success me-1"></i>
                                      Includes: Basic tables, chairs, fans, power, setup & cleanup
                                    </small>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Venue: Mabuhay Lounge -->
                        <div class="col-md-6">
                          <div class="venue-option border rounded p-0 overflow-hidden cursor-pointer" data-venue="Mabuhay Lounge" onclick="selectVenue(this, 'Mabuhay Lounge')">
                            <div class="row g-0 h-100">
                              <div class="col-5 venue-image">
                                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=200&h=300&fit=crop" alt="Mabuhay Lounge" class="img-fluid w-100 h-100" style="object-fit: cover; min-height: 250px;">
                              </div>
                              <div class="col-7">
                                <div class="p-3 venue-content h-100 d-flex flex-column">
                                  <h6 class="mb-2 text-success">Mabuhay Lounge</h6>
                                  <div class="row small mb-2">
                                    <div class="col-6"><strong>Capacity</strong><br>50-70 pax</div>
                                    <div class="col-6"><strong>Duration</strong><br>4 hours</div>
                                  </div>
                                  <div class="value-rates mb-3">
                                    <div class="d-flex justify-content-between small">
                                      <span>Air Conditioned:</span>
                                      <strong class="text-success">₱9,500</strong>
                                    </div>
                                    <div class="d-flex justify-content-between small">
                                      <span>Non Air Conditioned:</span>
                                      <strong class="text-success">₱7,500</strong>
                                    </div>
                                  </div>
                                  <div class="included-features mt-auto">
                                    <small class="text-muted">
                                      <i class="fas fa-check text-success me-1"></i>
                                      Includes: Basic tables, chairs, fans, power, setup & cleanup
                                    </small>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <input type="hidden" name="venuePreference" id="selectedVenue" required>
                    </div>
                  </div>

                  <!-- Divider to separate venue selection from other details -->
                  <div class="border-top pt-4 mt-4">
                    <h5 class="text-success mb-3">Event Information</h5>
                    
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
                      <input type="text" name="eventTitle" class="form-control" placeholder="e.g., Maria's Wedding, Company Year-End Party" required>
                      <div class="form-text">Give your event a descriptive title</div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-semibold">Event Type <span class="text-danger">*</span></label>
                      <select name="eventType" class="form-select" id="eventTypeSelect" required>
                        <option value="">Select event type</option>
                        <option>Wedding</option>
                        <option>Birthday</option>
                        <option>Corporate</option>
                        <option>Anniversary</option>
                        <option>Reunion</option>
                        <option>Graduation</option>
                        <option value="Other">Other</option>
                      </select>
                      <div id="otherEventTypeContainer" class="mt-2 d-none">
                        <input type="text" name="otherEventType" class="form-control" placeholder="Please specify event type" id="otherEventTypeInput">
                      </div>
                    </div>

                    <!-- Arrival Date & Time Inputs -->
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Arrival Date <span class="text-danger">*</span></label>
                        <input type="date" name="arrivalDate" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label fw-semibold">Arrival Time <span class="text-danger">*</span></label>
                        <input type="time" name="arrivalTime" class="form-control" required>
                      </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label fw-semibold">Expected Number of Guests <span class="text-danger">*</span></label>
                      <input type="number" name="expectedGuests" class="form-control" placeholder="e.g., 150" min="1" max="500" required>
                      <div class="form-text">Enter the approximate number of attendees</div>
                    </div>

                    <!-- Theme & Description -->
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Theme or Setup Style</label>
                      <select name="theme" class="form-select" id="themeSelect">
                        <option value="">Select theme (optional)</option>
                        <option>Classic</option>
                        <option>Modern</option>
                        <option>Rustic</option>
                        <option>Elegant</option>
                        <option>Casual</option>
                        <option>Tropical</option>
                        <option value="Other">Other</option>
                      </select>
                      <div id="otherThemeContainer" class="mt-2 d-none">
                        <input type="text" name="otherTheme" class="form-control" placeholder="Please specify theme" id="otherThemeInput">
                      </div>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Event Description</label>
                      <textarea name="description" class="form-control" rows="3" placeholder="Describe your event vision, special requirements, or any additional details..."></textarea>
                      <div class="form-text">Optional: Share more about your event vision</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- STEP 3: REQUIREMENTS                      -->
              <!-- ========================================= -->
              <div class="form-step" id="step-requirements">
                <div class="space-y-4">
                  
                  <!-- Step Header -->
                  <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                      <i class="fas fa-concierge-bell text-success fs-4"></i>
                    </div>
                    <h5 class="text-success mb-2">Add Extra Services</h5>
                    <p class="text-muted">Select additional services to enhance your event</p>
                  </div>

                  <!-- Catering Request -->
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <label class="form-label fw-semibold">Catering Services</label>
                      <p class="text-muted small mb-3">Would you like catering services for your event?</p>
                      <div class="mt-2">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="cateringRequest" id="cateringYes" value="yes">
                          <label class="form-check-label fw-medium" for="cateringYes">
                            <i class="fas fa-utensils me-2 text-success"></i>Yes, I need catering
                          </label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="cateringRequest" id="cateringNo" value="no" checked>
                          <label class="form-check-label fw-medium" for="cateringNo">
                            <i class="fas fa-times me-2 text-muted"></i>No, I'll handle catering separately
                          </label>
                        </div>
                      </div>
                      <div id="cateringDetails" class="mt-3 d-none">
                        <label class="form-label fw-semibold">Catering Details</label>
                        <textarea name="cateringDetails" class="form-control" rows="3" placeholder="Please describe your catering needs: number of meals, dietary restrictions, preferred cuisine, budget range, etc."></textarea>
                        <div class="form-text">
                          <i class="fas fa-info-circle me-1"></i>
                          Our catering manager will contact you to discuss menu options and pricing
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Optional Add-ons Section -->
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <h6 class="text-success mb-3">Optional Add-ons</h6>
                      <p class="text-muted small mb-3">Enhance your venue with these optional services</p>
                      
                      <div class="row g-3">
                        <!-- Air Conditioning -->
                        <div class="col-md-6">
                          <div class="addon-option border rounded p-3 h-100">
                            <div class="form-check">
                              <input class="form-check-input addon-checkbox" type="checkbox" name="addon_aircon" id="addonAircon" value="2500" data-cost="2500">
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
                            <div class="form-text small mt-2">
                              Keep your guests comfortable with climate control
                            </div>
                          </div>
                        </div>

                        <!-- Outside Catering Corkage -->
                        <div class="col-md-6">
                          <div class="addon-option border rounded p-3 h-100">
                            <div class="form-check">
                              <input class="form-check-input addon-checkbox" type="checkbox" name="addon_corkage" id="addonCorkage" value="5000" data-cost="5000">
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
                            <div class="form-text small mt-2">
                              Bring your own caterer with our corkage service
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mt-3 p-3 bg-light rounded">
                        <small class="text-muted">
                          <i class="fas fa-info-circle me-1"></i>
                          All venues include FREE: Basic tables, chairs, fans, power, setup & cleanup
                        </small>
                      </div>
                    </div>
                  </div>

                  <!-- Decorations Section -->
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <label class="form-label fw-semibold">Decorations & Setup</label>
                      <p class="text-muted small mb-3">Select the decoration items you need (optional)</p>
                      
                      <div class="row g-2">
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Stage Setup" id="decoration1">
                            <label class="form-check-label" for="decoration1">
                              <i class="fas fa-theater-masks me-2 text-primary"></i>Stage Setup
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Sound System" id="decoration2">
                            <label class="form-check-label" for="decoration2">
                              <i class="fas fa-volume-up me-2 text-primary"></i>Sound System
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Lighting System" id="decoration3">
                            <label class="form-check-label" for="decoration3">
                              <i class="fas fa-lightbulb me-2 text-primary"></i>Lighting System
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Flowers & Centerpieces" id="decoration4">
                            <label class="form-check-label" for="decoration4">
                              <i class="fas fa-spa me-2 text-primary"></i>Flowers & Centerpieces
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Balloons & Arches" id="decoration5">
                            <label class="form-check-label" for="decoration5">
                              <i class="fas fa-compass me-2 text-primary"></i>Balloons & Arches
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Entrance Arch" id="decoration6">
                            <label class="form-check-label" for="decoration6">
                              <i class="fas fa-archway me-2 text-primary"></i>Entrance Arch
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Backdrop & Drapes" id="decoration7">
                            <label class="form-check-label" for="decoration7">
                              <i class="fas fa-images me-2 text-primary"></i>Backdrop & Drapes
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Table Linens" id="decoration8">
                            <label class="form-check-label" for="decoration8">
                              <i class="fas fa-table me-2 text-primary"></i>Table Linens
                            </label>
                          </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                          <div class="form-check">
                            <input class="form-check-input decoration-check" type="checkbox" value="Chair Covers" id="decoration9">
                            <label class="form-check-label" for="decoration9">
                              <i class="fas fa-chair me-2 text-primary"></i>Chair Covers
                            </label>
                          </div>
                        </div>
                      </div>
                      <input type="hidden" name="decorations" id="selectedDecorations">
                      
                      <div class="mt-3">
                        <label class="form-label fw-semibold">Custom Decoration Requests</label>
                        <textarea name="customDecorations" class="form-control" rows="2" placeholder="Any specific decoration themes, colors, or special setup requests..."></textarea>
                        <div class="form-text">Let us know about any specific decoration preferences</div>
                      </div>
                    </div>
                  </div>

                  <!-- Equipment & Special Requests -->
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Equipment Needed</label>
                        <textarea name="equipmentNeeded" class="form-control" rows="2" placeholder="List any specific equipment: projectors, microphones, screens, etc."></textarea>
                        <div class="form-text">Any additional audio-visual or technical equipment requirements</div>
                      </div>
                      
                      <div>
                        <label class="form-label fw-semibold">Special Requests / Additional Notes</label>
                        <textarea name="specialRequests" class="form-control" rows="3" placeholder="Any special arrangements, accessibility needs, timing considerations, or other important details..."></textarea>
                        <div class="form-text">Please share any other requirements or considerations for your event</div>
                      </div>
                    </div>
                  </div>

                  <!-- Cost Preview -->
                  <div class="card border-info border-2">
                    <div class="card-header bg-info bg-opacity-10 border-info">
                      <h6 class="mb-0 text-info"><i class="fas fa-calculator me-2"></i>Additional Services Cost Preview</h6>
                    </div>
                    <div class="card-body">
                      <div class="row small">
                        <div class="col-6">
                          <span class="text-muted">Air Conditioning:</span>
                        </div>
                        <div class="col-6 text-end">
                          <strong id="addonAirconCost">₱0</strong>
                        </div>
                        
                        <div class="col-6">
                          <span class="text-muted">Corkage Fee:</span>
                        </div>
                        <div class="col-6 text-end">
                          <strong id="addonCorkageCost">₱0</strong>
                        </div>
                        
                        <div class="col-12 border-top mt-2 pt-2">
                          <div class="row">
                            <div class="col-6">
                              <strong>Additional Services Total:</strong>
                            </div>
                            <div class="col-6 text-end">
                              <strong class="text-info" id="additionalServicesTotal">₱0</strong>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="mt-2 text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        This is a preliminary estimate. Final pricing will be confirmed after manager review.
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- STEP 4: BUDGET                            -->
              <!-- ========================================= -->
              <div class="form-step" id="step-budget">
                <div class="space-y-4">
                  
                  <!-- Step Header -->
                  <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                      <i class="fas fa-calculator text-success fs-4"></i>
                    </div>
                    <h5 class="text-success mb-2">See costs and payment process</h5>
                    <p class="text-muted">Review your estimated costs and provide budget information</p>
                  </div>

                  <!-- Automatic Cost Calculation -->
                  <div class="card border-success border-2">
                    <div class="card-header bg-success bg-opacity-10 border-success">
                      <h6 class="mb-0 text-success"><i class="fas fa-calculator me-2"></i>Automatic Cost Calculation</h6>
                    </div>
                    <div class="card-body">
                      <div class="row small mb-3">
                        <div class="col-7">
                          <span class="text-muted">Venue Rental:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownVenue">₱0</strong>
                        </div>
                        
                        <div class="col-7">
                          <span class="text-muted">Catering Estimate:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownCatering">₱0</strong>
                        </div>
                        
                        <div class="col-7">
                          <span class="text-muted">Air Conditioning:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownAircon">₱0</strong>
                        </div>
                        
                        <div class="col-7">
                          <span class="text-muted">Corkage Fee:</span>
                        </div>
                        <div class="col-5 text-end">
                          <strong id="costBreakdownCorkage">₱0</strong>
                        </div>
                        
                        <div class="col-12 border-top mt-2 pt-2">
                          <div class="row">
                            <div class="col-7">
                              <strong>Total Estimated Cost:</strong>
                            </div>
                            <div class="col-5 text-end">
                              <strong class="text-success fs-6" id="costBreakdownTotal">₱0</strong>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="alert alert-info border-0 bg-info bg-opacity-10 small">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This is a preliminary estimate. Final pricing will be confirmed after manager review and may vary based on specific requirements.
                      </div>
                    </div>
                  </div>

                  <!-- Your Budget Section -->
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <h6 class="text-success mb-3"><i class="fas fa-wallet me-2"></i>Your Budget</h6>
                      
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Estimated Budget (PHP)</label>
                        <div class="input-group">
                          <span class="input-group-text bg-light">₱</span>
                          <input type="number" name="estimatedBudget" class="form-control" placeholder="e.g., 50000" min="0" step="1000" id="estimatedBudgetInput">
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
                    </div>
                  </div>

                  <!-- Payment Method -->
                  <div class="card border-0 shadow-sm">
                    <div class="card-body">
                      <h6 class="text-success mb-3"><i class="fas fa-credit-card me-2"></i>Payment Method Preference</h6>
                      
                      <div class="mb-3">
                        <label class="form-label fw-semibold">Preferred Payment Method <span class="text-danger">*</span></label>
                        <select name="paymentMethod" class="form-select" required>
                          <option value="">Select payment method</option>
                          <option value="Cash">💵 Cash</option>
                          <option value="GCash">📱 GCash</option>
                        </select>
                        <div class="form-text">Select your preferred payment method for the deposit and final payment</div>
                      </div>
                    </div>
                  </div>

                  <!-- Payment Process Explanation -->
                  <div class="card border-info border-2">
                    <div class="card-header bg-info bg-opacity-10 border-info">
                      <h6 class="mb-0 text-info"><i class="fas fa-info-circle me-2"></i>Payment Process Explained</h6>
                    </div>
                    <div class="card-body">
                      <div class="alert alert-success border-success bg-success bg-opacity-10 mb-4">
                        <div class="d-flex align-items-center">
                          <i class="fas fa-check-circle text-success fs-4 me-3"></i>
                          <div>
                            <h6 class="text-success mb-1">📋 NO PAYMENT NEEDED NOW!</h6>
                            <p class="mb-0 text-success">You don't need to pay anything to submit your proposal</p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="process-steps">
                        <div class="process-step d-flex align-items-start mb-3">
                          <div class="step-number bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0">
                            1
                          </div>
                          <div>
                            <h6 class="mb-1 text-success">Submit Proposal</h6>
                            <p class="text-muted small mb-0">Fill out and submit this event proposal form</p>
                          </div>
                        </div>
                        
                        <div class="process-step d-flex align-items-start mb-3">
                          <div class="step-number bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0">
                            2
                          </div>
                          <div>
                            <h6 class="mb-1 text-info">Manager Approves</h6>
                            <p class="text-muted small mb-0">Our event manager reviews your proposal (within 48 hours)</p>
                          </div>
                        </div>
                        
                        <div class="process-step d-flex align-items-start mb-3">
                          <div class="step-number bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0">
                            3
                          </div>
                          <div>
                            <h6 class="mb-1 text-warning">Pay 50% Deposit</h6>
                            <p class="text-muted small mb-0">Secure your booking with a 50% deposit payment</p>
                          </div>
                        </div>
                        
                        <div class="process-step d-flex align-items-start">
                          <div class="step-number bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0">
                            4
                          </div>
                          <div>
                            <h6 class="mb-1 text-success">Event Confirmed</h6>
                            <p class="text-muted small mb-0">Your event is officially booked and confirmed!</p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="text-success mb-2">💡 Important Notes:</h6>
                        <ul class="small text-muted mb-0">
                          <li>No payment is required to submit this proposal</li>
                          <li>You'll receive email updates on your proposal status</li>
                          <li>Deposit payment instructions will be provided after approval</li>
                          <li>Balance payment is due 7 days before your event</li>
                          <li>Cancellation policy: Full refund if cancelled 30+ days before event</li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- ========================================= -->
              <!-- STEP 5: REVIEW & SUBMIT FOR APPROVAL      -->
              <!-- ========================================= -->
              <div class="form-step" id="step-review">
                <div class="space-y-4">
                  
                  <!-- Step Header -->
                  <div class="text-center mb-4">
                    <div class="w-16 h-16 bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                      <i class="fas fa-clipboard-check text-success fs-4"></i>
                    </div>
                    <h5 class="text-success mb-2">Final check and submit</h5>
                    <p class="text-muted">Review your proposal details before submitting for manager approval</p>
                  </div>

                  <!-- Approval Process Information -->
                  <div class="card border-info border-2">
                    <div class="card-header bg-info bg-opacity-10 border-info">
                      <h6 class="mb-0 text-info"><i class="fas fa-clock me-2"></i>Approval Process Timeline</h6>
                    </div>
                    <div class="card-body">
                      <div class="row text-center mb-4">
                        <div class="col-md-3 mb-3">
                          <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                            <i class="fas fa-paper-plane text-info fs-5"></i>
                          </div>
                          <h6 class="text-info mb-1">Submit</h6>
                          <p class="small text-muted mb-0">You submit proposal</p>
                        </div>
                        <div class="col-md-3 mb-3">
                          <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                            <i class="fas fa-search text-info fs-5"></i>
                          </div>
                          <h6 class="text-info mb-1">Review</h6>
                          <p class="small text-muted mb-0">Manager reviews within 24-48 hours</p>
                        </div>
                        <div class="col-md-3 mb-3">
                          <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                            <i class="fas fa-envelope text-info fs-5"></i>
                          </div>
                          <h6 class="text-info mb-1">Notify</h6>
                          <p class="small text-muted mb-0">You receive email updates</p>
                        </div>
                        <div class="col-md-3 mb-3">
                          <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px;">
                            <i class="fas fa-check-circle text-info fs-5"></i>
                          </div>
                          <h6 class="text-info mb-1">Confirm</h6>
                          <p class="small text-muted mb-0">Final confirmation & deposit</p>
                        </div>
                      </div>
                      
                      <div class="alert alert-info border-0 bg-info bg-opacity-10">
                        <h6 class="text-info mb-2"><i class="fas fa-info-circle me-2"></i>Important Notes:</h6>
                        <ul class="mb-0 small">
                          <li><strong>Manager reviews typically take 24-48 hours</strong> during business days</li>
                          <li>You'll receive email updates on your proposal status at every stage</li>
                          <li>Management may contact you for additional details or clarification</li>
                          <li><strong>Final confirmation is subject to venue availability</strong></li>
                          <li>No payment is required until your proposal is approved</li>
                        </ul>
                      </div>
                    </div>
                  </div>

                  <!-- Event Summary -->
                  <div class="card border-success border-2">
                    <div class="card-header bg-success bg-opacity-10 border-success d-flex justify-content-between align-items-center">
                      <h6 class="mb-0 text-success"><i class="fas fa-calendar-check me-2"></i>Event Summary</h6>
                      <button type="button" class="btn btn-outline-success btn-sm" onclick="printProposalSummary()">
                        <i class="fas fa-print me-1"></i>Print Summary
                      </button>
                    </div>
                    <div class="card-body">
                      <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6 mb-3">
                          <h6 class="text-success border-bottom pb-2 mb-3">Contact Information</h6>
                          <div class="mb-2">
                            <small class="text-muted d-block">Full Name</small>
                            <p class="fw-semibold mb-1" id="reviewFullName">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Email Address</small>
                            <p class="fw-semibold mb-1" id="reviewEmail">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Contact Number</small>
                            <p class="fw-semibold mb-1" id="reviewContactNumber">N/A</p>
                          </div>
                        </div>
                        
                        <!-- Event Details -->
                        <div class="col-md-6 mb-3">
                          <h6 class="text-success border-bottom pb-2 mb-3">Event Details</h6>
                          <div class="mb-2">
                            <small class="text-muted d-block">Event Title</small>
                            <p class="fw-semibold mb-1" id="reviewEventTitle">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Event Type</small>
                            <p class="fw-semibold mb-1" id="reviewEventType">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Arrival Date & Time</small>
                            <p class="fw-semibold mb-1" id="reviewArrivalDateTime">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Expected Guests</small>
                            <p class="fw-semibold mb-1" id="reviewGuests">N/A</p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <!-- Venue & Services -->
                        <div class="col-md-6 mb-3">
                          <h6 class="text-success border-bottom pb-2 mb-3">Venue & Services</h6>
                          <div class="mb-2">
                            <small class="text-muted d-block">Selected Venue</small>
                            <p class="fw-semibold mb-1" id="reviewVenue">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Event Time</small>
                            <p class="fw-semibold mb-1" id="reviewEventTime">N/A</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Catering</small>
                            <p class="fw-semibold mb-1" id="reviewCatering">No</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Additional Services</small>
                            <p class="fw-semibold mb-1" id="reviewAdditionalServices">None</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Decorations</small>
                            <p class="fw-semibold mb-1" id="reviewDecorations">None selected</p>
                          </div>
                        </div>
                        
                        <!-- Financial Information -->
                        <div class="col-md-6 mb-3">
                          <h6 class="text-success border-bottom pb-2 mb-3">Financial Information</h6>
                          <div class="mb-2">
                            <small class="text-muted d-block">Venue Cost</small>
                            <p class="fw-semibold mb-1" id="reviewVenueCost">₱0</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Additional Services</small>
                            <p class="fw-semibold mb-1" id="reviewServicesCost">₱0</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Catering Estimate</small>
                            <p class="fw-semibold mb-1" id="reviewCateringCost">₱0</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Total Estimated Cost</small>
                            <p class="fw-semibold mb-2 text-success fs-6" id="reviewTotalCost">₱0</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Your Budget</small>
                            <p class="fw-semibold mb-1 text-info" id="reviewBudget">₱0</p>
                          </div>
                          <div class="mb-2">
                            <small class="text-muted d-block">Payment Method</small>
                            <p class="fw-semibold mb-1" id="reviewPaymentMethod">N/A</p>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Additional Details -->
                      <div class="row">
                        <div class="col-12">
                          <h6 class="text-success border-bottom pb-2 mb-3">Additional Details</h6>
                          <div class="row">
                            <div class="col-md-6 mb-2">
                              <small class="text-muted d-block">Theme/Style</small>
                              <p class="fw-semibold mb-1" id="reviewTheme">Not specified</p>
                            </div>
                            <div class="col-md-6 mb-2">
                              <small class="text-muted d-block">Equipment Needed</small>
                              <p class="fw-semibold mb-1" id="reviewEquipment">None specified</p>
                            </div>
                            <div class="col-12 mb-2">
                              <small class="text-muted d-block">Event Description</small>
                              <p class="fw-semibold mb-1" id="reviewDescription">No description provided</p>
                            </div>
                            <div class="col-12 mb-2">
                              <small class="text-muted d-block">Special Requests</small>
                              <p class="fw-semibold mb-1" id="reviewSpecialRequests">No special requests</p>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Edit Options -->
                      <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="text-success mb-3"><i class="fas fa-edit me-2"></i>Need to make changes?</h6>
                        <div class="d-flex flex-wrap gap-2">
                          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showStep(0)">
                            <i class="fas fa-user me-1"></i>Edit Basic Info
                          </button>
                          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showStep(1)">
                            <i class="fas fa-calendar me-1"></i>Edit Event Details
                          </button>
                          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showStep(2)">
                            <i class="fas fa-concierge-bell me-1"></i>Edit Services
                          </button>
                          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showStep(3)">
                            <i class="fas fa-calculator me-1"></i>Edit Budget
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Final Submission -->
                  <div class="card border-warning border-2">
                    <div class="card-header bg-warning bg-opacity-10 border-warning">
                      <h6 class="mb-0 text-warning"><i class="fas fa-paper-plane me-2"></i>Ready to Submit</h6>
                    </div>
                    <div class="card-body">
                      <div class="alert alert-success border-success bg-success bg-opacity-10 mb-4">
                        <div class="d-flex align-items-center">
                          <i class="fas fa-check-circle text-success fs-4 me-3"></i>
                          <div>
                            <h6 class="text-success mb-1">✅ NO PAYMENT REQUIRED AT THIS STAGE</h6>
                            <p class="mb-0 text-success">You can submit your proposal without any payment. Payment will only be required after approval.</p>
                          </div>
                        </div>
                      </div>
                      
                      <div class="row align-items-center">
                        <div class="col-md-8">
                          <h6 class="text-success mb-2">What happens next?</h6>
                          <ul class="small text-muted mb-0">
                            <li>You'll receive a confirmation email with your proposal reference ID</li>
                            <li>Our event manager will review your proposal within 24-48 hours</li>
                            <li>You'll be notified via email about approval status and next steps</li>
                            <li>If approved, you'll receive deposit payment instructions</li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================= -->
              <!-- NAVIGATION BUTTONS                        -->
              <!-- ========================================= -->
              <div class="d-flex gap-3 mt-4">
                <button type="button" id="prevBtn" class="btn btn-outline-secondary flex-1 d-none">
                  Previous
                </button>
                <button type="button" id="nextBtn" class="btn-proposal flex-1">
                  Next
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
        Summary
      </h5>
      <div class="space-y-3 small">
        <div><p class="text-muted mb-1">Event Title</p><p class="fw-semibold text-truncate" id="summaryEventTitle">Not set</p></div>
        <div><p class="text-muted mb-1">Type</p><p class="fw-semibold" id="summaryEventType">Not set</p></div>
        <div><p class="text-muted mb-1">Arrival Date</p><p class="fw-semibold" id="summaryArrivalDate">Not set</p></div>
        <div><p class="text-muted mb-1">Arrival Time</p><p class="fw-semibold" id="summaryArrivalTime">Not set</p></div>
        <div><p class="text-muted mb-1">Guests</p><p class="fw-semibold" id="summaryGuests">Not set</p></div>
        
        <!-- Updated Venue section with View Details button -->
        <div>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <p class="text-muted mb-0">Venue</p>
            <button class="btn btn-outline-success btn-sm venue-details-btn" id="venueDetailsBtn" disabled onclick="showVenueDetailsModal()">
              <i class="fas fa-info-circle me-1"></i>View Details
            </button>
          </div>
          <p class="fw-semibold text-truncate" id="summaryVenue">Not selected</p>
        </div>
        
        <!-- Add Payment Method to Summary -->
        <div>
          <p class="text-muted mb-1">Payment Method</p>
          <p class="fw-semibold" id="summaryPaymentMethod">Not set</p>
        </div>
        
        <!-- Preliminary Cost Estimate -->
        <div class="pt-2 border-top">
            <p class="text-muted mb-1">Preliminary Estimate</p>
            <p class="fw-bold text-info fs-6" id="summaryPreliminaryCost">₱0</p>
            <small class="text-muted">Based on venue + catering + add-ons</small>
        </div>

        <div class="pt-3 border-top">
          <p class="text-muted mb-1">Your Budget</p>
          <p class="fw-bold text-success fs-5" id="summaryBudget">₱0</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Venue Details Modal -->
<div class="modal fade" id="venueDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0">
      <div class="modal-body p-0 position-relative">
        <div class="row g-0 h-100">
          <!-- Modal Image Section -->
          <div class="col-md-6">
            <div class="modal-image-container h-100">
              <div class="modal-background-image" id="modalVenueBackground"></div>
              <div class="modal-image-overlay"></div>
            </div>
          </div>
          <!-- Modal Content Section -->
          <div class="col-md-6">
            <div class="modal-content-section h-100 d-flex flex-column">
              <div class="modal-header border-0 pb-0">
                <h4 class="modal-title text-success" id="modalVenueName">Venue Name</h4>
              </div>
              <div class="modal-body-scrollable flex-grow-1">
                <p class="text-muted mb-3" id="modalVenueDescription">Venue description will appear here.</p>
                
                <div class="venue-quick-info bg-light rounded p-3 mb-3">
                  <div class="row g-2">
                    <div class="col-6">
                      <small class="text-muted d-block">Capacity</small>
                      <strong id="modalVenueCapacity">0-0 pax</strong>
                    </div>
                    <div class="col-6">
                      <small class="text-muted d-block">Duration</small>
                      <strong id="modalVenueDuration">0 hours</strong>
                    </div>
                    <div class="col-6">
                      <small class="text-muted d-block">Daytime Rate</small>
                      <strong class="text-success" id="modalDaytimeRate">₱0</strong>
                    </div>
                    <div class="col-6">
                      <small class="text-muted d-block">Nighttime Rate</small>
                      <strong class="text-success" id="modalNighttimeRate">₱0</strong>
                    </div>
                  </div>
                </div>
                
                <div class="package-features">
                  <h6 class="text-success mb-2">Package Includes:</h6>
                  <div class="features-grid" id="modalFeatures">
                    <!-- Features will be populated here -->
                  </div>
                </div>
              </div>
              <div class="modal-footer border-0 pt-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================= -->
<!-- VALIDATION MODAL                          -->
<!-- ========================================= -->
<div class="modal fade" id="validationModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-5">
        <div class="w-16 h-16 bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4">
          <i class="fas fa-exclamation-triangle text-danger fs-2"></i>
        </div>
        <h3 class="fw-bold text-primary mb-3" id="validationModalTitle">Attention Required</h3>
        <p class="text-muted mb-4" id="validationModalMessage">
          Please complete the required fields before proceeding.
        </p>
        <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">
          Okay
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================= -->
<!-- NAVIGATION RESTRICTION MODAL              -->
<!-- ========================================= -->
<div class="modal fade" id="navigationRestrictionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-5">
        <div class="w-16 h-16 bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4">
          <i class="fas fa-exclamation-circle text-danger fs-2"></i>
        </div>
        <h3 class="fw-bold text-primary mb-3">Complete Current Step First</h3>
        <p class="text-muted mb-4" id="navigationRestrictionMessage">
          Please click the "Next" button to save your progress.
        </p>
        <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">
          Okay
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================= -->
<!-- CONFIRMATION MODAL                        -->
<!-- ========================================= -->
<div class="modal fade" id="confirmationModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-5">
        <div class="w-16 h-16 bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4">
          <i class="fas fa-clock text-info fs-2"></i>
        </div>
        <h3 class="fw-bold text-info mb-3">Proposal Submitted for Review!</h3>
        <p class="text-muted mb-4">
          Thank you! Your proposal has been successfully submitted. Our Event Manager will review your details and contact you within 48 hours via email to confirm availability and discuss the next steps (Deposit Payment).
        </p>
        <div class="bg-light border rounded p-3 mb-4">
          <p class="text-muted small mb-2"><strong>What to expect next:</strong></p>
          <ul class="text-muted small text-start mb-0">
            <li>Event Manager reviews your proposal (within 48 hours)</li>
            <li>Venue availability confirmation</li>
            <li>Email notification with next steps</li>
            <li>Deposit payment instructions</li>
            <li>Final booking confirmation</li>
          </ul>
        </div>
        <div class="bg-info bg-opacity-10 border border-info border-opacity-20 rounded p-3 mb-4">
          <p class="text-muted small mb-1">Proposal Reference ID</p>
          <p class="fw-semibold" id="proposalId"></p>
        </div>
        <button type="button" class="btn btn-info w-100" data-bs-dismiss="modal">
          <i class="fas fa-check me-2"></i>Understood
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================= -->
<!-- JAVASCRIPT LOGIC                          -->
<!-- ========================================= -->
<script>
  // Step configuration
  const steps = [
    { id: 'basic', label: 'Basic Info', description: 'Complete this section to proceed' },
    { id: 'details', label: 'Event Details', description: 'Provide event specifics' },
    { id: 'requirements', label: 'Requirements', description: 'Select additional services' },
    { id: 'budget', label: 'Budget', description: 'Set your budget and payment' },
    { id: 'review', label: 'Review & Submit', description: 'Verify details and submit for manager approval' }
  ];

  // Step validation configuration
  const stepValidation = {
    basic: {
      fields: ['fullName', 'email', 'contactNumber'],
      required: ['contactNumber'],
      customValidation: {
        'contactNumber': function(value) {
          if (!value.trim()) {
            return 'Contact number is required';
          }
          if (!validateContactNumber(value)) {
            return 'Please enter a valid phone number (10-15 digits)';
          }
          return null;
        }
      }
    },
    details: {
      fields: ['eventTitle', 'eventType', 'arrivalDate', 'arrivalTime', 'expectedGuests', 'venuePreference'],
      required: ['eventTitle', 'eventType', 'arrivalDate', 'arrivalTime', 'expectedGuests', 'venuePreference'],
      customValidation: {
        'expectedGuests': function(value) {
          if (!value.trim()) {
            return 'Number of guests is required';
          }
          const guests = parseInt(value);
          if (isNaN(guests) || guests < 1) {
            return 'Please enter a valid number of guests';
          }
          if (guests > 500) {
            return 'Maximum 500 guests allowed. Contact us for larger events';
          }
          return null;
        },
        'arrivalDate': function(value) {
          if (!value.trim()) {
            return 'Arrival date is required';
          }
          const selectedDate = new Date(value);
          const today = new Date();
          today.setHours(0, 0, 0, 0);
          
          if (selectedDate < today) {
            return 'Please select a future date';
          }
          return null;
        }
      }
    },
    requirements: {
      fields: ['cateringRequest', 'decorations', 'equipmentNeeded', 'specialRequests'],
      required: ['cateringRequest'],
      customValidation: {
        'cateringDetails': function(value) {
          const cateringRequest = document.querySelector('input[name="cateringRequest"]:checked');
          if (cateringRequest?.value === 'yes' && !value.trim()) {
            return 'Please provide catering details since you selected "Yes" for catering';
          }
          return null;
        }
      }
    },
    budget: {
      fields: ['estimatedBudget', 'paymentMethod'],
      required: ['paymentMethod'],
      customValidation: {
        'estimatedBudget': function(value) {
          if (value && parseInt(value) < 1000) {
            return 'Please enter a budget of at least ₱1,000';
          }
          return null;
        }
      }
    },
    review: {
      fields: [],
      required: []
    }
  };

  const venueData = {
    "Pavillion Old": {
      image: "https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=400&h=500&fit=crop",
      description: "A classic venue perfect for intimate gatherings and corporate events with a traditional charm.",
      capacity: "50-80 pax",
      duration: "4 hours",
      daytimeRate: "₱8,000",
      nighttimeRate: "₱12,000",
      features: ["A/C", "Sound System", "Stage Setup", "Basic Lighting", "Tables & Chairs"]
    },
    "Pavillion Anex": {
      image: "https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=400&h=500&fit=crop",
      description: "A spacious garden venue ideal for larger celebrations and outdoor events.",
      capacity: "100-150 pax",
      duration: "4 hours",
      daytimeRate: "₱14,000",
      nighttimeRate: "₱16,000",
      features: ["Garden Setup", "Sound System", "Basic Lighting", "Garden Chairs", "Decorative Elements"]
    },
    "Pavillion Whole": {
      image: "https://images.unsplash.com/photo-1497366216548-37526070297c?w=400&h=500&fit=crop",
      description: "Our largest venue combining modern amenities with elegant design for grand events.",
      capacity: "200-250 pax",
      duration: "4 hours",
      daytimeRate: "₱18,000",
      nighttimeRate: "₱20,000",
      features: ["A/C", "Projector", "Sound System", "Tables", "Premium Lighting", "Stage"]
    },
    "Mabuhay Lounge": {
      image: "https://images.unsplash.com/photo-1566073771259-6a8506099945?w=400&h=500&fit=crop",
      description: "A luxurious lounge area with pool access, perfect for sophisticated gatherings.",
      capacity: "50-70 pax",
      duration: "4 hours",
      airconRate: "₱9,500",
      nonAirconRate: "₱7,500",
      features: ["Pool Access", "Sound System", "Lighting", "Lounging Area", "Premium Seating"]
    }
  };

  let currentStep = 0;
  let maxStepReached = 0;
  let completedSteps = new Set(['basic']);

  // =========================================
  // INITIALIZATION
  // =========================================
  document.addEventListener('DOMContentLoaded', function() {
    showStep(currentStep);
    setupEventListeners();
    setupDynamicFields();
  });

  // =========================================
  // EVENT LISTENERS SETUP
  // =========================================
  function setupEventListeners() {
    document.getElementById('prevBtn').addEventListener('click', handlePrev);
    
    // Enhanced next button click with validation
    document.getElementById('nextBtn').addEventListener('click', function() {
      if (validateCurrentStep()) {
        saveCurrentStepProgress();
        handleNext();
      }
    });

    // Step circle navigation - Free navigation between visited steps
    document.querySelectorAll('.step-circle').forEach(circle => {
      circle.addEventListener('click', function() {
        const stepIndex = steps.findIndex(s => s.id === this.dataset.step);
        if (stepIndex !== -1 && stepIndex !== currentStep) {
          handleStepNavigation(stepIndex);
        }
      });
    });

    // Step 2: Time preference selection
    document.querySelectorAll('input[name="eventTime"]').forEach(radio => {
      radio.addEventListener('change', function() {
        updateSummary();
        calculatePreliminaryCost();
        updateCostBreakdown();
      });
    });

    // Step 3: Catering toggle
    document.querySelectorAll('input[name="cateringRequest"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const cateringDetails = document.getElementById('cateringDetails');
        cateringDetails.classList.toggle('d-none', this.value === 'no');
        
        if (this.value === 'yes') {
          cateringDetails.querySelector('textarea').focus();
        }
        
        updateSummary();
        validateCurrentStep();
        calculatePreliminaryCost();
        updateCostBreakdown();
        updateAdditionalServicesCost();
      });
    });

    // Step 3: Add-on checkboxes
    document.querySelectorAll('.addon-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        updateAdditionalServicesCost();
        calculatePreliminaryCost();
        updateCostBreakdown();
        updateSummary();
      });
    });

    // Step 3: Decorations
    document.querySelectorAll('.decoration-check').forEach(cb => cb.addEventListener('change', updateDecorations));

    // Step 4: Budget input
    const budgetInput = document.getElementById('estimatedBudgetInput');
    budgetInput.addEventListener('focus', function() {
      if (!this.value) {
        const calculatedCost = calculatePreliminaryCost();
        this.value = calculatedCost;
        updateBudgetComparison();
      }
    });
    
    budgetInput.addEventListener('input', function() {
      updateBudgetComparison();
      updateSummary();
    });

    // Step 4: Payment method change
    document.querySelector('select[name="paymentMethod"]').addEventListener('change', function() {
      updateSummary();
      validateCurrentStep();
    });

    // Real-time updates with validation
    document.querySelectorAll('#eventForm input, #eventForm select, #eventForm textarea').forEach(el => {
      if (el.name !== 'venuePreference') {
        el.addEventListener('blur', validateField);
      }
      el.addEventListener('input', function() {
        if (this.classList.contains('field-error')) {
          clearFieldError(this);
        }
        updateSummary();
        
        const currentStepId = steps[currentStep].id;
        markStepCompleted(currentStepId);
      });
      el.addEventListener('change', function() {
        updateSummary();
        const currentStepId = steps[currentStep].id;
        markStepCompleted(currentStepId);
      });
    });

    // Real-time cost calculation triggers
    document.querySelector('input[name="expectedGuests"]').addEventListener('input', function() {
      updateSummary();
      calculatePreliminaryCost();
      updateCostBreakdown();
    });
  }

  // =========================================
  // DYNAMIC "OTHER" FIELD HANDLING
  // =========================================
  function setupDynamicFields() {
    // Event Type "Other" handling
    const eventTypeSelect = document.getElementById('eventTypeSelect');
    const otherEventTypeContainer = document.getElementById('otherEventTypeContainer');
    const otherEventTypeInput = document.getElementById('otherEventTypeInput');
    
    eventTypeSelect.addEventListener('change', function() {
      if (this.value === 'Other') {
        otherEventTypeContainer.classList.remove('d-none');
        otherEventTypeInput.required = true;
      } else {
        otherEventTypeContainer.classList.add('d-none');
        otherEventTypeInput.required = false;
        otherEventTypeInput.value = '';
      }
      validateCurrentStep();
      updateSummary();
    });
    
    // Theme "Other" handling
    const themeSelect = document.getElementById('themeSelect');
    const otherThemeContainer = document.getElementById('otherThemeContainer');
    const otherThemeInput = document.getElementById('otherThemeInput');
    
    themeSelect.addEventListener('change', function() {
      if (this.value === 'Other') {
        otherThemeContainer.classList.remove('d-none');
        otherThemeInput.required = true;
      } else {
        otherThemeContainer.classList.add('d-none');
        otherThemeInput.required = false;
        otherThemeInput.value = '';
      }
      validateCurrentStep();
      updateSummary();
    });
  }

  // =========================================
  // VENUE SELECTION FUNCTION
  // =========================================
  function selectVenue(element, venueName) {
    // Remove selection from all venues
    document.querySelectorAll('.venue-option').forEach(opt => {
      opt.classList.remove('selected');
      opt.querySelector('.venue-content').classList.remove('bg-success', 'bg-opacity-10');
    });
    
    // Add selection to clicked venue
    element.classList.add('selected');
    element.querySelector('.venue-content').classList.add('bg-success', 'bg-opacity-10');
    
    // Set the hidden input value
    document.getElementById('selectedVenue').value = venueName;
    
    // Handle venue-specific UI changes
    handleVenueSpecificUI(venueName);
    
    // Update summary and validation
    updateSummary();
    validateCurrentStep();
    updateVenueDetailsButton();
    calculatePreliminaryCost();
    updateCostBreakdown();
  }

  function handleVenueSpecificUI(venueName) {
    const timeToggle = document.querySelector('.time-toggle');
    
    if (venueName === "Mabuhay Lounge") {
        // Hide the time toggle for Mabuhay Lounge
        timeToggle.style.display = 'none';
    } else {
        // Show time toggle for other venues
        timeToggle.style.display = 'block';
    }
  }

  // =========================================
  // COST CALCULATION FUNCTIONS
  // =========================================
  function calculatePreliminaryCost() {
    const venueCost = getVenueCost();
    const cateringCost = getCateringCost();
    const additionalServicesCost = getAdditionalServicesCost();
    const totalCost = venueCost + cateringCost + additionalServicesCost;
    
    // Update summary sidebar
    document.getElementById('summaryPreliminaryCost').textContent = '₱' + totalCost.toLocaleString();
    
    // Update budget step if visible
    if (currentStep === steps.findIndex(s => s.id === 'budget')) {
        updateBudgetStepDisplay(totalCost);
    }
    
    return totalCost;
  }

  function getVenueCost() {
    const selectedVenue = document.getElementById('selectedVenue').value;
    const timePreference = document.querySelector('input[name="eventTime"]:checked')?.value;
    
    if (!selectedVenue) return 0;
    
    const venueCosts = {
        "Pavillion Old": { daytime: 8000, nighttime: 12000 },
        "Pavillion Anex": { daytime: 14000, nighttime: 16000 },
        "Pavillion Whole": { daytime: 18000, nighttime: 20000 },
        "Mabuhay Lounge": { aircon: 9500, nonAircon: 7500 }
    };
    
    if (selectedVenue === "Mabuhay Lounge") {
        // For Mabuhay Lounge, use the higher rate as preliminary estimate
        return venueCosts[selectedVenue].aircon;
    } else {
        return timePreference === 'night' ? 
            venueCosts[selectedVenue].nighttime : 
            venueCosts[selectedVenue].daytime;
    }
  }

  function getCateringCost() {
    const cateringRequest = document.querySelector('input[name="cateringRequest"]:checked');
    if (cateringRequest?.value === 'yes') {
        const guestCount = parseInt(document.querySelector('input[name="expectedGuests"]').value) || 0;
        // Estimated catering cost: PHP 500 per person
        return guestCount * 500;
    }
    return 0;
  }

  function getAdditionalServicesCost() {
    let total = 0;
    
    // Air Conditioning
    const airconCheckbox = document.getElementById('addonAircon');
    if (airconCheckbox?.checked) {
        total += parseInt(airconCheckbox.value);
    }
    
    // Corkage Fee
    const corkageCheckbox = document.getElementById('addonCorkage');
    if (corkageCheckbox?.checked) {
        total += parseInt(corkageCheckbox.value);
    }
    
    return total;
  }

  function updateCostBreakdown() {
    const venueCost = getVenueCost();
    const cateringCost = getCateringCost();
    const additionalServicesCost = getAdditionalServicesCost();
    const totalCost = venueCost + cateringCost + additionalServicesCost;
    
    // Get individual add-on costs
    const airconCost = document.getElementById('addonAircon')?.checked ? parseInt(document.getElementById('addonAircon').value) : 0;
    const corkageCost = document.getElementById('addonCorkage')?.checked ? parseInt(document.getElementById('addonCorkage').value) : 0;
    
    // Update cost breakdown display
    document.getElementById('costBreakdownVenue').textContent = '₱' + venueCost.toLocaleString();
    document.getElementById('costBreakdownCatering').textContent = '₱' + cateringCost.toLocaleString();
    document.getElementById('costBreakdownAircon').textContent = '₱' + airconCost.toLocaleString();
    document.getElementById('costBreakdownCorkage').textContent = '₱' + corkageCost.toLocaleString();
    document.getElementById('costBreakdownTotal').textContent = '₱' + totalCost.toLocaleString();
    
    // Update calculated estimate display
    document.getElementById('calculatedEstimateDisplay').textContent = '₱' + totalCost.toLocaleString();
    
    // Update summary sidebar
    document.getElementById('summaryPreliminaryCost').textContent = '₱' + totalCost.toLocaleString();
    
    // Update budget comparison if budget is set
    updateBudgetComparison();
    
    return totalCost;
  }

  function updateAdditionalServicesCost() {
    let total = 0;
    
    // Air Conditioning
    const airconCheckbox = document.getElementById('addonAircon');
    const airconCost = airconCheckbox.checked ? parseInt(airconCheckbox.value) : 0;
    document.getElementById('addonAirconCost').textContent = `₱${airconCost.toLocaleString()}`;
    total += airconCost;
    
    // Corkage Fee
    const corkageCheckbox = document.getElementById('addonCorkage');
    const corkageCost = corkageCheckbox.checked ? parseInt(corkageCheckbox.value) : 0;
    document.getElementById('addonCorkageCost').textContent = `₱${corkageCost.toLocaleString()}`;
    total += corkageCost;
    
    // Update total
    document.getElementById('additionalServicesTotal').textContent = `₱${total.toLocaleString()}`;
    
    return total;
  }

  function updateBudgetStepDisplay(calculatedCost) {
    const budgetHelpText = document.querySelector('input[name="estimatedBudget"] + .form-text');
    
    if (budgetHelpText) {
        budgetHelpText.innerHTML = `
            <div class="mb-2">
                <strong>Calculated Estimate:</strong> <span id="calculatedEstimateDisplay" class="text-success">₱${calculatedCost.toLocaleString()}</span>
            </div>
            <div class="text-muted">
                Your estimated budget helps our manager understand your spending expectations. 
                Final pricing may vary based on specific requirements.
            </div>
        `;
    }
  }

  function updateBudgetComparison() {
    const budgetInput = document.getElementById('estimatedBudgetInput');
    const budgetComparison = document.getElementById('budgetComparison');
    const budgetWarning = document.getElementById('budgetWarning');
    
    const estimatedCost = calculatePreliminaryCost();
    const userBudget = parseInt(budgetInput.value) || 0;
    
    if (userBudget > 0) {
        budgetComparison.classList.remove('d-none');
        
        document.getElementById('yourBudgetAmount').textContent = '₱' + userBudget.toLocaleString();
        document.getElementById('estimatedCostAmount').textContent = '₱' + estimatedCost.toLocaleString();
        
        const difference = userBudget - estimatedCost;
        const differenceElement = document.getElementById('budgetDifference');
        
        if (difference >= 0) {
            differenceElement.textContent = '₱+' + difference.toLocaleString();
            differenceElement.className = 'text-success';
            budgetWarning.classList.add('d-none');
        } else {
            differenceElement.textContent = '₱' + difference.toLocaleString();
            differenceElement.className = 'text-danger';
            budgetWarning.classList.remove('d-none');
        }
    } else {
        budgetComparison.classList.add('d-none');
    }
  }

  // =========================================
  // NAVIGATION FUNCTIONS
  // =========================================
  function handleStepNavigation(targetStepIndex) {
    const targetStepCompleted = completedSteps.has(steps[targetStepIndex].id);
    const isWithinReachedSteps = targetStepIndex <= maxStepReached;
    
    if (isWithinReachedSteps || targetStepCompleted) {
      showStep(targetStepIndex);
    } else {
      showNavigationRestrictionModal(targetStepIndex);
    }
  }

  function showNavigationRestrictionModal(targetStepIndex) {
    const modalMessage = document.getElementById('navigationRestrictionMessage');
    
    modalMessage.textContent = `Please complete Step ${currentStep + 1} (${steps[currentStep].label}) and click the "Next" button before proceeding to Step ${targetStepIndex + 1}.`;
    
    const modal = new bootstrap.Modal(document.getElementById('navigationRestrictionModal'));
    modal.show();
  }

  function saveCurrentStepProgress() {
    if (validateCurrentStep()) {
      markStepCompleted(steps[currentStep].id);
      return true;
    }
    return false;
  }

  function showStep(stepIndex) {
    if (stepIndex < 0 || stepIndex >= steps.length) return;
    
    currentStep = stepIndex;
    
    // Update maxStepReached if we're going to a new furthest step
    if (stepIndex > maxStepReached) {
      maxStepReached = stepIndex;
    }

    document.querySelectorAll('.form-step').forEach(step => step.classList.remove('step-active'));
    document.getElementById('step-' + steps[stepIndex].id)?.classList.add('step-active');

    document.getElementById('stepTitle').textContent = steps[stepIndex].label;
    document.getElementById('stepDescription').textContent = steps[stepIndex].description;

    const prevBtn = document.getElementById('prevBtn');
    prevBtn.classList.toggle('d-none', currentStep === 0);

    document.getElementById('nextBtn').innerHTML = currentStep === steps.length - 1 
      ? 'Submit for Manager Review' 
      : 'Next<i class="fas fa-chevron-right ms-2"></i>';

    updateProgress();
    updateSummary();
    
    // Update review when showing Step 5
    if (currentStep === steps.length - 1) {
        updateReview();
    }
    
    // Update cost calculations when showing budget step
    if (currentStep === steps.findIndex(s => s.id === 'budget')) {
        calculatePreliminaryCost();
        updateCostBreakdown();
        updateBudgetComparison();
    }
  }

  function updateProgress() {
    const progressBar = document.getElementById('progressBar');
    const completedCount = Array.from(completedSteps).length;
    const progressPercentage = (completedCount / steps.length) * 100;
    
    progressBar.style.width = progressPercentage + '%';
    
    // Update step circles - show numbers until actually completed
    document.querySelectorAll('.step-circle').forEach((circle, index) => {
      const stepId = circle.dataset.step;
      
      // Only show as active if it's the current or previous step
      circle.classList.toggle('active', index <= currentStep);
      
      // Only show as completed if ALL required fields are filled AND user clicked Next
      circle.classList.toggle('completed', completedSteps.has(stepId));
      
      // Update labels
      const label = document.querySelectorAll('.progress-labels span')[index];
      if (label) {
        if (completedSteps.has(stepId)) {
          label.classList.add('text-success');
          label.classList.remove('text-muted');
        } else if (index === currentStep) {
          label.classList.add('text-success');
          label.classList.remove('text-muted');
        } else {
          label.classList.add('text-muted');
          label.classList.remove('text-success');
        }
      }
    });
  }

  function handleNext() { 
    if (validateCurrentStep()) {
      saveCurrentStepProgress();
      
      // Update maxStepReached when moving forward
      if (currentStep + 1 > maxStepReached) {
        maxStepReached = currentStep + 1;
      }
      
      currentStep < steps.length - 1 ? showStep(currentStep + 1) : submitProposal(); 
    }
  }
  
  function handlePrev() { 
    if (currentStep > 0) {
      showStep(currentStep - 1);
    }
  }

  // =========================================
  // SUMMARY & REVIEW FUNCTIONS
  // =========================================
  function updateSummary() {
    const f = document.getElementById('eventForm');
    if (!f) return;
    
    document.getElementById('summaryEventTitle').textContent = f.eventTitle?.value || 'Not set';
    
    // Handle event type (show "other" value if specified)
    let eventTypeDisplay = f.eventType?.value || 'Not set';
    if (eventTypeDisplay === 'Other' && f.otherEventType?.value) {
      eventTypeDisplay = f.otherEventType.value;
    }
    document.getElementById('summaryEventType').textContent = eventTypeDisplay;
    
    document.getElementById('summaryArrivalDate').textContent = f.arrivalDate?.value || 'Not set';
    
    // Format arrival time
    const arrivalTime = f.arrivalTime?.value || '';
    if (arrivalTime) {
      document.getElementById('summaryArrivalTime').textContent = formatTime(arrivalTime);
    } else {
      document.getElementById('summaryArrivalTime').textContent = 'Not set';
    }
    
    document.getElementById('summaryGuests').textContent = f.expectedGuests?.value || 'Not set';
    document.getElementById('summaryVenue').textContent = document.getElementById('selectedVenue')?.value || 'Not selected';
    
    // Add Payment Method to Summary
    document.getElementById('summaryPaymentMethod').textContent = f.paymentMethod?.value || 'Not set';
    
    const budget = parseInt(f.estimatedBudget?.value) || 0;
    document.getElementById('summaryBudget').textContent = '₱' + budget.toLocaleString();
    
    // Update venue details button state
    updateVenueDetailsButton();
  }

  function updateReview() {
    const f = document.getElementById('eventForm');
    if (!f) return;
    
    // Contact Information
    document.getElementById('reviewFullName').textContent = f.fullName?.value || 'N/A';
    document.getElementById('reviewEmail').textContent = f.email?.value || 'N/A';
    document.getElementById('reviewContactNumber').textContent = f.contactNumber?.value || 'N/A';
    
    // Event Details
    document.getElementById('reviewEventTitle').textContent = f.eventTitle?.value || 'N/A';
    
    // Handle event type (show "other" value if specified)
    let eventTypeDisplay = f.eventType?.value || 'N/A';
    if (eventTypeDisplay === 'Other' && f.otherEventType?.value) {
      eventTypeDisplay = f.otherEventType.value;
    }
    document.getElementById('reviewEventType').textContent = eventTypeDisplay;
    
    // Format arrival date and time
    const arrivalDate = f.arrivalDate?.value || '';
    const arrivalTime = f.arrivalTime?.value || '';
    if (arrivalDate && arrivalTime) {
      const formattedDate = formatDate(arrivalDate);
      const formattedTime = formatTime(arrivalTime);
      document.getElementById('reviewArrivalDateTime').textContent = `${formattedDate} at ${formattedTime}`;
    } else {
      document.getElementById('reviewArrivalDateTime').textContent = 'N/A';
    }
    
    document.getElementById('reviewGuests').textContent = (f.expectedGuests?.value || 'N/A') + ' guests';
    
    // Venue & Services
    document.getElementById('reviewVenue').textContent = document.getElementById('selectedVenue')?.value || 'N/A';
    
    const eventTime = document.querySelector('input[name="eventTime"]:checked');
    document.getElementById('reviewEventTime').textContent = eventTime ? 
        (eventTime.value === 'day' ? 'Day Time' : 'Night Time') : 'N/A';
    
    const catering = document.querySelector('input[name="cateringRequest"]:checked');
    document.getElementById('reviewCatering').textContent = catering?.value === 'yes' ? 'Yes' : 'No';
    
    // Additional Services
    const additionalServices = [];
    if (document.getElementById('addonAircon')?.checked) {
        additionalServices.push('Air Conditioning');
    }
    if (document.getElementById('addonCorkage')?.checked) {
        additionalServices.push('Outside Catering Corkage');
    }
    document.getElementById('reviewAdditionalServices').textContent = 
        additionalServices.length > 0 ? additionalServices.join(', ') : 'None';
    
    // Decorations
    const selectedDecorations = Array.from(document.querySelectorAll('.decoration-check:checked')).map(cb => cb.value);
    document.getElementById('reviewDecorations').textContent = 
        selectedDecorations.length > 0 ? selectedDecorations.join(', ') : 'None selected';
    
    // Financial Information
    const venueCost = getVenueCost();
    const cateringCost = getCateringCost();
    const additionalServicesCost = getAdditionalServicesCost();
    const totalCost = venueCost + cateringCost + additionalServicesCost;
    
    document.getElementById('reviewVenueCost').textContent = '₱' + venueCost.toLocaleString();
    document.getElementById('reviewServicesCost').textContent = '₱' + additionalServicesCost.toLocaleString();
    document.getElementById('reviewCateringCost').textContent = '₱' + cateringCost.toLocaleString();
    document.getElementById('reviewTotalCost').textContent = '₱' + totalCost.toLocaleString();
    
    const budget = parseInt(f.estimatedBudget?.value) || 0;
    document.getElementById('reviewBudget').textContent = '₱' + budget.toLocaleString();
    
    document.getElementById('reviewPaymentMethod').textContent = f.paymentMethod?.value || 'N/A';
    
    // Additional Details
    let themeDisplay = f.theme?.value || 'Not specified';
    if (themeDisplay === 'Other' && f.otherTheme?.value) {
      themeDisplay = f.otherTheme.value;
    }
    document.getElementById('reviewTheme').textContent = themeDisplay;
    
    document.getElementById('reviewEquipment').textContent = f.equipmentNeeded?.value || 'None specified';
    document.getElementById('reviewDescription').textContent = f.description?.value || 'No description provided';
    document.getElementById('reviewSpecialRequests').textContent = f.specialRequests?.value || 'No special requests';
  }

  function formatDate(dateString) {
    if (!dateString) return 'Not set';
    try {
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      return new Date(dateString).toLocaleDateString('en-US', options);
    } catch (e) {
      return 'Invalid date';
    }
  }

  function formatTime(timeString) {
    if (!timeString) return 'Not set';
    try {
      const [hours, minutes] = timeString.split(':');
      const hour = parseInt(hours);
      const ampm = hour >= 12 ? 'PM' : 'AM';
      const formattedHour = hour % 12 || 12;
      return `${formattedHour}:${minutes} ${ampm}`;
    } catch (e) {
      return 'Invalid time';
    }
  }

  function updateDecorations() {
    const selected = Array.from(document.querySelectorAll('.decoration-check:checked')).map(cb => cb.value);
    document.getElementById('selectedDecorations').value = JSON.stringify(selected);
    updateSummary();
  }

  // =========================================
  // VALIDATION FUNCTIONS
  // =========================================
  function validateContactNumber(phone) {
    const phoneRegex = /^[0-9+]{10,15}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
  }

  function validateField() {
    const field = this;
    const fieldName = field.name;
    const currentStepId = steps[currentStep].id;
    const validation = stepValidation[currentStepId];
    
    if (validation?.required.includes(fieldName)) {
      const value = field.value.trim();
      
      // Check custom validation if exists
      if (validation.customValidation && validation.customValidation[fieldName]) {
        const error = validation.customValidation[fieldName](value);
        if (error) {
          showFieldError(field, error);
          return false;
        }
      } else if (!value) {
        showFieldError(field, 'This field is required');
        return false;
      }
      
      showFieldValid(field);
      return true;
    }
    return true;
  }

  function validateCurrentStep() {
    const currentStepId = steps[currentStep].id;
    const validation = stepValidation[currentStepId];
    let isValid = true;
    
    if (validation) {
        validation.required.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            
            // Special handling for venue preference
            if (fieldName === 'venuePreference') {
                const venueValue = document.getElementById('selectedVenue').value;
                if (!venueValue || venueValue === '') {
                    showStepValidationMessage(currentStep, '📢 Please click on a venue card to select your preference.');
                    isValid = false;
                    
                    // Enhanced visual feedback for venue selection
                    const venueSelector = document.querySelector('.venue-selector');
                    if (venueSelector) {
                        venueSelector.style.border = '2px solid #dc3545';
                        venueSelector.style.borderRadius = '8px';
                        venueSelector.style.padding = '8px';
                        venueSelector.style.backgroundColor = '#fff5f5';
                    }
                }
            } 
            // Regular field validation
            else if (field && !field.value.trim()) {
                showFieldError(field, 'This field is required');
                isValid = false;
            }
        });
        
        // Custom validation
        if (validation.customValidation) {
            Object.keys(validation.customValidation).forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field && field.value.trim()) {
                    const error = validation.customValidation[fieldName](field.value);
                    if (error) {
                        showFieldError(field, error);
                        isValid = false;
                    }
                }
            });
        }
    }
    
    if (isValid) {
        markStepCompleted(currentStepId);
        enableNextStep(currentStepId);
        
        // Remove any venue selection highlighting
        const venueSelector = document.querySelector('.venue-selector');
        if (venueSelector) {
            venueSelector.style.border = '';
            venueSelector.style.padding = '';
            venueSelector.style.backgroundColor = '';
        }
    } else {
        completedSteps.delete(currentStepId);
        const stepCircle = document.querySelector(`.step-circle[data-step="${currentStepId}"]`);
        if (stepCircle) {
            stepCircle.classList.remove('completed');
        }
        updateProgress();
    }
    
    return isValid;
}

  function validateAllSteps() {
    let allValid = true;
    
    // Check each step's required fields
    for (let i = 0; i < steps.length - 1; i++) {
      const stepId = steps[i].id;
      const validation = stepValidation[stepId];
      
      if (validation) {
        validation.required.forEach(fieldName => {
          const field = document.querySelector(`[name="${fieldName}"]`);
          if (field && !field.value.trim()) {
            allValid = false;
            // Show which step has issues
            showStepValidationMessage(i, `Please complete all required fields in ${steps[i].label}`);
          }
        });
      }
    }
    
    if (!allValid) {
      const modalTitle = document.getElementById('validationModalTitle');
      const modalMessage = document.getElementById('validationModalMessage');
      modalTitle.textContent = 'Incomplete Proposal';
      modalMessage.textContent = 'Please complete all required fields in previous steps before submitting.';
      
      const modal = new bootstrap.Modal(document.getElementById('validationModal'));
      modal.show();
    }
    
    return allValid;
  }

  function markStepCompleted(stepId) {
    const validation = stepValidation[stepId];
    let isStepComplete = true;
    
    // No progress without proper completion
    if (validation) {
      validation.required.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field && !field.value.trim()) {
          isStepComplete = false;
        }
      });
    }
    
    // Checkmarks only when properly completed AND saved via Next
    if (isStepComplete) {
      completedSteps.add(stepId);
      const stepCircle = document.querySelector(`.step-circle[data-step="${stepId}"]`);
      
      if (stepCircle) {
        stepCircle.classList.add('completed');
        stepCircle.classList.remove('error', 'warning');
      }
    } else {
      // Remove from completed steps if not complete
      completedSteps.delete(stepId);
      const stepCircle = document.querySelector(`.step-circle[data-step="${stepId}"]`);
      if (stepCircle) {
        stepCircle.classList.remove('completed');
      }
    }
    
    updateProgress();
  }

  function enableNextStep(completedStepId) {
    const currentIndex = steps.findIndex(s => s.id === completedStepId);
    if (currentIndex < steps.length - 1) {
      const nextStepId = steps[currentIndex + 1].id;
      const nextStepCircle = document.querySelector(`.step-circle[data-step="${nextStepId}"]`);
      
      if (nextStepCircle) {
        nextStepCircle.classList.remove('error', 'warning');
      }
    }
  }

  function showFieldError(field, message) {
    field.classList.add('field-error');
    field.classList.remove('field-valid');
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.validation-message.error');
    if (existingError) existingError.remove();
    
    // Add new error message
    const errorMessage = document.createElement('div');
    errorMessage.className = 'validation-message error';
    errorMessage.textContent = message;
    field.parentNode.appendChild(errorMessage);
    
    // Mark step as having errors
    const currentStepId = steps[currentStep].id;
    const stepCircle = document.querySelector(`.step-circle[data-step="${currentStepId}"]`);
    if (stepCircle) {
      stepCircle.classList.add('error');
      stepCircle.classList.remove('completed');
    }
  }

  function showFieldValid(field) {
    field.classList.add('field-valid');
    field.classList.remove('field-error');
    
    // Remove error message if exists
    const existingError = field.parentNode.querySelector('.validation-message.error');
    if (existingError) existingError.remove();
  }

  function clearFieldError(field) {
    field.classList.remove('field-error');
    const existingError = field.parentNode.querySelector('.validation-message.error');
    if (existingError) existingError.remove();
  }

  function showStepValidationMessage(stepIndex, customMessage = null) {
    const stepId = steps[stepIndex].id;
    const stepCircle = document.querySelector(`.step-circle[data-step="${stepId}"]`);
    
    if (stepCircle) {
      stepCircle.classList.add('error');
      
      // Remove error class after animation
      setTimeout(() => {
        stepCircle.classList.remove('error');
      }, 2000);
    }
    
    // Use modal instead of alert
    const modalTitle = document.getElementById('validationModalTitle');
    const modalMessage = document.getElementById('validationModalMessage');
    
    if (customMessage) {
      modalTitle.textContent = 'Complete Required Fields';
      modalMessage.textContent = customMessage;
    } else {
      modalTitle.textContent = `Step ${stepIndex + 1} Required`;
      modalMessage.textContent = `Please complete all required fields in ${steps[stepIndex].label} before proceeding to this step.`;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('validationModal'));
    modal.show();
  }

// =========================================
// FORM SUBMISSION - COMPLETELY FIXED VERSION
// =========================================
async function submitProposal() {
    console.log('=== SUBMIT PROPOSAL STARTED ===');
    
    if (!validateAllSteps()) {
        console.log('❌ Form validation failed');
        return;
    }

    // Show loading state
    const nextBtn = document.getElementById('nextBtn');
    const originalText = nextBtn.innerHTML;
    nextBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    nextBtn.disabled = true;
    
    try {
        console.log('📦 Collecting form data...');
        
        // Collect all form data properly
        const formData = new FormData();
        
        // Add all form fields explicitly
        const form = document.getElementById('eventForm');
        const formElements = form.elements;
        
        for (let element of formElements) {
            if (element.name && element.type !== 'button' && element.type !== 'submit') {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    if (element.checked) {
                        formData.append(element.name, element.value);
                    }
                } else {
                    formData.append(element.name, element.value);
                }
            }
        }
        
        // Add calculated fields
        formData.append('preliminaryEstimate', calculatePreliminaryCost());
        formData.append('eventTime', document.querySelector('input[name="eventTime"]:checked')?.value || 'day');
        
        // Add decorations as JSON
        const selectedDecorations = Array.from(document.querySelectorAll('.decoration-check:checked')).map(cb => cb.value);
        formData.append('decorations', JSON.stringify(selectedDecorations));
        
        console.log('🚀 Sending request to server...');
        
        // Submit via AJAX
        const response = await fetch('event_api/submit_proposal_handler.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('📨 Response received, status:', response.status);
        
        // Get response text first to debug
        const responseText = await response.text();
        console.log('📄 Raw response:', responseText);
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ JSON Parse Error:', parseError);
            console.error('📄 Response that failed to parse:', responseText);
            throw new Error('Server returned invalid JSON. Check server for PHP errors.');
        }
        
        console.log('✅ Parsed JSON data:', data);
        
        if (data.success) {
            console.log('🎉 Submission successful! Proposal ID:', data.proposal_id);
            // Show success modal
            showSuccessModal(data.proposal_id);
        } else {
            console.error('❌ Server returned error:', data.message);
            throw new Error(data.message || 'Submission failed');
        }
        
    } catch (error) {
        console.error('💥 Submission error:', error);
        showErrorModal(error.message || 'Network error. Please try again.');
        nextBtn.innerHTML = originalText;
        nextBtn.disabled = false;
    }
}

function showSuccessModal(proposalId) {
    // Update modal with proposal ID
    document.getElementById('proposalId').textContent = proposalId;
    
    // Show confirmation modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
    
    // Redirect after modal is closed
    document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function() {
        window.location.href = 'user_dashboard.php?page=event_proposal';
    });
}

function showErrorModal(message) {
    const modalTitle = document.getElementById('validationModalTitle');
    const modalMessage = document.getElementById('validationModalMessage');
    
    modalTitle.textContent = 'Submission Failed';
    modalMessage.textContent = message;
    
    const modal = new bootstrap.Modal(document.getElementById('validationModal'));
    modal.show();
}

  // =========================================
  // VENUE DETAILS MODAL FUNCTION
  // =========================================
  function showVenueDetailsModal() {
    const selectedVenue = document.getElementById('selectedVenue').value;
    
    if (!selectedVenue || selectedVenue === '') {
      // Show error message
      const modalTitle = document.getElementById('validationModalTitle');
      const modalMessage = document.getElementById('validationModalMessage');
      modalTitle.textContent = 'No Venue Selected';
      modalMessage.textContent = 'Please select a venue first before viewing details.';
      
      const modal = new bootstrap.Modal(document.getElementById('validationModal'));
      modal.show();
      return;
    }
    
    const venue = venueData[selectedVenue];
    
    if (!venue) {
      return;
    }
    
    // Populate modal with venue data
    document.getElementById('modalVenueName').textContent = selectedVenue;
    document.getElementById('modalVenueDescription').textContent = venue.description;
    document.getElementById('modalVenueCapacity').textContent = venue.capacity;
    document.getElementById('modalVenueDuration').textContent = venue.duration;
    
    // Handle different rate structures for Mabuhay Lounge
    if (selectedVenue === "Mabuhay Lounge") {
      document.getElementById('modalDaytimeRate').textContent = venue.airconRate;
      document.getElementById('modalNighttimeRate').textContent = venue.nonAirconRate;
      // Update labels for Mabuhay Lounge
      const rateLabels = document.querySelectorAll('.modal-body-scrollable .row .col-6 small');
      if (rateLabels.length >= 4) {
        rateLabels[2].textContent = "Air Conditioned";
        rateLabels[3].textContent = "Non Air Conditioned";
      }
    } else {
      document.getElementById('modalDaytimeRate').textContent = venue.daytimeRate;
      document.getElementById('modalNighttimeRate').textContent = venue.nighttimeRate;
      // Reset labels for other venues
      const rateLabels = document.querySelectorAll('.modal-body-scrollable .row .col-6 small');
      if (rateLabels.length >= 4) {
        rateLabels[2].textContent = "Daytime Rate";
        rateLabels[3].textContent = "Nighttime Rate";
      }
    }
    
    // Set background image
    const modalBackground = document.getElementById('modalVenueBackground');
    if (modalBackground) {
      modalBackground.style.backgroundImage = `url('${venue.image}')`;
    }
    
    // Populate features
    const featuresContainer = document.getElementById('modalFeatures');
    if (featuresContainer) {
      featuresContainer.innerHTML = '';
      venue.features.forEach(feature => {
        const featureEl = document.createElement('div');
        featureEl.className = 'feature-item';
        featureEl.textContent = feature;
        featuresContainer.appendChild(featureEl);
      });
    }
    
    // Show modal using Bootstrap
    const venueModal = new bootstrap.Modal(document.getElementById('venueDetailsModal'));
    venueModal.show();
  }

  // =========================================
  // UPDATE VENUE DETAILS BUTTON STATE
  // =========================================
  function updateVenueDetailsButton() {
    const venueDetailsBtn = document.getElementById('venueDetailsBtn');
    const selectedVenue = document.getElementById('selectedVenue').value;
    
    if (venueDetailsBtn) {
      if (selectedVenue && selectedVenue !== '') {
        venueDetailsBtn.disabled = false;
        venueDetailsBtn.classList.remove('btn-outline-secondary');
        venueDetailsBtn.classList.add('btn-outline-success');
      } else {
        venueDetailsBtn.disabled = true;
        venueDetailsBtn.classList.remove('btn-outline-success');
        venueDetailsBtn.classList.add('btn-outline-secondary');
      }
    }
  }

  // =========================================
  // PRINT PROPOSAL SUMMARY
  // =========================================
  function printProposalSummary() {
    const printContent = document.querySelector('#step-review .card-border-success').outerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Event Proposal Summary</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body { padding: 20px; }
                .card { border: none !important; box-shadow: none !important; }
                .btn { display: none !important; }
                .text-success { color: #198754 !important; }
                .text-muted { color: #6c757d !important; }
                @media print {
                    .no-print { display: none !important; }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="text-center mb-4">
                    <h3 class="text-success">Event Proposal Summary</h3>
                    <p class="text-muted">Generated on ${new Date().toLocaleDateString()}</p>
                </div>
                ${printContent}
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>