<div class="container-fluid">
    <!-- ========================================= -->
    <!-- PAGE HEADER                               -->
    <!-- ========================================= -->
    <div class="dashboard-header">
        <div class="welcome-card p-4 position-relative mb-4">
            <h1 class="fw-bold mb-2">Notifications</h1>
            <p class="mb-0 opacity-75">Stay updated with admin responses and messages</p>
            <i class="fas fa-bell welcome-icon"></i>
        </div>
    </div>

    <div id="notificationsContainer">
        <!-- Loading state -->
        <div class="text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3">Loading notifications...</p>
        </div>
    </div>
</div>

<script>
function loadNotifications() {
    // Show loading state
    const container = document.getElementById('notificationsContainer');
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3">Loading notifications...</p>
        </div>
    `;

    fetch('api_reservation/get_notification.php')
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response URL:', response.url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notifications data:', data);
            
            const container = document.getElementById('notificationsContainer');
            
            if (!data.success) {
                container.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.error || 'Failed to load notifications'}
                        ${data.debug ? '<br><small>' + data.debug + '</small>' : ''}
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-success" onclick="loadNotifications()">
                            <i class="fas fa-redo me-1"></i> Try Again
                        </button>
                    </div>
                `;
                return;
            }
            
            if (!data.notifications || data.notifications.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No notifications yet</h5>
                        <p class="text-muted">You'll see updates about your bookings here</p>
                        <button class="btn btn-success mt-3" onclick="loadNotifications()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                `;
                return;
            }
            
            let html = '<div class="row g-3">';
            
            data.notifications.forEach(notification => {
                const icon = getNotificationIcon(notification.type, notification.title);
                const iconBgClass = getIconBgClass(notification.type, notification.title);
                const statusBadge = notification.status === 'unread' ? 
                    '<span class="badge bg-success position-absolute top-0 end-0 m-2">New</span>' : '';
                
                html += `
                    <div class="col-12">
                        <div class="card notification-card ${notification.status === 'unread' ? 'notification-unread' : ''} position-relative" 
                             data-id="${notification.id}" style="cursor: pointer;">
                            ${statusBadge}
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="notification-icon ${iconBgClass}">
                                            <i class="${icon}"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="card-title mb-2 fw-bold">${notification.title}</h6>
                                        <p class="card-text text-muted mb-2">${notification.message}</p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>${notification.time_ago}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
            
            // Add click handlers to mark as read - ENHANCED VERSION
            document.querySelectorAll('.notification-card').forEach(card => {
                card.addEventListener('click', function() {
                    const notificationId = this.dataset.id;
                    const isUnread = this.classList.contains('notification-unread');
                    
                    if (isUnread) {
                        markAsRead(notificationId, this);
                    }
                });
            });
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            const container = document.getElementById('notificationsContainer');
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load notifications. Please check your connection and try again.
                    <br><small class="mt-1">Error: ${error.message}</small>
                    <div class="mt-3">
                        <button class="btn btn-outline-danger btn-sm" onclick="loadNotifications()">
                            <i class="fas fa-redo me-1"></i> Try Again
                        </button>
                    </div>
                </div>
            `;
        });
}

function markAsRead(notificationId, cardElement) {
    console.log('Marking notification as read:', notificationId);
    
    fetch('api_reservation/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Mark as read response:', data);
        
        if (data.success) {
            // Update UI immediately
            if (cardElement) {
                cardElement.classList.remove('notification-unread');
                const badge = cardElement.querySelector('.badge');
                if (badge) {
                    badge.remove();
                }
            }
            
            // Optional: Show a subtle success message
            showToast('Notification marked as read', 'success');
        } else {
            console.error('Failed to mark as read:', data.error);
            showToast('Failed to mark as read', 'error');
        }
    })
    .catch(error => {
        console.error('Error marking as read:', error);
        showToast('Error marking as read', 'error');
    });
}

// Helper function to show toast messages
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0`;
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Add to container
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    toastContainer.appendChild(toast);
    
    // Initialize and show
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove after hide
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Your existing helper functions
function getNotificationIcon(type, title) {
    if (title.includes('approved') || title.includes('confirmed')) {
        return 'fas fa-check-circle';
    } else if (title.includes('pending') || title.includes('under review')) {
        return 'fas fa-clock';
    } else if (title.includes('rejected') || title.includes('cancelled')) {
        return 'fas fa-times-circle';
    } else if (title.includes('payment') || title.includes('paid')) {
        return 'fas fa-credit-card';
    } else if (title.includes('action required') || title.includes('required')) {
        return 'fas fa-exclamation-triangle';
    } else if (type === 'event') {
        return 'fas fa-calendar-alt';
    } else if (type === 'facility') {
        return 'fas fa-dumbbell';
    } else if (type === 'reservation') {
        return 'fas fa-bed';
    } else {
        return 'fas fa-info-circle';
    }
}

function getIconBgClass(type, title) {
    if (title.includes('approved') || title.includes('confirmed')) {
        return 'bg-success';
    } else if (title.includes('pending') || title.includes('under review')) {
        return 'bg-warning';
    } else if (title.includes('rejected') || title.includes('cancelled')) {
        return 'bg-danger';
    } else if (title.includes('payment') || title.includes('paid')) {
        return 'bg-info';
    } else if (title.includes('action required') || title.includes('required')) {
        return 'bg-warning';
    } else if (type === 'event') {
        return 'bg-primary';
    } else if (type === 'facility') {
        return 'bg-warning';
    } else if (type === 'reservation') {
        return 'bg-info';
    } else {
        return 'bg-secondary';
    }
}

// Auto-load notifications when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
});
</script>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
}

/* Welcome Card Styles */
.welcome-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 184, 169, 0.3);
}

.welcome-icon {
    font-size: 4rem;
    opacity: 0.2;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

.dashboard-header {
    margin-bottom: 30px;
}

.notification-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
    border-left: 4px solid transparent;
}

.notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.notification-unread {
    border-left-color: var(--primary);
    background-color: var(--primary-light);
}

.notification-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.card-title {
    color: #2c3e50;
    font-size: 1.1rem;
}

.card-text {
    font-size: 0.95rem;
    line-height: 1.5;
}

.badge {
    font-size: 0.7rem;
    padding: 4px 8px;
}

.bg-success { background-color: var(--primary) !important; }
.bg-warning { background-color: #ffc107 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-primary { background-color: #007bff !important; }
.bg-secondary { background-color: #6c757d !important; }

.text-success { color: var(--primary) !important; }
.text-warning { color: #ffc107 !important; }
.text-danger { color: #dc3545 !important; }
.text-info { color: #17a2b8 !important; }

.btn-success {
    background-color: var(--primary);
    border-color: var(--primary);
}

.btn-success:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
}

.spinner-border.text-success {
    color: var(--primary) !important;
}
</style>