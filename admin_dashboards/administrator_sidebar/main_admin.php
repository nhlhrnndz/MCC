<?php
// main_admin.php - Dashboard Overview
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}
$admin_name = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Overview - MCC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

    <style>
        :root {
            --primary: #00b8a9;
            --primary-dark: #00998c;
            --light-bg: #e3f8f6;
        }

        body {
            background: linear-gradient(to bottom, #f5f7fa, var(--light-bg));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        /* BEAUTIFUL HEADER BANNER - EXACTLY LIKE USER DASHBOARD */
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

        /* Your original styles below - unchanged */
        .loading { color: #6c757d !important; font-style: italic; }
        .stat-card { transition: transform 0.2s; border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        .border-left-primary, .border-left-success { border-left: 4px solid var(--primary) !important; }
        .text-primary, .text-success { color: var(--primary) !important; }
        .bg-primary, .bg-success { background-color: var(--primary) !important; }
        .bg-primary.bg-opacity-10, .bg-success.bg-opacity-10 { background-color: rgba(0, 184, 169, 0.1) !important; }

        .pending-item { border-left: 4px solid #ffc107; background: #fffbf0; }
        .activity-item { border-left: 4px solid var(--primary); background: #f8fdfc; }
        .utilization-fill { background: var(--primary); }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .text-gray-800 { color: #2d3748 !important; }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .dashboard-banner { padding: 2rem; text-align: center; }
            .dashboard-banner h1 { font-size: 2rem; }
            .banner-icon { display: none; }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Include sidebar -->
        <?php include 'admin_dashboard.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid">

                <!-- NEW: BEAUTIFUL HEADER BANNER -->
                <div class="dashboard-banner position-relative">
                    <div>
                        <h1>Dashboard Overview</h1>
                        <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?> • <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <i class="fas fa-tachometer-alt banner-icon"></i>
                </div>

                <!-- Your original content continues below -->
                <div class="row mb-4">
                    <!-- Total Users -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Users
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 loading">Loading...</div>
                                        <div class="mt-2">
                                            <span class="growth-positive loading">
                                                Loading...
                                            </span>
                                            <span class="text-muted small">from last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                            <i class="bi bi-people"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Reservations -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Reservations
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 loading">Loading...</div>
                                        <div class="mt-2">
                                            <span class="growth-positive loading">
                                                Loading...
                                            </span>
                                            <span class="text-muted small">from last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Reservations -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Reservations
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 loading">Loading...</div>
                                        <div class="mt-2">
                                            <span class="growth-positive loading">
                                                Loading...
                                            </span>
                                            <span class="text-muted small">from last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                            <i class="bi bi-clock-history"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Event Proposals -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info h-100">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Event Proposals
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800 loading">Loading...</div>
                                        <div class="mt-2">
                                            <span class="growth-neutral loading">
                                                Loading...
                                            </span>
                                            <span class="text-muted small">from last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Pending Approvals -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Pending Approvals</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush" id="pendingApprovalsContainer">
                                    <div class="text-center text-muted py-4">Loading approvals...</div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="reservations.php" class="btn btn-outline-primary btn-sm">View All</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush" id="recentActivityContainer">
                                    <div class="text-center text-muted py-4">Loading activity...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facility Utilization -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Facility Utilization</h6>
                            </div>
                            <div class="card-body" id="facilityUtilizationContainer">
                                <div class="text-center text-muted py-4">Loading utilization data...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fetch dashboard statistics
        async function fetchDashboardStats() {
            try {
                console.log('Fetching dashboard stats...');
                const response = await fetch('../api/dashboard_stats.php');
                const data = await response.json();
                
                console.log('Dashboard data received:', data);
                
                if (data.error) {
                    console.error('Error:', data.error);
                    showError('Failed to load dashboard statistics');
                    return;
                }

                // Update Total Users
                updateStatCard(0, data.total_users, data.users_growth);
                
                // Update Total Reservations (Approved/Confirmed)
                updateStatCard(1, data.total_reservations, data.reservations_growth);
                
                // Update Pending Reservations
                updateStatCard(2, data.pending_reservations, data.pending_reservations_growth);
                
                // Update Event Proposals (Card Index 3)
                updateStatCard(3, data.total_event_proposals, data.event_proposals_growth);

                console.log('Dashboard stats updated successfully');

            } catch (error) {
                console.error('Error fetching stats:', error);
                showError('Failed to load dashboard statistics');
            }
        }

        function updateStatCard(cardIndex, value, growth) {
            const statCards = document.querySelectorAll('.stat-card');
            const card = statCards[cardIndex];
            
            const valueElement = card.querySelector('.h5');
            const growthElement = card.querySelector('.growth-positive, .growth-neutral, .growth-negative');
            
            if (valueElement) {
                valueElement.textContent = typeof value === 'number' ? value.toLocaleString() : value;
                valueElement.classList.remove('loading');
            }
            
            if (growthElement) {
                growthElement.classList.remove('loading', 'growth-positive', 'growth-neutral', 'growth-negative');
                
                if (growth > 0) {
                    growthElement.classList.add('growth-positive');
                    growthElement.innerHTML = `<i class="bi bi-arrow-up-short"></i>+${growth}%`;
                } else if (growth < 0) {
                    growthElement.classList.add('growth-negative');
                    growthElement.innerHTML = `<i class="bi bi-arrow-down-short"></i>${growth}%`;
                } else {
                    growthElement.classList.add('growth-neutral');
                    growthElement.innerHTML = `<i class="bi bi-dash"></i>0%`;
                }
            }
        }

        // Fetch pending approvals
        async function fetchPendingApprovals() {
            try {
                const response = await fetch('../api/pending_approvals.php');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    showError('Failed to load pending approvals');
                    return;
                }

                const container = document.getElementById('pendingApprovalsContainer');
                
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-4">No pending approvals</div>';
                    return;
                }

                container.innerHTML = data.map(item => `
                    <div class="list-group-item px-0 py-3 pending-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${escapeHtml(item.title)}</h6>
                                <p class="mb-0 text-muted small">${escapeHtml(item.customer)} • ${escapeHtml(item.date)}</p>
                            </div>
                            <span class="badge bg-warning">Pending</span>
                        </div>
                    </div>
                `).join('');

            } catch (error) {
                console.error('Error fetching pending approvals:', error);
                showError('Failed to load pending approvals');
            }
        }

        // Fetch recent activity
        async function fetchRecentActivity() {
            try {
                const response = await fetch('../api/recent_activity.php');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    showError('Failed to load recent activity');
                    return;
                }

                const container = document.getElementById('recentActivityContainer');
                
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-4">No recent activity</div>';
                    return;
                }

                container.innerHTML = data.map(activity => `
                    <div class="list-group-item px-0 py-3 activity-item">
                        <div class="d-flex align-items-center">
                            <div class="bg-${escapeHtml(activity.color)} bg-opacity-10 rounded p-2 me-3">
                                <i class="bi bi-${escapeHtml(activity.icon)} text-${escapeHtml(activity.color)}"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">${escapeHtml(activity.title)}</h6>
                                <p class="mb-0 text-muted small">${escapeHtml(activity.user)} • ${formatTimeAgo(activity.timestamp)}</p>
                            </div>
                        </div>
                    </div>
                `).join('');

            } catch (error) {
                console.error('Error fetching recent activity:', error);
                showError('Failed to load recent activity');
            }
        }

        // Fetch facility utilization
        async function fetchFacilityUtilization() {
            try {
                const response = await fetch('../api/facility_utilization.php');
                const data = await response.json();
                
                if (data.error) {
                    console.error('Error:', data.error);
                    showError('Failed to load facility utilization');
                    return;
                }

                const container = document.getElementById('facilityUtilizationContainer');
                
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-4">No utilization data available</div>';
                    return;
                }

                container.innerHTML = `
                    <div class="row">
                        ${data.map(facility => `
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>${escapeHtml(facility.name)}</span>
                                    <span class="fw-bold">${facility.utilization}% • ${facility.bookings} bookings</span>
                                </div>
                                <div class="utilization-bar">
                                    <div class="utilization-fill bg-${getUtilizationColor(facility.utilization)}" style="width: ${facility.utilization}%"></div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;

            } catch (error) {
                console.error('Error fetching facility utilization:', error);
                showError('Failed to load facility utilization');
            }
        }

        function getUtilizationColor(percentage) {
            if (percentage >= 80) return 'success';
            if (percentage >= 60) return 'primary';
            if (percentage >= 40) return 'info';
            if (percentage >= 20) return 'warning';
            return 'secondary';
        }

        // Fixed Helper function to format time ago
        function formatTimeAgo(timestamp) {
            if (!timestamp) return 'recently';
            
            try {
                const now = new Date();
                const time = new Date(timestamp);
                
                // Check if the date is valid
                if (isNaN(time.getTime())) {
                    return 'recently';
                }
                
                const diffInSeconds = Math.floor((now - time) / 1000);
                
                if (diffInSeconds < 60) return 'just now';
                if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`;
                if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
                
                const days = Math.floor(diffInSeconds / 86400);
                return `${days} days ago`;
                
            } catch (error) {
                console.error('Error formatting time:', error);
                return 'recently';
            }
        }

        // Helper function to escape HTML
        function escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') return unsafe;
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Show error message
        function showError(message) {
            // You can implement a toast notification system here
            console.error('Dashboard Error:', message);
        }

        // Load all data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchDashboardStats();
            fetchPendingApprovals();
            fetchRecentActivity();
            fetchFacilityUtilization();
            
            // Refresh data every 30 seconds
            setInterval(() => {
                fetchDashboardStats();
                fetchPendingApprovals();
                fetchRecentActivity();
            }, 30000);
        });
    </script>
</body>
</html>