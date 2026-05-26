<?php
// notification_admin.php
session_start();
// Add your authentication check here
$current_page = 'notification_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - MCC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
        
        .notification-item {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 10px;
            background: var(--white);
        }
        
        .notification-item.unread {
            border-left-color: var(--primary);
            background-color: var(--primary-light);
        }
        
        .notification-item:hover {
            background-color: #f0f8f7;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 184, 169, 0.1);
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 184, 169, 0.2);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--primary-light);
            padding: 1.25rem;
        }
        
        #notificationsContainer {
            min-height: 400px;
        }
        
        .badge.bg-primary {
            background-color: var(--primary) !important;
        }
        
        .badge.bg-success {
            background-color: var(--primary) !important;
        }
        
        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: var(--white);
        }
        
        .progress-bar.bg-success {
            background-color: var(--primary) !important;
        }
        
        .progress-bar.bg-warning {
            background-color: #ffc107 !important;
        }
        
        .progress-bar.bg-info {
            background-color: var(--primary) !important;
            opacity: 0.8;
        }
        
        .progress-bar.bg-primary {
            background-color: var(--primary-dark) !important;
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
        <!-- Include sidebar from admin_dashboard.php -->
        <?php include 'admin_dashboard.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <!-- NEW: BEAUTIFUL HEADER BANNER -->
                <div class="dashboard-banner position-relative">
                    <div>
                        <h1>Notifications</h1>
                        <p>System alerts and user notifications • <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <i class="bi bi-bell-fill banner-icon"></i>
                </div>

                <div class="row">
                    <!-- Notifications List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Notifications</h5>
                                <span class="badge bg-primary" id="headerBadge">Loading...</span>
                            </div>
                            <div class="card-body">
                                <div id="notificationsContainer">
                                    <!-- Notifications will be loaded here dynamically -->
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-success" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="text-muted mt-3">Loading notifications...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Stats Sidebar -->
                    <div class="col-md-4">
                        <!-- Quick Stats Card -->
                        <div class="card stats-card">
                            <div class="card-body">
                                <h5 class="card-title text-white">
                                    <i class="bi bi-graph-up me-2"></i>Quick Stats
                                </h5>
                                <div class="stats-content">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Unread Notifications:</span>
                                        <strong class="fs-5">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Today:</span>
                                        <strong>-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>This Week:</span>
                                        <strong>-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Reservation Alerts:</span>
                                        <strong>-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Event Proposals:</span>
                                        <strong>-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Summary -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-pie-chart me-2"></i>Notification Summary
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3" id="summaryContent">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Reservations</small>
                                        <small>0%</small>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 0%"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Event Proposals</small>
                                        <small>0%</small>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-warning" style="width: 0%"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Payments</small>
                                        <small>0%</small>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: 0%"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>System</small>
                                        <small>0%</small>
                                    </div>
                                    <div class="progress mb-3" style="height: 6px;">
                                        <div class="progress-bar bg-secondary" style="width: 0%"></div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Users</small>
                                        <small>0%</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// Load admin notifications
function loadAdminNotifications() {
    const container = document.getElementById('notificationsContainer');
    
    // Show loading state
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3">Loading notifications...</p>
        </div>
    `;

    fetch('../api/get_admin_notifs.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Admin notifications data:', data);
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load notifications');
            }
            
            renderNotifications(data);
            updateStats(data.stats, data.summary);
        })
        .catch(error => {
            console.error('Error loading admin notifications:', error);
            showError(error.message);
        });
}

// Render notifications
function renderNotifications(data) {
    const container = document.getElementById('notificationsContainer');
    
    if (!data.notifications || data.notifications.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-bell-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No notifications yet</h5>
                <p class="text-muted">You'll see system notifications here</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    data.notifications.forEach(notification => {
        const iconClass = getNotificationIcon(notification.type);
        const iconBgClass = getIconBgClass(notification.type);
        const statusBadge = notification.status === 'unread' ? 
            '<span class="badge bg-success position-absolute top-0 end-0 m-2">New</span>' : '';
        
        html += `
            <div class="notification-item ${notification.status === 'unread' ? 'unread' : ''} p-3"
                 data-id="${notification.id}" style="cursor: pointer;">
                ${statusBadge}
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <div class="notification-icon ${iconBgClass}">
                            <i class="${iconClass}"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 fw-bold">${notification.title}</h6>
                                <p class="mb-1 text-muted">${notification.message}</p>
                                ${notification.user_name ? 
                                    `<small class="text-muted">From: ${notification.user_name}</small>` : ''}
                            </div>
                            <small class="text-muted">${notification.time_ago}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Add click handlers to mark as read
    document.querySelectorAll('.notification-item.unread').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.id;
            markAdminNotificationAsRead(notificationId, this);
        });
    });
}

// Update stats cards
function updateStats(stats, summary) {
    // Update quick stats
    document.querySelector('.stats-content').innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span>Unread Notifications:</span>
            <strong class="fs-5">${stats.unread || 0}</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span>Today:</span>
            <strong>${stats.today || 0}</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span>This Week:</span>
            <strong>${stats.this_week || 0}</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span>Reservation Alerts:</span>
            <strong>${stats.reservations || 0}</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <span>Event Proposals:</span>
            <strong>${stats.events || 0}</strong>
        </div>
    `;
    
    // Update notification summary
    document.getElementById('summaryContent').innerHTML = `
        <div class="d-flex justify-content-between mb-1">
            <small>Reservations</small>
            <small>${summary.reservations_percent || 0}%</small>
        </div>
        <div class="progress mb-3" style="height: 6px;">
            <div class="progress-bar bg-success" style="width: ${summary.reservations_percent || 0}%"></div>
        </div>
        
        <div class="d-flex justify-content-between mb-1">
            <small>Event Proposals</small>
            <small>${summary.events_percent || 0}%</small>
        </div>
        <div class="progress mb-3" style="height: 6px;">
            <div class="progress-bar bg-warning" style="width: ${summary.events_percent || 0}%"></div>
        </div>
        
        <div class="d-flex justify-content-between mb-1">
            <small>Payments</small>
            <small>${summary.payments_percent || 0}%</small>
        </div>
        <div class="progress mb-3" style="height: 6px;">
            <div class="progress-bar bg-info" style="width: ${summary.payments_percent || 0}%"></div>
        </div>
        
        <div class="d-flex justify-content-between mb-1">
            <small>System</small>
            <small>${summary.system_percent || 0}%</small>
        </div>
        <div class="progress mb-3" style="height: 6px;">
            <div class="progress-bar bg-secondary" style="width: ${summary.system_percent || 0}%"></div>
        </div>
        
        <div class="d-flex justify-content-between mb-1">
            <small>Users</small>
            <small>${summary.users_percent || 0}%</small>
        </div>
        <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-primary" style="width: ${summary.users_percent || 0}%"></div>
        </div>
    `;
    
    // Update header badge
    const headerBadge = document.getElementById('headerBadge');
    if (headerBadge) {
        headerBadge.textContent = `${stats.unread || 0} Unread`;
    }
}

// Mark notification as read
function markAdminNotificationAsRead(notificationId, element) {
    fetch('../api/mark_admin_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            element.classList.remove('unread');
            const badge = element.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
            // Reload stats
            loadAdminNotifications();
        } else {
            console.error('Failed to mark as read:', data.error);
        }
    })
    .catch(error => {
        console.error('Error marking as read:', error);
    });
}

// Helper functions for icons
function getNotificationIcon(type) {
    const icons = {
        'reservation': 'bi bi-calendar-check',
        'event': 'bi bi-file-earmark-text',
        'payment': 'bi bi-credit-card',
        'user': 'bi bi-person-plus',
        'system': 'bi bi-gear'
    };
    return icons[type] || 'bi bi-bell';
}

function getIconBgClass(type) {
    const bgClasses = {
        'reservation': 'bg-success',
        'event': 'bg-warning',
        'payment': 'bg-info',
        'user': 'bg-primary',
        'system': 'bg-secondary'
    };
    return bgClasses[type] || 'bg-secondary';
}

// Error handling
function showError(message) {
    const container = document.getElementById('notificationsContainer');
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            ${message}
            <div class="mt-3">
                <button class="btn btn-outline-danger btn-sm" onclick="loadAdminNotifications()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Try Again
                </button>
            </div>
        </div>
    `;
}

// Auto-load when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadAdminNotifications();
    
    // Optional: Auto-refresh every 30 seconds
    setInterval(loadAdminNotifications, 30000);
});
</script>
</body>
</html>