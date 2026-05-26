<?php 
if (!isset($_SESSION['username'])) {
  header("Location: user_login.php");
  exit();
}
?>

<!-- ========================================= -->
<!-- FACILITY BOOKING DASHBOARD                -->
<!-- ========================================= -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Facility Booking System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ========================================= -->
    <!-- EXTERNAL STYLESHEET                       -->
    <!-- ========================================= -->
    <style>
        .event-card {
            transition: all 0.3s ease;
            border-left: 4px solid #198754 !important;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .event-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        :root {
            --primary: #00b8a9;
            --primary-dark: #00998c;
            --primary-light: #e3f8f6;
            --success: #00b8a9;
            --success-light: rgba(0, 184, 169, 0.1);
            --warning: #ffc107;
            --warning-light: rgba(255, 193, 7, 0.1);
            --danger: #dc3545;
            --danger-light: rgba(220, 53, 69, 0.1);
            --info: #0dcaf0;
            --info-light: rgba(13, 202, 240, 0.1);
        }

        body {
            background: linear-gradient(to bottom, #f5f7fa, #e3f8f6);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0;
        }

        .reservation-item {
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }

        .reservation-item:hover {
            background-color: #f8f9fa;
            border-left-color: var(--primary);
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .btn-dashboard {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-dashboard:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 184, 169, 0.2);
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px;
            overflow: hidden;
        }

        .welcome-icon {
            font-size: 4rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .detail-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 140px;
        }

        .detail-value {
            color: #6c757d;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .status-badge-modal {
            font-size: 0.8rem;
            padding: 6px 16px;
            border-radius: 20px;
        }

        /* Updated button styles */
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-success {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-outline-success {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-success:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .text-success {
            color: var(--primary) !important;
        }

        .bg-success {
            background-color: var(--primary) !important;
        }

        .bg-success.bg-opacity-10 {
            background-color: rgba(0, 184, 169, 0.1) !important;
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .bg-primary {
            background-color: var(--primary) !important;
        }

        .border-success {
            border-color: var(--primary) !important;
        }

        /* Card styling to match upcoming events */
        .reservation-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background: white;
        }

        .reservation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            color: var(--primary);
            font-weight: 600;
        }

        /* Event status badges */
        .status-approved {
            background-color: var(--success);
        }
        .status-pending {
            background-color: var(--warning);
        }
        .status-rejected {
            background-color: var(--danger);
        }
        .status-draft {
            background-color: var(--info);
        }
    </style>
</head>

<body>

    <!-- ========================================= -->
    <!-- RESERVATION DETAILS MODAL                 -->
    <!-- ========================================= -->
    <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reservationModalLabel">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Reservation Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="reservationModalBody">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printReservationBtn">
                        <i class="fas fa-print me-1"></i>Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- EVENT DETAILS MODAL                       -->
    <!-- ========================================= -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>
                        Event Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body" id="eventModalBody">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printEventBtn">
                        <i class="fas fa-print me-1"></i>Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- MAIN CONTAINER: Dashboard Wrapper         -->
    <!-- ========================================= -->
    <div class="dashboard-container">
        <div class="px-2">

            <!-- ========================================= -->
            <!-- PAGE HEADER                               -->
            <!-- ========================================= -->
            <div class="dashboard-header">
                <div class="welcome-card p-4 position-relative mb-4">
                    <h1 class="fw-bold mb-2">Welcome back, <span id="userName">User</span>!</h1>
                    <p class="mb-0 opacity-75">Here's an overview of your facility bookings and requests</p>
                    <i class="fas fa-calendar-alt welcome-icon"></i>
                </div>
            </div>

            <!-- ========================================= -->
            <!-- STATISTICS CARDS                          -->
            <!-- ========================================= -->
            <div class="row g-4 mb-5">
                <!-- Upcoming Reservations Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h3 class="stat-number text-success" id="upcomingCount">0</h3>
                                    <p class="stat-title">Upcoming Reservations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approved Events Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div>
                                    <h3 class="stat-number text-info" id="approvedCount">0</h3>
                                    <p class="stat-title">Approved Events</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Requests Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div>
                                    <h3 class="stat-number text-warning" id="pendingCount">0</h3>
                                    <p class="stat-title">Pending Requests</p>
                                    <small class="text-muted" id="pendingStatus">Awaiting review</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Approved Reservations Card - FIXED WITH UNIQUE ICON -->
                <div class="col-md-6 col-lg-3">
                    <div class="card stat-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div>
                                    <h3 class="stat-number text-primary" id="approvedReservationsCount">0</h3>
                                    <p class="stat-title">Approved Reservations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ========================================= -->
            <!-- MAIN CONTENT GRID                         -->
            <!-- ========================================= -->
            <div class="row g-4">
                <!-- ========================================= -->
                <!-- RECENT RESERVATIONS SECTION               -->
                <!-- ========================================= -->
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <h4 class="card-title text-success mb-3">Recent Reservations</h4>
                            <p class="text-muted mb-4">Your latest booking activities</p>
                            
                            <div id="recentReservationsContainer">
                                <!-- Dynamic content will be loaded here -->
                                <div class="empty-state">
                                    <i class="fas fa-calendar-plus"></i>
                                    <p>No recent reservations found</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- UPCOMING EVENTS SECTION -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>Upcoming Events
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="upcomingEventsContainer">
                                <!-- Loading spinner -->
                                <div class="text-center py-4">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading upcoming events...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- JAVASCRIPT FOR DATA LOADING               -->
    <!-- ========================================= -->
    <script>
    // Global variable to store current reservation data
    let currentReservation = null;
    let currentEvent = null;

    // Load real data from database
    document.addEventListener('DOMContentLoaded', function() {
        // Load user data
        loadUserData();
        
        // Load statistics
        loadStatistics();
        
        // Load recent reservations
        loadRecentReservations();
        
        // Load upcoming events
        loadUpcomingEvents();
        
        // Setup print button event listeners
        document.getElementById('printReservationBtn').addEventListener('click', printReservationDetails);
        document.getElementById('printEventBtn').addEventListener('click', printEventDetails);
    });

    function loadUserData() {
        // Set username from session - PHP variable properly escaped
        document.getElementById('userName').textContent = '<?php echo isset($_SESSION["username"]) ? addslashes($_SESSION["username"]) : "User"; ?>';
    }

    function loadStatistics() {
        fetch('api_reservation/get_statistics.php')
            .then(response => {
                console.log('Statistics response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Statistics data:', data);
                if (data.success) {
                    document.getElementById('upcomingCount').textContent = data.statistics.upcoming;
                    document.getElementById('approvedCount').textContent = data.statistics.approved_events;
                    document.getElementById('pendingCount').textContent = data.statistics.pending;
                    document.getElementById('approvedReservationsCount').textContent = data.statistics.approved_reservations;
                    
                    // Update status messages
                    document.getElementById('pendingStatus').textContent = 
                        data.statistics.pending > 0 ? 'Awaiting review' : 'All clear';
                } else {
                    console.error('Statistics API error:', data.error);
                }
            })
            .catch(error => {
                console.error('Error loading statistics:', error);
                // Set default values on error
                document.getElementById('upcomingCount').textContent = '0';
                document.getElementById('approvedCount').textContent = '0';
                document.getElementById('pendingCount').textContent = '0';
                document.getElementById('approvedReservationsCount').textContent = '0';
            });
    }

    function loadRecentReservations() {
        fetch('api_reservation/get_recents_reservations.php')
            .then(response => {
                console.log('Recent reservations response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Recent reservations API returned:', text);
                        throw new Error(`HTTP ${response.status}: ${text.substring(0, 100)}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Recent reservations data:', data);
                const container = document.getElementById('recentReservationsContainer');
                
                if (!data.success || !data.reservations || data.reservations.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-calendar-plus"></i>
                            <p>No recent reservations found</p>
                            <small class="text-muted">Your recent bookings will appear here</small>
                        </div>
                    `;
                    return;
                }
                
                let html = '';
                data.reservations.forEach(reservation => {
                    const statusClass = reservation.status === 'confirmed' ? 
                        'bg-success' : 
                        reservation.status === 'pending' ?
                        'bg-warning' :
                        'bg-secondary';
                    
                    const statusText = reservation.status === 'confirmed' ? 'Confirmed' : 
                                     reservation.status === 'pending' ? 'Pending' : 'Cancelled';
                    
                    // Room Reservation Card
                    if (reservation.type === 'room') {
                        html += `
                            <div class="d-flex align-items-start p-4 border rounded mb-3 reservation-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 me-3">
                                        <i class="fas fa-bed fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="badge bg-primary mb-2">Room Booking</span>
                                    <h6 class="fw-bold mb-1 text-success">${reservation.room_type} ${reservation.room_number ? '- Room ' + reservation.room_number : ''}</h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="fas fa-calendar me-1"></i>
                                        ${formatDate(reservation.checkin_date)} to ${formatDate(reservation.checkout_date)}
                                    </p>
                                    <p class="text-muted mb-1 small">
                                        <i class="fas fa-clock me-1"></i>
                                        ${formatTime(reservation.arrival_time) || '2:00 PM'}
                                    </p>
                                    <p class="mb-0 small text-success fw-bold">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        ₱${parseFloat(reservation.total_amount || 0).toLocaleString()}
                                    </p>
                                </div>
                                <div class="ms-3 text-end">
                                    <span class="badge ${statusClass} fs-6 px-3 py-2 mb-2">${statusText}</span>
                                    <br>
                                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="showReservationDetails(${JSON.stringify(reservation).replace(/"/g, '&quot;')})">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        `;
                    } 
                    // Facility Booking Card
                    else if (reservation.type === 'facility') {
                        html += `
                            <div class="d-flex align-items-start p-4 border rounded mb-3 reservation-card">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3 me-3">
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="badge bg-info mb-2">Facility Booking</span>
                                    <h6 class="fw-bold mb-1 text-success">${reservation.facility_name}</h6>
                                    <p class="text-muted mb-1 small">
                                        <i class="fas fa-calendar me-1"></i>
                                        ${formatDate(reservation.checkin_date)}
                                    </p>
                                    ${reservation.booking_time ? `
                                    <p class="text-muted mb-1 small">
                                        <i class="fas fa-clock me-1"></i>
                                        ${formatTime(reservation.booking_time)}
                                    </p>
                                    ` : ''}
                                    <p class="mb-0 small text-success fw-bold">
                                        <i class="fas fa-dollar-sign me-1"></i>
                                        ₱${parseFloat(reservation.total_amount || 0).toLocaleString()}
                                    </p>
                                </div>
                                <div class="ms-3 text-end">
                                    <span class="badge ${statusClass} fs-6 px-3 py-2 mb-2">${statusText}</span>
                                    <br>
                                    <button class="btn btn-outline-primary btn-sm mt-2" onclick="showReservationDetails(${JSON.stringify(reservation).replace(/"/g, '&quot;')})">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                });
                
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading recent reservations:', error);
                const container = document.getElementById('recentReservationsContainer');
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <p>Error loading reservations</p>
                        <small class="text-muted">${error.message}</small>
                    </div>
                `;
            });
    }

    function loadUpcomingEvents() {
        fetch('api_reservation/get_upcoming_events.php')
            .then(response => {
                console.log('Upcoming events response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Upcoming events data:', data);
                const container = document.getElementById('upcomingEventsContainer');
                
                if (!data.success || !data.events || data.events.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times text-muted fa-3x mb-3"></i>
                            <h5 class="text-muted">No Upcoming Events</h5>
                            <p class="text-muted">You don't have any confirmed events scheduled.</p>
                            <a href="event_proposal_form.php" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Create Event Proposal
                            </a>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = data.events.map(event => `
                    <div class="event-card border rounded p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <div class="event-icon bg-success bg-opacity-10 rounded-circle p-3">
                                    <i class="fas ${event.icon || 'fa-calendar'} text-success fa-lg"></i>
                                </div>
                            </div>
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-success">${event.event_title}</h6>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i>${event.venue_preference}
                                        </p>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-users me-1"></i>${event.expected_guests} guests
                                        </p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-clock me-1"></i>${event.arrival_date_formatted} at ${event.arrival_time_formatted}
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-${event.payment_badge || 'secondary'} mb-2">${event.payment_text || 'Pending'}</span>
                                        <div class="text-success fw-bold">₱${parseFloat(event.total_estimated_cost || 0).toLocaleString()}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-outline-success btn-sm" onclick="showEventDetails(${JSON.stringify(event).replace(/"/g, '&quot;')})">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error loading upcoming events:', error);
                document.getElementById('upcomingEventsContainer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Failed to load events. Please try again.
                    </div>
                `;
            });
    }

    // Show event details in modal
    function showEventDetails(event) {
        currentEvent = event;
        const modalBody = document.getElementById('eventModalBody');
        
        // Get status badge class and text
        const statusClass = getEventStatusClass(event.status);
        const statusText = getEventStatusText(event.status);
        
        const html = `
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Event Title:</span>
                        <strong>${event.event_title || 'N/A'}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Event Type:</span>
                        <strong>${event.event_type || 'N/A'}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Venue:</span>
                        <strong>${event.venue_preference || 'N/A'}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Expected Guests:</span>
                        <strong>${event.expected_guests || '0'}</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Event Date:</span>
                        <strong>${event.arrival_date_formatted || 'N/A'}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Event Time:</span>
                        <strong>${event.arrival_time_formatted || 'N/A'}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Total Cost:</span>
                        <strong class="fs-5 text-success">₱${parseFloat(event.total_estimated_cost || 0).toLocaleString()}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Status:</span>
                        <span class="badge ${statusClass} status-badge-modal fs-6">${statusText}</span>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <h6 class="text-success mb-3">Payment Information</h6>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Payment Status:</span>
                        <span class="badge bg-${event.payment_badge || 'secondary'}">${event.payment_text || 'Pending'}</span>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Deposit Paid:</span>
                        <strong>${event.deposit_paid ? 'Yes' : 'No'}</strong>
                    </div>
                    <div class="detail-item d-flex justify-content-between">
                        <span class="detail-label">Balance Paid:</span>
                        <strong>${event.balance_paid ? 'Yes' : 'No'}</strong>
                    </div>
                </div>
            </div>
        `;

        modalBody.innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('eventModal'));
        modal.show();
    }

    // Utility function to get event status class
    function getEventStatusClass(status) {
        const statusMap = {
            'approved': 'bg-success',
            'pending': 'bg-warning',
            'rejected': 'bg-danger',
            'draft': 'bg-info',
            'confirmed': 'bg-success',
            'cancelled': 'bg-secondary'
        };
        return statusMap[status] || 'bg-secondary';
    }

    // Utility function to get event status text
    function getEventStatusText(status) {
        const statusMap = {
            'approved': 'Approved',
            'pending': 'Pending Review',
            'rejected': 'Rejected',
            'draft': 'Draft',
            'confirmed': 'Confirmed',
            'cancelled': 'Cancelled'
        };
        return statusMap[status] || 'Unknown';
    }

    // Show reservation details in modal
    function showReservationDetails(reservation) {
        currentReservation = reservation;
        const modalBody = document.getElementById('reservationModalBody');
        let html = '';

        if (reservation.type === 'room') {
            const statusClass = reservation.status === 'confirmed' ? 'bg-success' : 
                               reservation.status === 'pending' ? 'bg-warning' : 'bg-secondary';
            const statusText = reservation.status === 'confirmed' ? 'Confirmed' : 
                              reservation.status === 'pending' ? 'Pending' : 'Cancelled';

            html = `
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Reference No:</span>
                            <strong>${reservation.reservation_ref || 'N/A'}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Room Type:</span>
                            <strong>${reservation.room_type || 'N/A'}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Room Number:</span>
                            <strong class="text-primary">
                                ${reservation.room_number ? '#' + reservation.room_number : '<em class="text-muted">Not assigned</em>'}
                            </strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Check-in:</span>
                            <strong>${formatDate(reservation.checkin_date)}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Check-out:</span>
                            <strong>${formatDate(reservation.checkout_date)}</strong>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Nights:</span>
                            <strong>${reservation.nights || 'N/A'}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Arrival Time:</span>
                            <strong>${formatTime(reservation.arrival_time) || '2:00 PM'}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Total Amount:</span>
                            <strong class="fs-5 text-success">₱${parseFloat(reservation.total_amount || 0).toLocaleString()}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Status:</span>
                            <span class="badge ${statusClass} status-badge-modal fs-6">${statusText}</span>
                        </div>
                    </div>
                </div>
            `;
        } else {
            const statusClass = reservation.status === 'confirmed' ? 'bg-success' : 
                               reservation.status === 'pending' ? 'bg-warning' : 'bg-secondary';
            const statusText = reservation.status === 'confirmed' ? 'Confirmed' : 
                              reservation.status === 'pending' ? 'Pending' : 'Cancelled';

            html = `
                <div class="text-center mb-4">
                    <i class="fas fa-building fa-4x text-success opacity-25"></i>
                </div>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Reference No:</span>
                            <strong>${reservation.reservation_ref || reservation.payment_reference}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Facility:</span>
                            <strong>${reservation.facility_name || 'N/A'}</strong>
                        </div>
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Type:</span>
                            <strong>${reservation.facility_type || 'N/A'}</strong>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Booking Date:</span>
                            <strong>${formatDate(reservation.checkin_date || reservation.booking_date)}</strong>
                        </div>
                        ${reservation.booking_time ? `
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Time:</span>
                            <strong>${formatTime(reservation.booking_time)}</strong>
                        </div>
                        ` : ''}
                        <div class="detail-item d-flex justify-content-between">
                            <span class="detail-label">Status:</span>
                            <span class="badge ${statusClass} status-badge-modal ms-2">${statusText}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        modalBody.innerHTML = html;
        const modal = new bootstrap.Modal(document.getElementById('reservationModal'));
        modal.show();
    }

    // Print event details
    function printEventDetails() {
        if (!currentEvent) return;
        
        const printWindow = window.open('', '_blank');
        const event = currentEvent;
        
        const content = `
            <html>
            <head>
                <title>Event Details - ${event.event_title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #00b8a9; padding-bottom: 10px; }
                    .detail-row { display: flex; margin-bottom: 8px; }
                    .detail-label { font-weight: bold; min-width: 150px; }
                    .total { font-size: 1.2em; font-weight: bold; color: #00b8a9; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>MCC - Event Details</h2>
                    <h3>${event.event_title}</h3>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Event Type:</span>
                    <span>${event.event_type}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Venue:</span>
                    <span>${event.venue_preference}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span>${event.arrival_date_formatted}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span>${event.arrival_time_formatted}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expected Guests:</span>
                    <span>${event.expected_guests}</span>
                </div>
                <div class="detail-row total">
                    <span class="detail-label">Total Cost:</span>
                    <span>₱${parseFloat(event.total_estimated_cost).toLocaleString()}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span>${getEventStatusText(event.status)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Printed:</span>
                    <span>${new Date().toLocaleString()}</span>
                </div>
            </body>
            </html>
        `;
        
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.print();
    }

    // Utility functions
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        } catch (e) {
            return dateString;
        }
    }

    function formatTime(timeString) {
        if (!timeString) return '';
        try {
            const time = timeString.split(':');
            let hours = parseInt(time[0]);
            const minutes = time[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            return `${hours}:${minutes} ${ampm}`;
        } catch (e) {
            return timeString;
        }
    }

    function printReservationDetails() {
        if (!currentReservation) return;
        
        const printWindow = window.open('', '_blank');
        const reservation = currentReservation;
        
        let content = '';
        if (reservation.type === 'room') {
            content = `
                <html>
                <head>
                    <title>Reservation Details - ${reservation.reservation_ref}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #00b8a9; padding-bottom: 10px; }
                        .detail-row { display: flex; margin-bottom: 8px; }
                        .detail-label { font-weight: bold; min-width: 150px; }
                        .total { font-size: 1.2em; font-weight: bold; color: #00b8a9; margin-top: 20px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>MCC - Reservation Details</h2>
                        <h3>${reservation.reservation_ref}</h3>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Room Type:</span>
                        <span>${reservation.room_type}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-in:</span>
                        <span>${formatDate(reservation.checkin_date)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-out:</span>
                        <span>${formatDate(reservation.checkout_date)}</span>
                    </div>
                    <div class="detail-row total">
                        <span class="detail-label">Total Amount:</span>
                        <span>₱${parseFloat(reservation.total_amount).toLocaleString()}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Printed:</span>
                        <span>${new Date().toLocaleString()}</span>
                    </div>
                </body>
                </html>
            `;
        }
        
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.print();
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</body>
</html>