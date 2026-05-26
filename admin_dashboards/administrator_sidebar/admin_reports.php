<?php
session_start();
$current_page = 'admin_reports.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - MCC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #00b8a9;
            --primary-dark: #00998c;
            --primary-light: #e3f8f6;
            --body-bg-light: #f5f7fa;
            --white: #ffffff;
        }
        
        body {
            background: linear-gradient(to bottom, var(--body-bg-light), var(--primary-light));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }
        
        /* BEAUTIFUL HEADER BANNER */
        .dashboard-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 18px;
            padding: 2.5rem 2.8rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 184, 169, 0.3);
        }
        
        .dashboard-banner h1 {
            font-size: 2.4rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }
        
        .dashboard-banner p {
            opacity: 0.92;
            font-size: 1.15rem;
            margin: 0.6rem 0 0;
        }
        
        .banner-icon {
            font-size: 7rem;
            opacity: 0.12;
            position: absolute;
            right: 20px;
            bottom: -25px;
            pointer-events: none;
        }
        
        .stat-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            background: var(--white);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 184, 169, 0.15);
        }
        
        .text-primary { color: var(--primary) !important; }
        .text-success { color: var(--primary) !important; }
        .text-warning { color: #ffc107 !important; }
        .text-info { color: var(--primary-dark) !important; }
        
        .progress {
            height: 8px;
        }
        
        .progress-bar.bg-primary { background-color: var(--primary) !important; }
        .progress-bar.bg-success { background-color: var(--primary) !important; }
        .progress-bar.bg-warning { background-color: #ffc107 !important; }
        .progress-bar.bg-info { background-color: var(--primary-dark) !important; }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        
        .export-success {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            border-radius: 8px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            background: var(--white);
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--primary-light);
            padding: 1.25rem;
        }
        
        .btn-light {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.9);
            color: var(--primary-dark);
        }
        
        .btn-light:hover {
            background-color: var(--white);
            border-color: var(--white);
            color: var(--primary-dark);
        }
        
        .border.rounded {
            border-color: var(--primary-light) !important;
            transition: all 0.2s;
        }
        
        .border.rounded:hover {
            border-color: var(--primary) !important;
            box-shadow: 0 2px 8px rgba(0, 184, 169, 0.1);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            .dashboard-banner {
                padding: 2rem;
                text-align: center;
            }
            .dashboard-banner h1 {
                font-size: 2rem;
            }
            .banner-icon {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'admin_dashboard.php'; ?>
        
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <!-- NEW: BEAUTIFUL HEADER BANNER -->
                <div class="dashboard-banner position-relative">
                    <div>
                        <h1>Reports & Analytics</h1>
                        <p>System performance and business insights • <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group">
                            <button class="btn btn-light" onclick="exportReport('csv')">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportReport('csv')">CSV Format</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportReport('pdf')">PDF Format</a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportReport('excel')">Excel Format</a></li>
                            </ul>
                        </div>
                    </div>
                    <i class="bi bi-graph-up banner-icon"></i>
                </div>

                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="loading-spinner">
                    <div class="text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading reports data...</p>
                    </div>
                </div>

                <!-- Content Area (initially hidden) -->
                <div id="reportsContent" style="display: none;">
                    <!-- Key Metrics Row -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h3 class="text-primary" id="totalBookings">-</h3>
                                    <p class="text-muted mb-1">Total Bookings</p>
                                    <small class="text-success" id="bookingGrowth">
                                        <i class="bi bi-arrow-up"></i> <span id="bookingGrowthValue">-</span>% vs last year
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h3 class="text-success" id="avgBookingValue">-</h3>
                                    <p class="text-muted mb-1">Avg. Booking Value</p>
                                    <small class="text-success" id="avgGrowth">
                                        <i class="bi bi-arrow-up"></i> <span id="avgGrowthValue">-</span>% vs last year
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h3 class="text-warning" id="occupancyRate">-</h3>
                                    <p class="text-muted mb-1">Occupancy Rate</p>
                                    <small class="text-success" id="occupancyGrowth">
                                        <i class="bi bi-arrow-up"></i> <span id="occupancyGrowthValue">-</span>% vs last year
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h3 class="text-info" id="activeUsers">-</h3>
                                    <p class="text-muted mb-1">Active Users</p>
                                    <small class="text-success" id="usersGrowth">
                                        <i class="bi bi-arrow-up"></i> <span id="usersGrowthValue">-</span>% vs last month
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column - Charts -->
                        <div class="col-lg-8">
                            <!-- Monthly Revenue Chart -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Monthly Revenue</h5>
                                    <p class="text-muted mb-0 small">Revenue trend for 2025</p>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="revenueChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Facility Bookings Chart -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Facility Bookings</h5>
                                    <p class="text-muted mb-0 small">Total bookings per facility</p>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="facilityBookingsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Analytics -->
                        <div class="col-lg-4">
                            <!-- Event Types -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Event Types</h6>
                                    <p class="text-muted mb-0 small">Most requested event categories</p>
                                </div>
                                <div class="card-body" id="eventTypesContainer">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>

                            <!-- Weekly Reservation Trend -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Weekly Reservation Trend</h6>
                                    <p class="text-muted mb-0 small">Current month performance</p>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="weeklyTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Performing Facilities -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Top Performing Facilities</h6>
                                </div>
                                <div class="card-body" id="topPerformersContainer">
                                    <!-- Will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="alert alert-danger" style="display: none;">
                    <h4><i class="bi bi-exclamation-triangle"></i> Unable to load reports</h4>
                    <p class="mb-0">There was an error loading the reports data. Please try again later.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let revenueChart, facilityChart, weeklyChart;

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadReportsData();
        });

        async function loadReportsData() {
            try {
                console.log('Loading reports data...');
                
                const apiUrl = '../api/reports_admin.php';
                console.log('Testing API URL:', apiUrl);
                
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin'
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Reports data received:', data);
                
                if (data.success) {
                    displayRealData(data);
                } else {
                    throw new Error(data.error || 'Unknown error from API');
                }
                
            } catch (error) {
                console.error('Error loading reports:', error);
                showError();
            }
        }

        function displayRealData(data) {
            // Hide loading spinner, show content
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('reportsContent').style.display = 'block';
            
            // Update metrics
            document.getElementById('totalBookings').textContent = data.metrics.total_bookings;
            document.getElementById('bookingGrowthValue').textContent = data.metrics.booking_growth;
            
            document.getElementById('avgBookingValue').textContent = '₱' + data.metrics.avg_booking_value.toLocaleString();
            document.getElementById('avgGrowthValue').textContent = data.metrics.avg_growth;
            
            document.getElementById('occupancyRate').textContent = data.metrics.occupancy_rate + '%';
            document.getElementById('occupancyGrowthValue').textContent = data.metrics.occupancy_growth;
            
            document.getElementById('activeUsers').textContent = data.metrics.active_users;
            document.getElementById('usersGrowthValue').textContent = data.metrics.users_growth;

            // Create charts with real data
            createRevenueChart(data.monthly_revenue);
            createFacilityBookingsChart(data.facility_bookings);
            createWeeklyTrendChart(data.weekly_trend);
            
            // Update event types with real data
            updateEventTypes(data.event_types);
            
            // Update top performers with real data
            updateTopPerformers(data.top_performers);
        }

        function showError() {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'block';
        }

        function createRevenueChart(monthlyData) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            const labels = monthlyData.map(item => item.month);
            const data = monthlyData.map(item => item.revenue);
            
            if (revenueChart) {
                revenueChart.destroy();
            }
            
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: data,
                        borderColor: '#00b8a9',
                        backgroundColor: 'rgba(0, 184, 169, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { drawBorder: false },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        function createFacilityBookingsChart(facilityData) {
            const ctx = document.getElementById('facilityBookingsChart').getContext('2d');
            
            const labels = facilityData.map(item => item.name);
            const data = facilityData.map(item => item.bookings);
            
            if (facilityChart) {
                facilityChart.destroy();
            }
            
            facilityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Bookings',
                        data: data,
                        backgroundColor: ['#00b8a9', '#00998c', '#ffc107', '#17a2b8', '#6f42c1', '#e83e8c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { drawBorder: false }
                        },
                        x: { grid: { display: false } }
                    }
                }
            });
        }

        function createWeeklyTrendChart(weeklyData) {
            const ctx = document.getElementById('weeklyTrendChart').getContext('2d');
            
            const labels = weeklyData.map(item => item.week);
            const data = weeklyData.map(item => item.reservations);
            
            if (weeklyChart) {
                weeklyChart.destroy();
            }
            
            weeklyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Reservations',
                        data: data,
                        backgroundColor: '#00b8a9',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        function updateEventTypes(eventTypes) {
            const colors = ['primary', 'success', 'warning', 'info'];
            let html = '';
            
            eventTypes.forEach((event, index) => {
                html += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>${event.type}</span>
                        <span>${event.percentage}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-${colors[index % colors.length]}" style="width: ${event.percentage}%"></div>
                    </div>
                </div>`;
            });
            
            document.getElementById('eventTypesContainer').innerHTML = html;
        }

        function updateTopPerformers(performers) {
            const html = `
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <h6 class="text-muted">Most Booked</h6>
                            <h5 class="text-primary mb-1">${performers.most_booked_rooms.name}</h5>
                            <p class="mb-0">${performers.most_booked_rooms.bookings} bookings</p>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <h6 class="text-muted">Highest Revenue</h6>
                            <h5 class="text-success mb-1">${performers.highest_revenue.name}</h5>
                            <p class="mb-0">${performers.highest_revenue.revenue}</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 border rounded">
                            <h6 class="text-muted">Best Utilization</h6>
                            <h5 class="text-warning mb-1">${performers.best_utilization.name}</h5>
                            <p class="mb-0">${performers.best_utilization.rate}% occupied</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 border rounded">
                            <h6 class="text-muted">Fastest Growing</h6>
                            <h5 class="text-info mb-1">${performers.fastest_growing.name}</h5>
                            <p class="mb-0">+${performers.fastest_growing.growth}% vs last year</p>
                        </div>
                    </div>
                </div>`;
            
            document.getElementById('topPerformersContainer').innerHTML = html;
        }

        function exportReport(format = 'csv') {
            // Show loading state
            const exportBtn = event.target.closest('.btn-group')?.querySelector('button') || event.target;
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Exporting...';
            exportBtn.disabled = true;

            try {
                console.log('Starting export in format:', format);
                
                const apiUrl = `../api/export_reports.php?format=${format}`;
                
                // Create a hidden iframe for download
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                iframe.src = apiUrl;
                document.body.appendChild(iframe);
                
                // Remove iframe after download
                setTimeout(() => {
                    document.body.removeChild(iframe);
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                    console.log('Export completed');
                    
                    // Show success message
                    showExportSuccess(format);
                }, 3000);
                
            } catch (error) {
                console.error('Export error:', error);
                alert('Error exporting report: ' + error.message);
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            }
        }

        function showExportSuccess(format) {
            // Create a temporary success alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show export-success';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle"></i> 
                Report exported successfully as ${format.toUpperCase()}!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 3000);
        }
    </script>
</body>
</html>