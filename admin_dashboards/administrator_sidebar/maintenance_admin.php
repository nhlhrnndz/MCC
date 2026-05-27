<?php
// admin_dashboards/administrator_sidebar/maintenance_admin.php
// Admin page for managing maintenance issues

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? '';

$current_page = 'maintenance_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Management - MCC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
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
            transition: transform 0.2s;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
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
        
        /* Toast Notification Styles */
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
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
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
            .toast-notify {
                min-width: 280px;
            }
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
                <!-- Header Banner -->
                <div class="dashboard-banner position-relative">
                    <div>
                        <h1>Maintenance Management</h1>
                        <p>Track and manage facility maintenance requests • <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <i class="fas fa-tools banner-icon"></i>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Open Issues</div>
                                        <div class="h5 mb-0 font-weight-bold" id="statOpen">0</div>
                                    </div>
                                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">In Progress</div>
                                        <div class="h5 mb-0 font-weight-bold" id="statInProgress">0</div>
                                    </div>
                                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Resolved</div>
                                        <div class="h5 mb-0 font-weight-bold" id="statResolved">0</div>
                                    </div>
                                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Critical</div>
                                        <div class="h5 mb-0 font-weight-bold" id="statCritical">0</div>
                                    </div>
                                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Status Filter</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">All Statuses</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Severity Filter</label>
                                <select class="form-select" id="filterSeverity">
                                    <option value="">All Severities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Search</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by title or facility...">
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-primary w-100" id="refreshBtn" style="background-color: #00b8a9; border: none;">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Issues Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-list me-2"></i>Maintenance Issues</h6>
                    </div>
                    <div class="card-body">
                        <div id="issuesContainer">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Loading issues...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Issue Action Modal -->
    <div class="modal fade" id="issueModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #00b8a9; color: white;">
                    <h5 class="modal-title"><i class="fas fa-clipboard-list me-2"></i>Issue #<span id="modalIssueId"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="issueModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #00b8a9; color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Assign Issue</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assignIssueId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Staff</label>
                        <select class="form-select" id="assignStaffId">
                            <option value="">-- Select staff --</option>
                            <?php
                            require_once dirname(__DIR__, 2) . '/db_connect.php';
                            $staffQuery = "SELECT id, username, fullname FROM admin_users WHERE status = 'active' AND role IN ('admin', 'manager') ORDER BY username";
                            $staffResult = $conn->query($staffQuery);
                            while ($staff = $staffResult->fetch_assoc()) {
                                echo '<option value="' . $staff['id'] . '">' . htmlspecialchars($staff['username'] . ' (' . $staff['fullname'] . ')') . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmAssignBtn" style="background-color: #00b8a9; border: none;">Assign</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #00b8a9; color: white;">
                    <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Update Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="statusIssueId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Status</label>
                        <select class="form-select" id="newStatus">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStatusBtn" style="background-color: #00b8a9; border: none;">Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #00b8a9; color: white;">
                    <h5 class="modal-title"><i class="fas fa-sticky-note me-2"></i>Add Progress Note</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="noteIssueId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Note</label>
                        <textarea class="form-control" id="noteText" rows="4" placeholder="Enter progress update..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmNoteBtn" style="background-color: #00b8a9; border: none;">Add Note</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flag Facility Modal -->
    <div class="modal fade" id="flagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #00b8a9; color: white;">
                    <h5 class="modal-title"><i class="fas fa-flag me-2"></i>Flag Facility</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="flagIssueId">
                    <input type="hidden" id="flagFacilityId">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Action</label>
                        <select class="form-select" id="flagAction">
                            <option value="flag">Flag as Unavailable</option>
                            <option value="unflag">Remove Flag (Make Available)</option>
                        </select>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i>
                        Flagging a facility will mark it as unavailable for new bookings until the issue is resolved.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmFlagBtn" style="background-color: #00b8a9; border: none;">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
            
            const icons = { success: '✓', error: '✗', warning: '⚠', info: 'ℹ' };
            const titles = { success: 'Success', error: 'Error', warning: 'Warning', info: 'Information' };
            const colors = { success: '#28a745', error: '#dc3545', warning: '#ffc107', info: '#17a2b8' };
            
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
            
            setTimeout(() => { this.close(toastId); }, 5000);
        },
        
        close(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => { if (toast && toast.parentNode) toast.remove(); }, 300);
            }
        }
    };

    // API base path
    const API_BASE = '../api/maintenance/';
    
    // Fetch all issues with filters
    async function fetchIssues() {
        const status = document.getElementById('filterStatus').value;
        const severity = document.getElementById('filterSeverity').value;
        const search = document.getElementById('searchInput').value;
        
        let url = API_BASE + 'get_all_issues.php';
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (severity) params.append('severity', severity);
        if (search) params.append('search', search);
        if (params.toString()) url += '?' + params.toString();
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.error) {
                document.getElementById('issuesContainer').innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }
            
            updateStats(data);
            
            if (data.issues.length === 0) {
                document.getElementById('issuesContainer').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No maintenance issues found.</p>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Facility</th>
                                <th>Title</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Reported By</th>
                                <th>Reported On</th>
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            for (const issue of data.issues) {
                html += `
                    <tr>
                        <td>#${issue.id}</td>
                        <td><strong>${escapeHtml(issue.facility_name)}</strong></td>
                        <td>${escapeHtml(issue.title)}</td>
                        <td>${getSeverityBadge(issue.severity)}</td>
                        <td>${getStatusBadge(issue.status)}</td>
                        <td><small>${escapeHtml(issue.reporter_name || 'User #' + issue.reported_by)}</small></td>
                        <td><small>${new Date(issue.created_at).toLocaleDateString()}</small></td>
                        <td><small>${escapeHtml(issue.assigned_name || 'Unassigned')}</small></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary view-issue" data-id="${issue.id}" title="View Details"><i class="fas fa-eye"></i></button>
                            <?php if ($admin_role === 'admin') { ?>
                            <button class="btn btn-sm btn-outline-secondary assign-issue" data-id="${issue.id}" title="Assign Staff"><i class="fas fa-user-check"></i></button>
                            <?php } ?>
                            <button class="btn btn-sm btn-outline-warning status-issue" data-id="${issue.id}" data-status="${issue.status}" title="Update Status"><i class="fas fa-tasks"></i></button>
                            <button class="btn btn-sm btn-outline-info note-issue" data-id="${issue.id}" title="Add Note"><i class="fas fa-sticky-note"></i></button>
                            <?php if ($admin_role === 'admin') { ?>
                            <button class="btn btn-sm btn-outline-danger flag-issue" data-id="${issue.id}" data-facility="${issue.facility_id}" title="Flag Facility"><i class="fas fa-flag"></i></button>
                            <?php } ?>
                        </td>
                    </tr>
                `;
            }
            
            html += `</tbody></table></div>`;
            document.getElementById('issuesContainer').innerHTML = html;
            
            // Attach event handlers
            document.querySelectorAll('.view-issue').forEach(btn => btn.addEventListener('click', () => showIssueDetails(btn.dataset.id)));
            document.querySelectorAll('.assign-issue').forEach(btn => btn.addEventListener('click', () => showAssignModal(btn.dataset.id)));
            document.querySelectorAll('.status-issue').forEach(btn => btn.addEventListener('click', () => showStatusModal(btn.dataset.id, btn.dataset.status)));
            document.querySelectorAll('.note-issue').forEach(btn => btn.addEventListener('click', () => showNoteModal(btn.dataset.id)));
            document.querySelectorAll('.flag-issue').forEach(btn => btn.addEventListener('click', () => showFlagModal(btn.dataset.id, btn.dataset.facility)));
            
        } catch (error) {
            console.error('Error fetching issues:', error);
            document.getElementById('issuesContainer').innerHTML = `<div class="alert alert-danger">Failed to load issues. Please try again.</div>`;
        }
    }
    
    function updateStats(data) {
        const stats = data.stats || { open: 0, in_progress: 0, resolved: 0, critical: 0 };
        document.getElementById('statOpen').textContent = stats.open || 0;
        document.getElementById('statInProgress').textContent = stats.in_progress || 0;
        document.getElementById('statResolved').textContent = stats.resolved || 0;
        document.getElementById('statCritical').textContent = stats.critical || 0;
    }
    
    async function showIssueDetails(issueId) {
        const modal = new bootstrap.Modal(document.getElementById('issueModal'));
        const modalBody = document.getElementById('issueModalBody');
        document.getElementById('modalIssueId').textContent = issueId;
        modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
        modal.show();
        
        try {
            const response = await fetch(API_BASE + `get_issue_details.php?id=${issueId}`);
            const issue = await response.json();
            if (issue.error) { modalBody.innerHTML = `<div class="alert alert-danger">${issue.error}</div>`; return; }
            
            let notesHtml = '';
            if (issue.notes && issue.notes.length > 0) {
                notesHtml = `<div class="mt-4"><h6 class="fw-bold"><i class="fas fa-comments me-2"></i>Progress Notes</h6>`;
                for (const note of issue.notes) {
                    notesHtml += `<div class="border-start ps-3 mb-3" style="border-left-color: #00b8a9 !important;">
                        <small class="text-muted">${new Date(note.created_at).toLocaleString()}</small>
                        <p class="mb-0">${escapeHtml(note.note)}</p>
                        <small class="text-muted">— ${escapeHtml(note.author_name || note.author_type)}</small>
                    </div>`;
                }
                notesHtml += `</div>`;
            }
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="fw-bold text-muted small">Issue ID</label><p>#${issue.id}</p></div>
                    <div class="col-md-6 mb-3"><label class="fw-bold text-muted small">Facility</label><p><strong>${escapeHtml(issue.facility_name)}</strong></p></div>
                    <div class="col-12 mb-3"><label class="fw-bold text-muted small">Title</label><p>${escapeHtml(issue.title)}</p></div>
                    <div class="col-12 mb-3"><label class="fw-bold text-muted small">Description</label><p class="bg-light p-2 rounded">${escapeHtml(issue.description)}</p></div>
                    <div class="col-md-3 mb-3"><label class="fw-bold text-muted small">Severity</label><p>${getSeverityBadge(issue.severity)}</p></div>
                    <div class="col-md-3 mb-3"><label class="fw-bold text-muted small">Status</label><p>${getStatusBadge(issue.status)}</p></div>
                    <div class="col-md-3 mb-3"><label class="fw-bold text-muted small">Reported By</label><p>${escapeHtml(issue.reporter_name || 'User #' + issue.reported_by)}</p></div>
                    <div class="col-md-3 mb-3"><label class="fw-bold text-muted small">Reported On</label><p>${new Date(issue.created_at).toLocaleString()}</p></div>
                    ${issue.resolved_at ? `<div class="col-md-6 mb-3"><label class="fw-bold text-muted small">Resolved On</label><p>${new Date(issue.resolved_at).toLocaleString()}</p></div>` : ''}
                    ${issue.assigned_name ? `<div class="col-md-6 mb-3"><label class="fw-bold text-muted small">Assigned To</label><p>${escapeHtml(issue.assigned_name)}</p></div>` : ''}
                </div>
                ${notesHtml}
            `;
        } catch (error) { modalBody.innerHTML = `<div class="alert alert-danger">Failed to load issue details.</div>`; }
    }
    
    function showAssignModal(issueId) { 
        document.getElementById('assignIssueId').value = issueId; 
        document.getElementById('assignStaffId').value = '';
        new bootstrap.Modal(document.getElementById('assignModal')).show(); 
    }
    
    function showStatusModal(issueId, currentStatus) { 
        document.getElementById('statusIssueId').value = issueId; 
        document.getElementById('newStatus').value = currentStatus; 
        new bootstrap.Modal(document.getElementById('statusModal')).show(); 
    }
    
    function showNoteModal(issueId) { 
        document.getElementById('noteIssueId').value = issueId; 
        document.getElementById('noteText').value = ''; 
        new bootstrap.Modal(document.getElementById('noteModal')).show(); 
    }
    
    function showFlagModal(issueId, facilityId) { 
        document.getElementById('flagIssueId').value = issueId; 
        document.getElementById('flagFacilityId').value = facilityId; 
        new bootstrap.Modal(document.getElementById('flagModal')).show(); 
    }
    
    // Confirm Assign
    document.getElementById('confirmAssignBtn')?.addEventListener('click', async function() {
        const issueId = document.getElementById('assignIssueId').value;
        const staffId = document.getElementById('assignStaffId').value;
        if (!staffId) { Toast.show('Please select a staff member', 'warning'); return; }
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
        
        try {
            const response = await fetch(API_BASE + 'assign_maintenance_task.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ issue_id: issueId, assigned_to: staffId }) 
            });
            const data = await response.json();
            if (data.success) { 
                bootstrap.Modal.getInstance(document.getElementById('assignModal')).hide(); 
                fetchIssues(); 
                Toast.show('Issue assigned successfully', 'success');
            } else { 
                Toast.show(data.error || 'Failed to assign issue', 'error');
            }
        } catch (error) { 
            Toast.show('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
    
    // Confirm Status Update
    document.getElementById('confirmStatusBtn')?.addEventListener('click', async function() {
        const issueId = document.getElementById('statusIssueId').value;
        const newStatus = document.getElementById('newStatus').value;
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        try {
            const response = await fetch(API_BASE + 'update_issue_status.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ issue_id: issueId, status: newStatus }) 
            });
            const data = await response.json();
            if (data.success) { 
                bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide(); 
                fetchIssues(); 
                Toast.show('Status updated successfully', 'success');
            } else { 
                Toast.show(data.error || 'Failed to update status', 'error');
            }
        } catch (error) { 
            Toast.show('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
    
    // Confirm Add Note
    document.getElementById('confirmNoteBtn')?.addEventListener('click', async function() {
        const issueId = document.getElementById('noteIssueId').value;
        const note = document.getElementById('noteText').value;
        if (!note.trim()) { Toast.show('Please enter a note', 'warning'); return; }
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        
        try {
            const response = await fetch(API_BASE + 'add_maintenance_note.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ issue_id: issueId, note: note }) 
            });
            const data = await response.json();
            if (data.success) { 
                bootstrap.Modal.getInstance(document.getElementById('noteModal')).hide(); 
                Toast.show('Note added successfully', 'success');
            } else { 
                Toast.show(data.error || 'Failed to add note', 'error');
            }
        } catch (error) { 
            Toast.show('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
    
    // Confirm Flag Facility
    document.getElementById('confirmFlagBtn')?.addEventListener('click', async function() {
        const issueId = document.getElementById('flagIssueId').value;
        const facilityId = document.getElementById('flagFacilityId').value;
        const action = document.getElementById('flagAction').value;
        
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        try {
            const response = await fetch(API_BASE + 'flag_facility_unavailable.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ issue_id: issueId, facility_id: facilityId, action: action }) 
            });
            const data = await response.json();
            if (data.success) { 
                bootstrap.Modal.getInstance(document.getElementById('flagModal')).hide(); 
                fetchIssues(); 
                Toast.show(data.message, 'success');
            } else { 
                Toast.show(data.error || 'Failed to update facility status', 'error');
            }
        } catch (error) { 
            Toast.show('An error occurred', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
    
    function getSeverityBadge(severity) {
        const badges = { 
            'low': '<span class="badge bg-success">🟢 Low</span>', 
            'medium': '<span class="badge bg-info text-dark">🟡 Medium</span>', 
            'high': '<span class="badge bg-warning text-dark">🟠 High</span>', 
            'critical': '<span class="badge bg-danger">🔴 Critical</span>' 
        };
        return badges[severity] || '<span class="badge bg-secondary">' + severity + '</span>';
    }
    
    function getStatusBadge(status) {
        const badges = { 
            'open': '<span class="badge bg-warning text-dark">📋 Open</span>', 
            'in_progress': '<span class="badge bg-primary">🔧 In Progress</span>', 
            'resolved': '<span class="badge bg-success">✅ Resolved</span>', 
            'closed': '<span class="badge bg-secondary">🔒 Closed</span>' 
        };
        return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
    }
    
    function escapeHtml(str) { 
        if (!str) return ''; 
        return str.replace(/[&<>]/g, function(m) { 
            if (m === '&') return '&amp;'; 
            if (m === '<') return '&lt;'; 
            if (m === '>') return '&gt;'; 
            return m; 
        }); 
    }
    
    // Event listeners
    document.getElementById('filterStatus')?.addEventListener('change', fetchIssues);
    document.getElementById('filterSeverity')?.addEventListener('change', fetchIssues);
    document.getElementById('searchInput')?.addEventListener('input', fetchIssues);
    document.getElementById('refreshBtn')?.addEventListener('click', fetchIssues);
    
    // Initial load
    document.addEventListener('DOMContentLoaded', fetchIssues);
    </script>
</body>
</html>