<?php
// This file contains only the event reports content
$current_page = 'reports';

// Database functions
function getReportStats($managerId = null, $year = null) {
    global $conn;
    $currentYear = $year ?? date('Y');
    
    $query = "SELECT 
        COALESCE(SUM(CASE WHEN status IN ('confirmed', 'fully_paid', 'completed') THEN total_estimated_cost ELSE 0 END), 0) as total_revenue,
        COUNT(CASE WHEN status IN ('confirmed', 'fully_paid', 'completed') THEN 1 END) as events_completed,
        COUNT(DISTINCT user_id) as total_clients,
        COALESCE(AVG(CASE WHEN status IN ('confirmed', 'fully_paid', 'completed') THEN expected_guests ELSE NULL END), 0) as avg_event_size
        FROM event_proposals 
        WHERE YEAR(submitted) = ?";
    
    if ($managerId) {
        $query .= " AND assigned_manager_id = ?";
    }
    
    $stmt = $conn->prepare($query);
    if ($managerId) {
        $stmt->bind_param("ii", $currentYear, $managerId);
    } else {
        $stmt->bind_param("i", $currentYear);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data ?: [
        'total_revenue' => 0,
        'events_completed' => 0,
        'total_clients' => 0,
        'avg_event_size' => 0
    ];
}

function getEventTypesData($managerId = null) {
    global $conn;
    
    $query = "SELECT event_type, COUNT(*) as count 
              FROM event_proposals 
              WHERE status IN ('confirmed', 'fully_paid', 'completed')";
    
    if ($managerId) {
        $query .= " AND assigned_manager_id = ?";
    }
    
    $query .= " GROUP BY event_type ORDER BY count DESC";
    
    $stmt = $conn->prepare($query);
    if ($managerId) {
        $stmt->bind_param("i", $managerId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data ?: [];
}

function getFacilitiesPerformance($managerId = null) {
    global $conn;
    
    $query = "SELECT venue_preference as venue, COUNT(*) as events, SUM(total_estimated_cost) as revenue
              FROM event_proposals 
              WHERE status IN ('confirmed', 'fully_paid', 'completed')";
    
    if ($managerId) {
        $query .= " AND assigned_manager_id = ?";
    }
    
    $query .= " GROUP BY venue_preference ORDER BY revenue DESC LIMIT 6";
    
    $stmt = $conn->prepare($query);
    if ($managerId) {
        $stmt->bind_param("i", $managerId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data ?: [];
}

function getMonthlyTrendData($managerId = null, $year = null) {
    global $conn;
    $currentYear = $year ?? date('Y');
    
    // First, get all months to ensure we have data for each month
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
        $monthName = date('F', mktime(0, 0, 0, $i, 1));
        $months[$i] = [
            'month' => $monthName,
            'month_num' => $i,
            'events' => 0,
            'revenue' => 0
        ];
    }
    
    $query = "SELECT 
        MONTH(submitted) as month_num,
        COUNT(*) as events,
        COALESCE(SUM(total_estimated_cost), 0) as revenue
        FROM event_proposals 
        WHERE YEAR(submitted) = ? 
        AND status IN ('confirmed', 'fully_paid', 'completed')";
    
    if ($managerId) {
        $query .= " AND assigned_manager_id = ?";
    }
    
    $query .= " GROUP BY MONTH(submitted) ORDER BY month_num";
    
    $stmt = $conn->prepare($query);
    if ($managerId) {
        $stmt->bind_param("ii", $currentYear, $managerId);
    } else {
        $stmt->bind_param("i", $currentYear);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $dbData = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Merge database data with all months
    foreach ($dbData as $row) {
        $monthNum = $row['month_num'];
        if (isset($months[$monthNum])) {
            $months[$monthNum]['events'] = $row['events'];
            $months[$monthNum]['revenue'] = $row['revenue'];
        }
    }
    
    return array_values($months);
}

function getGuestCapacityData($managerId = null) {
    global $conn;
    
    $query = "SELECT 
        CASE 
            WHEN expected_guests BETWEEN 0 AND 50 THEN '0-50'
            WHEN expected_guests BETWEEN 51 AND 100 THEN '51-100'
            WHEN expected_guests BETWEEN 101 AND 200 THEN '101-200'
            WHEN expected_guests BETWEEN 201 AND 300 THEN '201-300'
            ELSE '300+'
        END as capacity_range,
        COUNT(*) as count
        FROM event_proposals 
        WHERE status IN ('confirmed', 'fully_paid', 'completed')";
    
    if ($managerId) {
        $query .= " AND assigned_manager_id = ?";
    }
    
    $query .= " GROUP BY capacity_range ORDER BY 
        CASE capacity_range
            WHEN '0-50' THEN 1
            WHEN '51-100' THEN 2
            WHEN '101-200' THEN 3
            WHEN '201-300' THEN 4
            ELSE 5
        END";
    
    $stmt = $conn->prepare($query);
    if ($managerId) {
        $stmt->bind_param("i", $managerId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $data ?: [];
}

function getAdditionalMetrics($managerId = null) {
    global $conn;
    
    // Average event duration (using arrival_time as proxy)
    $query1 = "SELECT AVG(TIME_TO_SEC(arrival_time) / 3600) as avg_duration 
               FROM event_proposals 
               WHERE status IN ('confirmed', 'fully_paid', 'completed')";
    if ($managerId) {
        $query1 .= " AND assigned_manager_id = ?";
    }
    
    $stmt1 = $conn->prepare($query1);
    if ($managerId) {
        $stmt1->bind_param("i", $managerId);
    }
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $avgDuration = $result1->fetch_assoc()['avg_duration'] ?? 0;
    $stmt1->close();
    
    return [
        'avg_duration' => round($avgDuration, 1)
    ];
}

// Get report parameters
$currentYear = date('Y');
$managerId = $_SESSION['admin_id'] ?? null;

// Get data from database
$reportStats = getReportStats($managerId, $currentYear);
$eventTypesData = getEventTypesData($managerId);
$facilitiesData = getFacilitiesPerformance($managerId);
$monthlyTrendData = getMonthlyTrendData($managerId, $currentYear);
$capacityData = getGuestCapacityData($managerId);
$additionalMetrics = getAdditionalMetrics($managerId);

// Calculate percentages for event types
$totalEvents = $reportStats['events_completed'] ?? 0;
if ($totalEvents > 0) {
    foreach ($eventTypesData as &$eventType) {
        $eventType['percentage'] = round(($eventType['count'] / $totalEvents) * 100, 1);
    }
}

// Prepare stats for display
$stats = [
    [
        'label' => 'Total Revenue (YTD)',
        'value' => '₱' . number_format($reportStats['total_revenue'] ?? 0, 2),
        'change' => '+0%', // Would calculate from previous year
        'icon' => 'fas fa-money-bill-wave',
        'color' => '#00b8a9'
    ],
    [
        'label' => 'Events Completed',
        'value' => $reportStats['events_completed'] ?? 0,
        'change' => '+0%',
        'icon' => 'fas fa-calendar-check',
        'color' => '#00b8a9'
    ],
    [
        'label' => 'Total Clients',
        'value' => $reportStats['total_clients'] ?? 0,
        'change' => '+0%',
        'icon' => 'fas fa-users',
        'color' => '#00b8a9'
    ],
    [
        'label' => 'Average Event Size',
        'value' => round($reportStats['avg_event_size'] ?? 0),
        'change' => '+0%',
        'icon' => 'fas fa-chart-line',
        'color' => '#00b8a9'
    ]
];

$chartColors = ['#00b8a9', '#00998c', '#00857a', '#ffc107', '#6c757d', '#17a2b8'];
?>

<!-- Header -->
<div class="header-card">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="fas fa-chart-bar me-3"></i>Event Reports</h1>
            <p class="mb-0">View your event management statistics and insights</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-light" onclick="exportReports()">
                <i class="fas fa-download"></i> Export Report
            </button>
        </div>
    </div>
</div>

<!-- Stats Grid -->
<div class="row mb-4">
    <?php foreach ($stats as $stat): ?>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon">
                    <i class="<?php echo $stat['icon']; ?>" style="color: <?php echo $stat['color']; ?>;"></i>
                </div>
                <span class="text-success small"><?php echo $stat['change']; ?></span>
            </div>
            <h3 class="text-primary mb-1"><?php echo $stat['value']; ?></h3>
            <p class="mb-0 text-muted"><?php echo $stat['label']; ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Charts Section -->
<div class="row mb-4">
    <!-- Event Types Distribution -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Event Types Distribution</h5>
            </div>
            <div class="card-body">
                <?php if (empty($eventTypesData)): ?>
                    <div class="chart-placeholder text-center">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div>
                                <i class="fas fa-chart-pie fs-1 mb-3 text-muted"></i>
                                <p class="text-muted">No event data available</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($eventTypesData as $index => $item): ?>
                        <div class="col-6 mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="w-3 h-3 rounded-circle me-2" 
                                     style="background-color: <?php echo $chartColors[$index % count($chartColors)]; ?>"></div>
                                <small class="text-muted"><?php echo htmlspecialchars($item['event_type']); ?></small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold"><?php echo $item['count']; ?> events</span>
                                <span class="text-muted small"><?php echo $item['percentage']; ?>%</span>
                            </div>
                            <div class="progress mt-1" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo $item['percentage']; ?>%; background-color: <?php echo $chartColors[$index % count($chartColors)]; ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Guest Capacity Distribution -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Guest Capacity Distribution</h5>
            </div>
            <div class="card-body">
                <?php if (empty($capacityData)): ?>
                    <div class="chart-placeholder text-center">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div>
                                <i class="fas fa-chart-bar fs-1 mb-3 text-muted"></i>
                                <p class="text-muted">No capacity data available</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-start">
                        <?php foreach ($capacityData as $index => $item): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><?php echo $item['capacity_range']; ?> guests</span>
                                <span class="fw-bold"><?php echo $item['count']; ?> events</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo ($item['count'] / $totalEvents) * 100; ?>%; background-color: <?php echo $chartColors[$index % count($chartColors)]; ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Performing Facilities -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Top Performing Facilities</h5>
    </div>
    <div class="card-body">
        <?php if (empty($facilitiesData)): ?>
            <div class="chart-placeholder text-center">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div>
                        <i class="fas fa-building fs-1 mb-3 text-muted"></i>
                        <p class="text-muted">No facility data available</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th class="text-end">Events</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Avg. Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facilitiesData as $facility): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="w-3 h-3 rounded-circle me-2" style="background-color: #00b8a9;"></div>
                                    <?php echo htmlspecialchars($facility['venue']); ?>
                                </div>
                            </td>
                            <td class="text-end"><?php echo $facility['events']; ?></td>
                            <td class="text-end fw-bold text-primary">₱<?php echo number_format($facility['revenue'], 2); ?></td>
                            <td class="text-end text-muted">
                                ₱<?php echo number_format($facility['revenue'] / max($facility['events'], 1), 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Monthly Events & Revenue Trend -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Monthly Events & Revenue Trend (<?php echo $currentYear; ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($monthlyTrendData)): ?>
            <div class="chart-placeholder text-center">
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div>
                        <i class="fas fa-chart-line fs-1 mb-3 text-muted"></i>
                        <p class="text-muted">No trend data available</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Events</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Avg. per Event</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthlyTrendData as $month): ?>
                        <tr>
                            <td><?php echo $month['month']; ?></td>
                            <td class="text-end"><?php echo $month['events']; ?></td>
                            <td class="text-end fw-bold text-primary">₱<?php echo number_format($month['revenue'], 2); ?></td>
                            <td class="text-end text-muted">
                                ₱<?php echo number_format($month['revenue'] / max($month['events'], 1), 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Additional Metrics - Only Average Duration -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="stat-icon-large mb-3">
                    <i class="fas fa-clock text-primary"></i>
                </div>
                <h6 class="text-muted mb-2">Average Event Duration</h6>
                <h3 class="text-primary mb-1"><?php echo number_format($additionalMetrics['avg_duration'] ?? 0, 1); ?> hrs</h3>
                <p class="text-success small mb-0">Based on arrival times</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Quick Statistics</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3 mb-3">
                <div class="border-end">
                    <h4 class="text-primary mb-1"><?php echo $reportStats['events_completed'] ?? 0; ?></h4>
                    <small class="text-muted">Completed Events</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="border-end">
                    <h4 class="text-primary mb-1"><?php echo $reportStats['total_clients'] ?? 0; ?></h4>
                    <small class="text-muted">Unique Clients</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="border-end">
                    <h4 class="text-primary mb-1">₱<?php echo number_format($reportStats['total_revenue'] ?? 0, 2); ?></h4>
                    <small class="text-muted">Total Revenue</small>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <h4 class="text-primary mb-1"><?php echo round($reportStats['avg_event_size'] ?? 0); ?></h4>
                <small class="text-muted">Avg. Guests/Event</small>
            </div>
        </div>
    </div>
</div>

<script>
function exportReports() {
    // Generate a simple CSV export
    const data = [
        ['Metric', 'Value'],
        ['Total Revenue', '₱<?php echo number_format($reportStats['total_revenue'] ?? 0, 2); ?>'],
        ['Events Completed', '<?php echo $reportStats['events_completed'] ?? 0; ?>'],
        ['Total Clients', '<?php echo $reportStats['total_clients'] ?? 0; ?>'],
        ['Average Event Size', '<?php echo round($reportStats['avg_event_size'] ?? 0); ?>']
    ];
    
    const csvContent = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `event-reports-<?php echo date('Y-m-d'); ?>.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}
</script>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
    --white: #ffffff;
    --form-bg: #f9fbfc;
}

/* Header Card */
.header-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 184, 169, 0.3);
}

.header-card h1 {
    margin: 0;
    font-weight: 600;
}

.header-card p {
    opacity: 0.9;
    margin: 0.5rem 0 0 0;
}

/* Stat Cards */
.stat-card {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    border-left: 4px solid var(--primary);
    height: 100%;
    transition: all 0.3s ease;
    border: 1px solid rgba(0, 184, 169, 0.1);
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
    background: var(--primary-light);
    font-size: 1.5rem;
}

.stat-icon-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-light);
    font-size: 2rem;
    margin: 0 auto;
}

/* Cards */
.card {
    border: 1px solid rgba(0, 184, 169, 0.1);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    background: var(--white);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 184, 169, 0.15);
}

.card-header {
    background: var(--primary-light);
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
    padding: 1.25rem;
    font-weight: 600;
    color: var(--primary-dark);
    border-radius: 12px 12px 0 0 !important;
}

.card-body {
    padding: 1.5rem;
}

/* Chart Placeholder */
.chart-placeholder {
    background: var(--primary-light);
    border: 2px dashed rgba(0, 184, 169, 0.3);
    border-radius: 12px;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-dark);
}

/* Progress Bars */
.progress {
    background-color: var(--primary-light);
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    border-radius: 10px;
    transition: width 0.6s ease;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
}

/* Tables */
.table {
    margin-bottom: 0;
}

.table th {
    border-bottom: 2px solid var(--primary);
    font-weight: 600;
    background: var(--primary-light);
    color: var(--primary-dark);
    padding: 1rem;
}

.table td {
    border-color: rgba(0, 184, 169, 0.1);
    vertical-align: middle;
    padding: 1rem;
}

.table-hover tbody tr:hover {
    background-color: var(--primary-light);
}

/* Buttons */
.btn-light {
    background: var(--white);
    border: 1px solid rgba(0, 184, 169, 0.2);
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    color: var(--primary-dark);
    transition: all 0.3s ease;
}

.btn-light:hover {
    background: var(--primary-light);
    border-color: var(--primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.2);
}

/* Text Colors */
.text-primary {
    color: var(--primary) !important;
}

.text-success {
    color: var(--primary) !important;
}

/* Borders */
.border-end {
    border-right: 1px solid rgba(0, 184, 169, 0.2) !important;
}

/* Utility Classes */
.w-3 {
    width: 12px;
}

.h-3 {
    height: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stat-card {
        padding: 1.25rem;
    }
    
    .chart-placeholder {
        height: 150px;
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .border-end {
        border-right: none !important;
        border-bottom: 1px solid rgba(0, 184, 169, 0.2);
        padding-bottom: 1rem;
        margin-bottom: 1rem;
    }
    
    .header-card {
        padding: 1rem;
        text-align: center;
    }
    
    .header-card h1 {
        font-size: 1.5rem;
    }
}

/* Animation for better UX */
.stat-card, .card {
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

/* Print Styles */
@media print {
    .btn, .card-header, .header-card .col-auto {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    
    .stat-card {
        box-shadow: none !important;
        border: 1px solid #000 !important;
    }
    
    .progress-bar {
        background: #000 !important;
    }
}

/* Custom scrollbar for tables */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: var(--primary-light);
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--primary-dark);
}
</style>