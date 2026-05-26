<?php
// This file contains only the dashboard content
$current_page = 'main_manager';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
include __DIR__ . '/../../db_connect.php';

// Include functions ONLY if they haven't been loaded yet
if (!function_exists('getPendingProposalsCount')) {
    include __DIR__ . '/manager_functions.php';
}

// Check if user is logged in and is a manager
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

// Verify database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "No connection object"));
}

// Get manager ID from session
$managerId = $_SESSION['admin_id'] ?? null;

// Status configuration matching the workflow
$statusConfig = [
    'pending' => [
        'label' => 'Pending Review',
        'color' => 'bg-warning text-dark',
        'icon' => 'fas fa-clock',
        'description' => 'Waiting for manager assignment'
    ],
    'under_review' => [
        'label' => 'Under Review',
        'color' => 'bg-info text-white',
        'icon' => 'fas fa-search',
        'description' => 'Currently being reviewed by manager'
    ],
    'approved' => [
        'label' => 'Approved - Awaiting Deposit',
        'color' => 'bg-primary text-white',
        'icon' => 'fas fa-check',
        'description' => 'Approved, waiting for client payment'
    ],
    'needs_changes' => [
        'label' => 'Changes Requested',
        'color' => 'bg-warning text-dark',
        'icon' => 'fas fa-edit',
        'description' => 'Client needs to make changes'
    ],
    'rejected' => [
        'label' => 'Rejected',
        'color' => 'bg-danger text-white',
        'icon' => 'fas fa-times',
        'description' => 'Proposal has been rejected'
    ],
    'payment_pending_verification' => [
        'label' => 'Payment Pending Verification',
        'color' => 'bg-info text-white',
        'icon' => 'fas fa-credit-card',
        'description' => 'Waiting for payment verification'
    ],
    'confirmed' => [
        'label' => 'Confirmed',
        'color' => 'bg-success text-white',
        'icon' => 'fas fa-check-circle',
        'description' => 'Deposit paid, event confirmed'
    ],
    'fully_paid' => [
        'label' => 'Fully Paid',
        'color' => 'bg-success text-white',
        'icon' => 'fas fa-dollar-sign',
        'description' => 'Full payment received'
    ],
    'completed' => [
        'label' => 'Completed',
        'color' => 'bg-secondary text-white',
        'icon' => 'fas fa-flag',
        'description' => 'Event completed successfully'
    ]
];

// Get real data from database using the functions - ONLY CALL ONCE
$statusCounts = getProposalStats($managerId);
$totalProposalsCount = getTotalProposalsCount($managerId);
$availableProposalsCount = getAvailableProposalsCount($managerId);
$recentProposals = getRecentProposals(5, $managerId);
$upcomingEvents = getUpcomingEvents(5, $managerId);

// Calculate stats for quick stats cards - USING REAL DATA
$pendingProposals = $statusCounts['pending'] ?? 0;
$underReviewProposals = $statusCounts['under_review'] ?? 0;
$approvedProposals = $statusCounts['approved'] ?? 0;
$rejectedProposals = $statusCounts['rejected'] ?? 0;
$confirmedEvents = $statusCounts['confirmed'] ?? 0;
$completedEvents = $statusCounts['completed'] ?? 0;

$stats = [
    [
        'label' => 'Available Proposals',
        'value' => $availableProposalsCount,
        'color' => '#00b8a9',
        'bgColor' => 'rgba(0, 184, 169, 0.1)',
        'icon' => 'fas fa-inbox',
        'description' => 'Proposals you can work on'
    ],
    [
        'label' => 'Pending Review',
        'value' => $pendingProposals,
        'color' => '#ffc107',
        'bgColor' => 'rgba(255, 193, 7, 0.1)',
        'icon' => 'fas fa-clock',
        'description' => 'Waiting for your review'
    ],
    [
        'label' => 'Under Review',
        'value' => $underReviewProposals,
        'color' => '#17a2b8',
        'bgColor' => 'rgba(23, 162, 184, 0.1)',
        'icon' => 'fas fa-search',
        'description' => 'Currently being reviewed'
    ],
    [
        'label' => 'Rejected',
        'value' => $rejectedProposals,
        'color' => '#dc3545',
        'bgColor' => 'rgba(220, 53, 69, 0.1)',
        'icon' => 'fas fa-times',
        'description' => 'Proposals not approved'
    ],
    [
        'label' => 'Confirmed Events',
        'value' => $confirmedEvents,
        'color' => '#00b8a9',
        'bgColor' => 'rgba(0, 184, 169, 0.1)',
        'icon' => 'fas fa-check-circle',
        'description' => 'Your confirmed events'
    ],
    [
        'label' => 'Completed Events',
        'value' => $completedEvents,
        'color' => '#28a745',
        'bgColor' => 'rgba(40, 167, 69, 0.1)',
        'icon' => 'fas fa-flag',
        'description' => 'Successfully completed events'
    ]
];

// Priority statuses for the pipeline - using real counts (include rejected in pipeline)
$pipelineData = [
    'pending' => $pendingProposals,
    'under_review' => $underReviewProposals,
    'approved' => $approvedProposals,
    'rejected' => $rejectedProposals,
    'confirmed' => $confirmedEvents,
    'completed' => $completedEvents
];

// Filter out statuses with 0 counts for pipeline
$activePipelineStatuses = array_filter($pipelineData, function($count) {
    return $count > 0;
});
?>

<script>
// JavaScript for live clock and date navigation with Manila timezone
function updateManilaClock() {
    // Create date object with Manila timezone
    const now = new Date();
    
    // Format for Manila time (UTC+8)
    const options = {
        timeZone: 'Asia/Manila',
        hour12: true,
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit'
    };
    
    const dateOptions = {
        timeZone: 'Asia/Manila',
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    // Get Manila time and date
    const timeString = now.toLocaleTimeString('en-US', options);
    const dateString = now.toLocaleDateString('en-US', dateOptions);
    
    // Update the clock and date elements
    document.getElementById('live-clock').textContent = timeString;
    document.getElementById('live-date').textContent = dateString;
    
    // Add timezone indicator
    document.getElementById('timezone').textContent = 'Manila Time (PHT)';
}

// Initialize clock and update every second
document.addEventListener('DOMContentLoaded', function() {
    updateManilaClock();
    setInterval(updateManilaClock, 1000);
});
</script>

<div class="dashboard-container">
    <div class="welcome-card">
        <div class="welcome-header">
            <div class="welcome-text">
                <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h3>
                <p class="welcome-description">You are logged in as Event Manager. Here's an overview of your event management.</p>
            </div>
            <div class="clock-container">
                <div id="live-clock" class="live-clock"></div>
                <div id="live-date" class="live-date"></div>
                <div id="timezone" class="timezone"></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats Cards - INCLUDING REJECTED AND COMPLETED -->
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: <?php echo $stat['bgColor']; ?>; color: <?php echo $stat['color']; ?>;">
                <i class="<?php echo $stat['icon']; ?>"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number" style="color: <?php echo $stat['color']; ?>;"><?php echo $stat['value']; ?></div>
                <div class="stat-title"><?php echo $stat['label']; ?></div>
                <small class="stat-description"><?php echo $stat['description']; ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Combined Proposal Pipeline Section - INCLUDING REJECTED AND COMPLETED -->
    <div class="dashboard-card">
        <div class="card-header">
            <h6 class="card-title">Your Proposal Pipeline</h6>
            <a href="event_manager_dashboard.php?page=event_proposals" class="view-all-btn">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <!-- Visual Pipeline Bar - INCLUDING REJECTED AND COMPLETED -->
            <div class="pipeline-container">
                <div class="pipeline-bar">
                    <?php if (!empty($activePipelineStatuses)): 
                        $totalActive = array_sum($activePipelineStatuses);
                        
                        // Define color mapping for pipeline segments using teal scheme
                        $pipelineColors = [
                            'pending' => '#ffc107', // warning yellow
                            'under_review' => '#17a2b8', // info blue
                            'approved' => '#00b8a9', // primary teal
                            'rejected' => '#dc3545', // danger red
                            'confirmed' => '#00998c', // dark teal
                            'completed' => '#28a745' // success green
                        ];
                        
                        foreach ($activePipelineStatuses as $status => $count): 
                            $percentage = ($count / $totalActive) * 100;
                            $color = $pipelineColors[$status] ?? '#6c757d'; // default gray
                    ?>
                        <div 
                            class="pipeline-segment"
                            style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>;"
                            title="<?php echo $statusConfig[$status]['label']; ?>: <?php echo $count; ?>"
                        >
                            <?php echo $count; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <div class="pipeline-empty">
                            No active proposals in your pipeline
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="pipeline-legend">
                    <?php 
                    // Use the same color mapping for legend
                    $legendColors = [
                        'pending' => '#ffc107',
                        'under_review' => '#17a2b8', 
                        'approved' => '#00b8a9',
                        'rejected' => '#dc3545',
                        'confirmed' => '#00998c',
                        'completed' => '#28a745'
                    ];
                    
                    foreach ($activePipelineStatuses as $status => $count): 
                        $color = $legendColors[$status] ?? '#6c757d';
                    ?>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: <?php echo $color; ?>;"></div>
                            <span class="legend-text">
                                <?php echo $statusConfig[$status]['label']; ?> (<?php echo $count; ?>)
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Proposals - SHOWING ALL STATUSES INCLUDING REJECTED AND COMPLETED -->
            <div class="recent-proposals">
                <h6 class="section-title">Your Recent Proposals</h6>
                <?php if (empty($recentProposals)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No recent proposals assigned to you</p>
                        <small class="text-muted">New proposals will appear here when assigned to you</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentProposals as $proposal): 
                        $status = $proposal['status'];
                        $statusInfo = $statusConfig[$status] ?? $statusConfig['pending'];
                        
                        // Map status to badge classes for recent proposals
                        $badgeClasses = [
                            'pending' => 'bg-warning text-dark',
                            'under_review' => 'bg-info text-white',
                            'approved' => 'bg-primary text-white',
                            'needs_changes' => 'bg-warning text-dark',
                            'rejected' => 'bg-danger text-white',
                            'payment_pending_verification' => 'bg-info text-white',
                            'confirmed' => 'bg-success text-white',
                            'fully_paid' => 'bg-success text-white',
                            'completed' => 'bg-secondary text-white'
                        ];
                        $badgeClass = $badgeClasses[$status] ?? 'bg-secondary text-white';
                    ?>
                        <div class="proposal-card">
                            <div class="proposal-header">
                                <div class="proposal-info">
                                    <h6 class="proposal-title"><?php echo htmlspecialchars($proposal['event_title']); ?></h6>
                                    <small class="proposal-id">#<?php echo $proposal['proposal_id']; ?></small>
                                </div>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $statusInfo['label']; ?>
                                </span>
                            </div>
                            
                            <p class="client-name"><?php echo htmlspecialchars($proposal['client_name']); ?></p>
                            
                            <div class="event-details">
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M j, Y', strtotime($proposal['event_date'] ?? $proposal['arrival_date'])); ?> • 
                                    <i class="fas fa-users"></i>
                                    <?php echo $proposal['expected_guests']; ?> guests •
                                    <i class="fas fa-dollar-sign"></i>
                                    ₱<?php echo number_format($proposal['total_estimated_cost'] ?? 0, 2); ?>
                                </small>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="event_manager_dashboard.php?page=event_proposals" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                                <?php if ($proposal['status'] == 'rejected'): ?>
                                    <span class="text-danger small">
                                        <i class="fas fa-info-circle"></i> This proposal was rejected
                                    </span>
                                <?php elseif ($proposal['status'] == 'completed'): ?>
                                    <span class="text-success small">
                                        <i class="fas fa-check-circle"></i> Event completed successfully
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
    --white: #ffffff;
    --form-bg: #f9fbfc;
}

/* Dashboard Layout */
.dashboard-container {
    background: var(--white);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 184, 169, 0.3);
}

.welcome-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.welcome-text h3 {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.welcome-description {
    margin-bottom: 0;
    opacity: 0.9;
    font-size: 1rem;
}

.clock-container {
    min-width: 220px;
    text-align: right;
}

.live-clock {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.live-date {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0.25rem;
}

.timezone {
    font-size: 0.75rem;
    opacity: 0.7;
    font-style: italic;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    border: 1px solid rgba(0, 184, 169, 0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 184, 169, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
    line-height: 1;
}

.stat-title {
    font-size: 0.9rem;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.stat-description {
    color: #6c757d;
    font-size: 0.8rem;
}

/* Dashboard Cards */
.dashboard-card {
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0, 184, 169, 0.1);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    background: var(--primary-light);
    border-radius: 12px 12px 0 0;
}

.card-title {
    margin-bottom: 0;
    font-weight: 600;
    color: var(--primary-dark);
}

.view-all-btn {
    color: var(--primary);
    background: transparent;
    border: none;
    font-size: 0.875rem;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-all-btn:hover {
    color: var(--primary-dark);
    transform: translateX(3px);
}

.card-body {
    padding: 1.5rem;
}

/* Pipeline */
.pipeline-container {
    margin-bottom: 2rem;
}

.pipeline-bar {
    display: flex;
    height: 40px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.pipeline-segment {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    min-width: 50px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.pipeline-segment:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px rgba(255,255,255,0.5);
}

.pipeline-empty {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    background-color: var(--primary-light);
    font-style: italic;
}

.pipeline-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.legend-text {
    color: #2c3e50;
    font-weight: 500;
}

/* Recent Proposals */
.recent-proposals {
    margin-top: 1.5rem;
}

.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--primary-dark);
    font-size: 1rem;
}

.proposal-card {
    border: 1px solid rgba(0, 184, 169, 0.2);
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    background: var(--white);
    transition: all 0.3s ease;
}

.proposal-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.15);
    transform: translateY(-2px);
}

.proposal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.proposal-info {
    flex: 1;
}

.proposal-title {
    margin-bottom: 0.25rem;
    font-weight: 600;
    color: #2c3e50;
}

.proposal-id {
    color: #6c757d;
    font-size: 0.8rem;
}

.client-name {
    font-weight: 500;
    margin-bottom: 0.75rem;
    color: #495057;
}

.event-details {
    margin-bottom: 1rem;
}

.event-details small {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
    align-items: center;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00857a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
}

/* Badge Styles */
.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

.bg-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
}

.bg-success {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
}

.bg-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    margin-bottom: 0.5rem;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .welcome-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .clock-container {
        text-align: left;
        min-width: auto;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 1.25rem;
    }
    
    .pipeline-legend {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .action-buttons {
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .pipeline-bar {
        height: 32px;
    }
    
    .pipeline-segment {
        font-size: 0.75rem;
        min-width: 40px;
    }
    
    .proposal-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
    
    .card-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
    
    .view-all-btn {
        align-self: flex-end;
    }
}

/* Animation for better UX */
.stat-card, .dashboard-card, .proposal-card {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>