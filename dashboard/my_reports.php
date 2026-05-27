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

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-clipboard-list me-2"></i>My Maintenance Reports
                    </h5>
                    <a href="user_dashboard.php?page=report_issue" class="btn btn-sm" style="background-color: #00b8a9; color: white;">
                        <i class="fas fa-plus me-1"></i>Report New Issue
                    </a>
                </div>
                <div class="card-body">
                    <div id="reportsContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading your reports...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const USER_ID = <?php echo $user_id; ?>;

async function fetchMyReports() {
    const container = document.getElementById('reportsContainer');
    try {
        const response = await fetch('maintenance_api/get_user_issues.php?user_id=' + USER_ID);
        const data = await response.json();
        
        if (data.error) {
            container.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
            return;
        }
        
        if (data.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                    <p class="text-muted">You haven't submitted any maintenance reports yet.</p>
                    <a href="user_dashboard.php?page=report_issue" class="btn btn-sm" style="background-color: #00b8a9; color: white;">
                        <i class="fas fa-plus me-1"></i>Report an Issue
                    </a>
                </div>`;
            return;
        }
        
        let html = `<div class="table-responsive"><table class="table table-hover">
            <thead class="table-light">
                <tr><th>ID</th><th>Facility</th><th>Title</th><th>Severity</th><th>Status</th><th>Reported</th><th>Actions</th></tr>
            </thead>
            <tbody>`;
        
        for (const issue of data) {
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
            
            html += `<tr>
                <td>#${issue.id}</td>
                <td>${escapeHtml(issue.facility_name)}</td>
                <td>${escapeHtml(issue.title)}</td>
                <td>${severityBadge}</td>
                <td>${statusBadge}</td>
                <td>${new Date(issue.created_at).toLocaleDateString()}</td>
                <td><button class="btn btn-sm btn-outline-primary view-details" data-id="${issue.id}">View</button></td>
            </tr>`;
        }
        
        html += `</tbody></table></div>`;
        container.innerHTML = html;
        
        document.querySelectorAll('.view-details').forEach(btn => {
            btn.addEventListener('click', () => showIssueDetails(btn.dataset.id));
        });
        
    } catch (error) {
        container.innerHTML = `<div class="alert alert-danger">Failed to load reports. Please try again.</div>`;
    }
}

async function showIssueDetails(issueId) {
    const modalHtml = `
        <div class="modal fade" id="issueModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #00b8a9; color: white;">
                        <h5 class="modal-title">Issue Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="issueModalBody">
                        <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>`;
    
    if (!document.getElementById('issueModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('issueModal'));
    const modalBody = document.getElementById('issueModalBody');
    modal.show();
    
    try {
        const response = await fetch('maintenance_api/get_user_issues.php?id=' + issueId + '&user_id=' + USER_ID);
        const issue = await response.json();
        
        if (issue.error) {
            modalBody.innerHTML = `<div class="alert alert-danger">${issue.error}</div>`;
            return;
        }
        
        let notesHtml = '';
        if (issue.notes && issue.notes.length > 0) {
            notesHtml = `<div class="mt-4"><h6 class="fw-bold">Progress Notes</h6>`;
            for (const note of issue.notes) {
                notesHtml += `<div class="border-start ps-3 mb-3">
                    <small class="text-muted">${new Date(note.created_at).toLocaleString()}</small>
                    <p class="mb-0">${escapeHtml(note.note)}</p>
                </div>`;
            }
            notesHtml += `</div>`;
        }
        
        modalBody.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold text-muted small">Issue ID</label>
                    <p>#${issue.id}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold text-muted small">Facility</label>
                    <p>${escapeHtml(issue.facility_name)}</p>
                </div>
                <div class="col-12 mb-3">
                    <label class="fw-bold text-muted small">Title</label>
                    <p>${escapeHtml(issue.title)}</p>
                </div>
                <div class="col-12 mb-3">
                    <label class="fw-bold text-muted small">Description</label>
                    <p class="bg-light p-2 rounded">${escapeHtml(issue.description)}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-bold text-muted small">Severity</label>
                    <p>${issue.severity}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-bold text-muted small">Status</label>
                    <p>${issue.status}</p>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="fw-bold text-muted small">Reported On</label>
                    <p>${new Date(issue.created_at).toLocaleString()}</p>
                </div>
                ${issue.resolved_at ? `<div class="col-md-6 mb-3">
                    <label class="fw-bold text-muted small">Resolved On</label>
                    <p>${new Date(issue.resolved_at).toLocaleString()}</p>
                </div>` : ''}
                ${issue.assigned_name ? `<div class="col-md-6 mb-3">
                    <label class="fw-bold text-muted small">Assigned To</label>
                    <p>${escapeHtml(issue.assigned_name)}</p>
                </div>` : ''}
            </div>
            ${notesHtml}`;
        
    } catch (error) {
        modalBody.innerHTML = `<div class="alert alert-danger">Failed to load issue details.</div>`;
    }
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

document.addEventListener('DOMContentLoaded', fetchMyReports);
</script>