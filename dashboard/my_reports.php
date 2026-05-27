<?php
// dashboard/my_reports.php
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

<div class="container-fluid px-4">
    <div class="row">
        <div class="col-12">
            <!-- Stats Cards -->
            <div class="row mb-4" id="statsCards" style="display: none;">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Reports</h6>
                                    <h3 class="mb-0" id="totalReports">-</h3>
                                </div>
                                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                    <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Open Issues</h6>
                                    <h3 class="mb-0" id="openReports">-</h3>
                                </div>
                                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">In Progress</h6>
                                    <h3 class="mb-0" id="inProgressReports">-</h3>
                                </div>
                                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                    <i class="fas fa-spinner fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Resolved</h6>
                                    <h3 class="mb-0" id="resolvedReports">-</h3>
                                </div>
                                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Reports Card -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h5 class="mb-0 fw-bold" style="color: #00b8a9;">
                        <i class="fas fa-clipboard-list me-2"></i>My Maintenance Reports
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="filterSelect" style="width: auto;">
                            <option value="all">All Reports</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <input type="text" class="form-control form-control-sm" id="searchReports" placeholder="Search..." style="width: 200px;">
                        <a href="user_dashboard.php?page=report_issue" class="btn btn-sm" style="background-color: #00b8a9; color: white;">
                            <i class="fas fa-plus me-1"></i>New Issue
                        </a>
                        <button class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="reportsContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status" style="color: #00b8a9;"></div>
                            <p class="mt-2 text-muted">Loading your reports...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Issue Details Modal -->
<div class="modal fade" id="issueModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background: linear-gradient(135deg, #00b8a9 0%, #00a89a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-tools me-2"></i>Issue Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="issueModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" style="color: #00b8a9;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const USER_ID = <?php echo $user_id; ?>;
let allReports = [];
let currentFilter = 'all';
let currentSearch = '';

async function fetchMyReports() {
    const container = document.getElementById('reportsContainer');
    
    if (!USER_ID || USER_ID === 0) {
        container.innerHTML = `<div class="alert alert-danger">User not found. Please log in again.</div>`;
        return;
    }
    
    try {
        const response = await fetch(`maintenance_api/get_user_issues.php?user_id=${USER_ID}`);
        const data = await response.json();
        
        console.log('API Response:', data);
        
        if (data.error) {
            container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }
        
        let reports = Array.isArray(data) ? data : (data.data || []);
        
        if (reports.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                    <p class="text-muted">You haven't submitted any maintenance reports yet.</p>
                    <a href="user_dashboard.php?page=report_issue" class="btn btn-sm" style="background-color: #00b8a9; color: white;">
                        <i class="fas fa-plus me-1"></i>Report an Issue
                    </a>
                </div>`;
            document.getElementById('statsCards').style.display = 'none';
            return;
        }
        
        allReports = reports;
        updateStats(reports);
        document.getElementById('statsCards').style.display = 'flex';
        
        let filteredReports = filterReports(reports);
        renderReports(filteredReports);
        
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `<div class="alert alert-danger">Failed to load reports. Please try again. Error: ${error.message}</div>`;
    }
}

function filterReports(reports) {
    let filtered = [...reports];
    
    if (currentFilter !== 'all') {
        filtered = filtered.filter(issue => issue.status === currentFilter);
    }
    
    if (currentSearch) {
        const searchLower = currentSearch.toLowerCase();
        filtered = filtered.filter(issue => 
            (issue.title && issue.title.toLowerCase().includes(searchLower)) ||
            (issue.facility_name && issue.facility_name.toLowerCase().includes(searchLower)) ||
            (issue.id && issue.id.toString().includes(searchLower))
        );
    }
    
    return filtered;
}

function renderReports(reports) {
    const container = document.getElementById('reportsContainer');
    
    if (reports.length === 0) {
        let message = 'No reports found';
        if (currentFilter !== 'all') message += ` with status "${currentFilter}"`;
        if (currentSearch) message += ` matching "${currentSearch}"`;
        
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <p class="text-muted">${message}.</p>
                <button onclick="resetFilters()" class="btn btn-sm" style="background-color: #00b8a9; color: white;">
                    <i class="fas fa-undo me-1"></i>Reset Filters
                </button>
            </div>`;
        return;
    }
    
    let html = `<div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Facility</th>
                    <th>Title</th>
                    <th>Severity</th>
                    <th>Status</th>
                    <th>Facility Status</th>
                    <th>Reported</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>`;
    
    for (const issue of reports) {
        // Severity Badge
        let severityBadge = '';
        if (issue.severity === 'critical') {
            severityBadge = '<span class="badge bg-danger">🔴 Critical</span>';
        } else if (issue.severity === 'high') {
            severityBadge = '<span class="badge bg-warning">🟠 High</span>';
        } else if (issue.severity === 'medium') {
            severityBadge = '<span class="badge bg-info">🟡 Medium</span>';
        } else {
            severityBadge = '<span class="badge bg-secondary">🟢 Low</span>';
        }
        
        // Status Badge
        let statusBadge = '';
        if (issue.status === 'resolved') {
            statusBadge = '<span class="badge bg-success">✅ Resolved</span>';
        } else if (issue.status === 'in_progress') {
            statusBadge = '<span class="badge bg-primary">🔧 In Progress</span>';
        } else if (issue.status === 'closed') {
            statusBadge = '<span class="badge bg-secondary">🔒 Closed</span>';
        } else {
            statusBadge = '<span class="badge bg-warning">📋 Open</span>';
        }
        
        // Facility Availability Badge
        let facilityStatusBadge = '';
        if (issue.is_facility_unavailable == 1) {
            facilityStatusBadge = '<span class="badge bg-danger"><i class="fas fa-ban me-1"></i>Unavailable</span>';
        } else if (issue.status === 'resolved' || issue.status === 'closed') {
            facilityStatusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Available</span>';
        } else {
            facilityStatusBadge = '<span class="badge bg-secondary"><i class="fas fa-question me-1"></i>Unknown</span>';
        }
        
        // Format date
        let reportDate = 'N/A';
        if (issue.created_at) {
            try {
                reportDate = new Date(issue.created_at).toLocaleDateString();
            } catch(e) {
                reportDate = issue.created_at;
            }
        }
        
        html += `<tr>
            <td class="fw-bold">#${issue.id}</td>
            <td>${escapeHtml(issue.facility_name || 'N/A')}</td>
            <td>${escapeHtml(issue.title || 'No Title')}</td>
            <td>${severityBadge}</td>
            <td>${statusBadge}</td>
            <td>${facilityStatusBadge}</td>
            <td>${reportDate}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary view-details" data-id="${issue.id}">
                    <i class="fas fa-eye me-1"></i>View
                </button>
            </td>
        </tr>`;
    }
    
    html += `</tbody>
        </tr>
    </div>`;
    
    container.innerHTML = html;
    
    document.querySelectorAll('.view-details').forEach(btn => {
        btn.addEventListener('click', () => showIssueDetails(btn.dataset.id));
    });
}

function updateStats(reports) {
    const total = reports.length;
    const open = reports.filter(r => r.status === 'open').length;
    const inProgress = reports.filter(r => r.status === 'in_progress').length;
    const resolved = reports.filter(r => r.status === 'resolved' || r.status === 'closed').length;
    
    document.getElementById('totalReports').textContent = total;
    document.getElementById('openReports').textContent = open;
    document.getElementById('inProgressReports').textContent = inProgress;
    document.getElementById('resolvedReports').textContent = resolved;
}

async function showIssueDetails(issueId) {
    const modal = new bootstrap.Modal(document.getElementById('issueModal'));
    const modalBody = document.getElementById('issueModalBody');
    modal.show();
    
    modalBody.innerHTML = `<div class="text-center py-4">
        <div class="spinner-border text-primary" style="color: #00b8a9;"></div>
        <p class="mt-3 text-muted">Loading issue details...</p>
    </div>`;
    
    try {
        const response = await fetch(`maintenance_api/get_user_issues.php?id=${issueId}&user_id=${USER_ID}`);
        const issue = await response.json();
        
        if (issue.error) {
            const foundIssue = allReports.find(r => r.id == issueId);
            if (foundIssue) {
                displayIssueDetails(foundIssue);
                return;
            }
            modalBody.innerHTML = `<div class="alert alert-danger m-3">${issue.error}</div>`;
            return;
        }
        
        displayIssueDetails(issue);
        
    } catch (error) {
        const foundIssue = allReports.find(r => r.id == issueId);
        if (foundIssue) {
            displayIssueDetails(foundIssue);
        } else {
            modalBody.innerHTML = `<div class="alert alert-danger m-3">Failed to load issue details. Please try again.</div>`;
        }
    }
}

function displayIssueDetails(issue) {
    const modalBody = document.getElementById('issueModalBody');
    
    let severityBadge = '';
    if (issue.severity === 'critical') severityBadge = '<span class="badge bg-danger">🔴 Critical</span>';
    else if (issue.severity === 'high') severityBadge = '<span class="badge bg-warning">🟠 High</span>';
    else if (issue.severity === 'medium') severityBadge = '<span class="badge bg-info">🟡 Medium</span>';
    else severityBadge = '<span class="badge bg-secondary">🟢 Low</span>';
    
    let statusBadge = '';
    if (issue.status === 'resolved') statusBadge = '<span class="badge bg-success">✅ Resolved</span>';
    else if (issue.status === 'in_progress') statusBadge = '<span class="badge bg-primary">🔧 In Progress</span>';
    else if (issue.status === 'closed') statusBadge = '<span class="badge bg-secondary">🔒 Closed</span>';
    else statusBadge = '<span class="badge bg-warning">📋 Open</span>';
    
    let facilityStatusBadge = '';
    if (issue.is_facility_unavailable) {
        facilityStatusBadge = `
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Facility Unavailable:</strong> This facility has been marked as unavailable for bookings due to this maintenance issue.
            </div>`;
    } else if (issue.status === 'resolved' || issue.status === 'closed') {
        facilityStatusBadge = `
            <div class="alert alert-success mt-3">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Facility Available:</strong> This facility is now available for bookings.
            </div>`;
    }
    
    let notesHtml = '';
    if (issue.notes && issue.notes.length > 0) {
        notesHtml = `<div class="mt-4">
            <h6 class="fw-bold mb-3">Progress Notes</h6>`;
        for (const note of issue.notes) {
            notesHtml += `<div class="border-start ps-3 mb-3">
                <small class="text-muted">${new Date(note.created_at).toLocaleString()}</small>
                <p class="mb-0 mt-1">${escapeHtml(note.note)}</p>
            </div>`;
        }
        notesHtml += `</div>`;
    }
    
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="fw-bold text-muted small">Issue ID</label>
                <p class="mb-0">#${issue.id}</p>
            </div>
            <div class="col-md-6 mb-3">
                <label class="fw-bold text-muted small">Facility</label>
                <p class="mb-0">${escapeHtml(issue.facility_name || 'N/A')}</p>
            </div>
            <div class="col-12 mb-3">
                <label class="fw-bold text-muted small">Title</label>
                <p class="mb-0">${escapeHtml(issue.title || 'No Title')}</p>
            </div>
            <div class="col-12 mb-3">
                <label class="fw-bold text-muted small">Description</label>
                <p class="bg-light p-2 rounded mb-0">${escapeHtml(issue.description || 'No description provided')}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold text-muted small">Severity</label>
                <p class="mb-0">${severityBadge}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold text-muted small">Status</label>
                <p class="mb-0">${statusBadge}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="fw-bold text-muted small">Reported On</label>
                <p class="mb-0">${issue.created_at ? new Date(issue.created_at).toLocaleString() : 'N/A'}</p>
            </div>
            ${issue.resolved_at ? `<div class="col-md-6 mb-3">
                <label class="fw-bold text-muted small">Resolved On</label>
                <p class="mb-0">${new Date(issue.resolved_at).toLocaleString()}</p>
            </div>` : ''}
            ${issue.assigned_name ? `<div class="col-md-6 mb-3">
                <label class="fw-bold text-muted small">Assigned To</label>
                <p class="mb-0">${escapeHtml(issue.assigned_name)}</p>
            </div>` : ''}
        </div>
        ${facilityStatusBadge}
        ${notesHtml}`;
}

function resetFilters() {
    currentFilter = 'all';
    currentSearch = '';
    document.getElementById('filterSelect').value = 'all';
    document.getElementById('searchReports').value = '';
    renderReports(filterReports(allReports));
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

document.addEventListener('DOMContentLoaded', () => {
    fetchMyReports();
    
    document.getElementById('filterSelect').addEventListener('change', (e) => {
        currentFilter = e.target.value;
        if (allReports.length > 0) {
            renderReports(filterReports(allReports));
        }
    });
    
    document.getElementById('searchReports').addEventListener('input', (e) => {
        currentSearch = e.target.value;
        if (allReports.length > 0) {
            renderReports(filterReports(allReports));
        }
    });
    
    document.getElementById('refreshBtn').addEventListener('click', () => {
        fetchMyReports();
    });
});
</script>