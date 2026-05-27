<?php
// dashboard/report_issue.php
if (!isset($_SESSION['username'])) {
    header("Location: user_login.php");
    exit();
}

require_once __DIR__ . '/../db_connect.php';
$username = $_SESSION['username'];
$userQuery = $conn->prepare("SELECT id FROM users WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$currentUser = $userResult->fetch_assoc();
$user_id = $currentUser ? $currentUser['id'] : 0;
?>

<!-- Toast Notification CSS -->
<style>
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    .toast-notify {
        min-width: 320px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin-bottom: 10px;
        overflow: hidden;
        animation: slideIn 0.3s ease;
        border-left: 4px solid;
    }
    .toast-notify.success { border-left-color: #28a745; }
    .toast-notify.error { border-left-color: #dc3545; }
    .toast-notify.warning { border-left-color: #ffc107; }
    .toast-notify.info { border-left-color: #17a2b8; }
    
    .toast-header {
        padding: 12px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 500;
    }
    .toast-body {
        padding: 12px 15px;
        color: #333;
        font-size: 14px;
    }
    .toast-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #999;
        transition: color 0.2s;
    }
    .toast-close:hover { color: #333; }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-tools me-2"></i>Report Facility Issue
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Report any damaged equipment, maintenance needs, or safety concerns</p>
                </div>
                <div class="card-body">
                    <form id="reportIssueForm">
                        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Facility *</label>
                                <select class="form-select" name="facility_id" required>
                                    <option value="">Select facility...</option>
                                    <?php
                                    $facilityQuery = "SELECT id, name, type FROM sport_leisure_facilities ORDER BY type, name";
                                    $facilityResult = $conn->query($facilityQuery);
                                    while ($facility = $facilityResult->fetch_assoc()) {
                                        echo '<option value="' . $facility['id'] . '">' . ucfirst($facility['type']) . ' - ' . htmlspecialchars($facility['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Severity *</label>
                                <select class="form-select" name="severity" required>
                                    <option value="low">🟢 Low - Minor issue</option>
                                    <option value="medium">🟡 Medium - Needs attention</option>
                                    <option value="high">🟠 High - Affects usability</option>
                                    <option value="critical">🔴 Critical - Safety hazard</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Issue Title *</label>
                            <input type="text" class="form-control" name="title" placeholder="e.g., Broken light fixture, Leaking faucet" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description *</label>
                            <textarea class="form-control" name="description" rows="4" placeholder="Please provide detailed description of the issue..." required></textarea>
                        </div>
                        
                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle me-2"></i>
                            Our maintenance team will review your report and take action as soon as possible.
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                            <button type="submit" class="btn" style="background-color: #00b8a9; color: white;">Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<script>
// Toast Notification System
const Toast = {
    container: null,
    
    init() {
        this.container = document.getElementById('toastContainer');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            this.container.id = 'toastContainer';
            document.body.appendChild(this.container);
        }
    },
    
    show(message, type = 'success') {
        this.init();
        const toastId = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
        
        const icons = {
            success: '✓',
            error: '✗',
            warning: '⚠',
            info: 'ℹ'
        };
        
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };
        
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast-notify ${type}`;
        toast.id = toastId;
        toast.innerHTML = `
            <div class="toast-header">
                <strong style="color: ${colors[type]}">${icons[type]} ${titles[type]}</strong>
                <button class="toast-close" onclick="Toast.close('${toastId}')">&times;</button>
            </div>
            <div class="toast-body">${message}</div>
        `;
        
        this.container.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            this.close(toastId);
        }, 5000);
    },
    
    close(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (toast && toast.parentNode) {
                    toast.remove();
                }
            }, 300);
        }
    }
};

// Form submission handler
document.getElementById('reportIssueForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get form data
    const facility_id = document.querySelector('[name="facility_id"]').value;
    const severity = document.querySelector('[name="severity"]').value;
    const title = document.querySelector('[name="title"]').value;
    const description = document.querySelector('[name="description"]').value;
    const user_id = document.querySelector('[name="user_id"]').value;
    
    // Validate
    if (!facility_id || !title || !description) {
        Toast.show('Please fill in all required fields', 'warning');
        return;
    }
    
    // Create data object
    const formData = new URLSearchParams();
    formData.append('user_id', user_id);
    formData.append('facility_id', facility_id);
    formData.append('severity', severity);
    formData.append('title', title);
    formData.append('description', description);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalHtml = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';
    
    try {
        const response = await fetch('maintenance_api/submit_issue.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        });
        
        const data = await response.json();
        
        if (data.success) {
            Toast.show(data.message, 'success');
            // Reset form
            this.reset();
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'user_dashboard.php?page=my_reports';
            }, 2000);
        } else {
            Toast.show(data.error || 'Failed to submit report', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.show('Network error. Please try again.', 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    }
});
</script>