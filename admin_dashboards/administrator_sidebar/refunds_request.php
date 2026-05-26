<?php
// File remains at: MCC/admin_dashboards/administrator_sidebar/refunds_request.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../admin_login.php");   // ← now goes two levels up
    exit();
}
$current_page = 'refunds_request.php';   // ← keep your exact filename
?>

<!-- Sidebar is in the SAME folder now, so no subfolder needed -->
<?php include 'admin_dashboard.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Requests - MCC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .main-content { margin-left: 260px; padding: 20px; min-height: 100vh; }
        .header-card { 
            background: linear-gradient(135deg, #00b8a9, #00998c); 
            color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; 
        }
        .refund-card { border-left: 4px solid #ffc107; transition: all 0.3s ease; margin-bottom: 1rem; }
        .refund-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .refund-approved { border-left-color: #198754; }
        .refund-rejected { border-left-color: #dc3545; }
        .refund-processed { border-left-color: #0dcaf0; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d1edff; color: #0c5460; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .badge-processed { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'admin_dashboard.php'; ?>
        
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <div class="header-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1><i class="bi bi-arrow-clockwise me-3"></i>Refund Requests</h1>
                            <p class="mb-0">Manage customer refund requests and processing</p>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-light text-dark fs-6" id="pendingCount">0 Pending</span>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4" id="refundTabs">
                    <li class="nav-item"><a class="nav-link active" href="#" onclick="showRefundTab('pending')">Pending Review</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="showRefundTab('approved')">Approved</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="showRefundTab('processed')">Processed</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" onclick="showRefundTab('rejected')">Rejected</a></li>
                </ul>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Refund ID</th>
                                        <th>Booking Reference</th>
                                        <th>Customer</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Requested</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="refundRequestsTable">
                                    <tr><td colspan="8" class="text-center py-4">Loading refund requests...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Action Modal -->
    <div class="modal fade" id="refundActionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Refund Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="refundActionModalBody">Loading...</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="approveRefundBtn">Approve Refund</button>
                    <button type="button" class="btn btn-warning" id="processPaymentBtn" style="display:none;">Mark as Paid</button>
                    <button type="button" class="btn btn-danger" id="rejectRefundBtn">Reject Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentRefund = null;
    let currentTab = 'pending';

    function loadRefundRequests(status = 'pending') {
        const tbody = document.getElementById('refundRequestsTable');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4"><div class="spinner-border text-success"></div> Loading...</td></tr>';

        fetch(`../api/get_refund_requests.php?status=${status}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${data.error}</td></tr>`;
                    return;
                }
                const requests = data.requests || [];
                if (requests.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4">No ${status} refund requests</td></tr>`;
                    return;
                }
                tbody.innerHTML = requests.map(r => `
                    <tr class="refund-card ${getRefundRowClass(r.status)}">
                        <td><strong>${r.refund_ref || 'N/A'}</strong></td>
                        <td>${r.reservation_ref || r.payment_reference || 'N/A'}</td>
                        <td>${r.full_name || r.user_name || 'Guest'}<br><small class="text-muted">${r.email || r.user_email || ''}</small></td>
                        <td><span class="badge ${r.type === 'room' ? 'bg-primary' : 'bg-info'}">${(r.type || '').toUpperCase()}</span></td>
                        <td class="fw-bold text-success">₱${parseFloat(r.refund_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        <td>${new Date(r.requested_at).toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'})}</td>
                        <td><span class="status-badge badge-${r.status}">${(r.status || '').toUpperCase()}</span></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary btn-sm" onclick="viewRefundDetails(${r.id})"><i class="bi bi-eye"></i></button>
                                ${r.status === 'pending' ? `
                                    <button class="btn btn-success btn-sm" onclick="processRefundAction(${r.id}, 'approve')">Approve</button>
                                    <button class="btn btn-danger btn-sm" onclick="processRefundAction(${r.id}, 'reject')">Reject</button>
                                ` : ''}
                                ${r.status === 'approved' ? `<button class="btn btn-warning btn-sm" onclick="processRefundAction(${r.id}, 'process')">Mark Paid</button>` : ''}
                            </div>
                        </td>
                    </tr>
                `).join('');
            })
            .catch(() => tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">Failed to connect</td></tr>');
    }

    function getRefundRowClass(s) {
        return {pending:'', approved:'refund-approved', rejected:'refund-rejected', processed:'refund-processed'}[s] || '';
    }

    function showRefundTab(s) {
        currentTab = s;
        document.querySelectorAll('#refundTabs .nav-link').forEach(t => t.classList.remove('active'));
        event.target.classList.add('active');
        loadRefundRequests(s);
    }

    function viewRefundDetails(id) {
        fetch(`../api/get_refund_details.php?id=${id}`)
            .then(r => r.json())
            .then(d => {
                if (!d.success) return alert(d.error);
                currentRefund = d.refund;
                document.getElementById('refundActionModalBody').innerHTML = `
                    <div class="row g-3">
                        <div class="col-12"><h5>Refund Request #${d.refund.refund_ref}</h5><hr></div>
                        <div class="col-md-6"><strong>Customer:</strong> ${d.refund.full_name || d.refund.user_name}</div>
                        <div class="col-md-6"><strong>Email:</strong> ${d.refund.email || d.refund.user_email}</div>
                        <div class="col-md-6"><strong>Type:</strong> <span class="badge bg-info">${(d.refund.type || '').toUpperCase()}</span></div>
                        <div class="col-md-6"><strong>Amount:</strong> <strong class="text-success">₱${parseFloat(d.refund.refund_amount).toLocaleString()}</strong></div>
                        <div class="col-12"><strong>Reason:</strong><div class="border p-3 bg-light mt-2">${d.refund.reason || 'Not provided'}</div></div>
                        ${d.refund.admin_notes ? `<div class="col-12"><strong>Admin Notes:</strong><div class="border p-3 bg-warning-subtle">${d.refund.admin_notes}</div></div>` : ''}
                    </div>`;
                document.getElementById('approveRefundBtn').style.display = d.refund.status === 'pending' ? 'inline-block' : 'none';
                document.getElementById('rejectRefundBtn').style.display = d.refund.status === 'pending' ? 'inline-block' : 'none';
                document.getElementById('processPaymentBtn').style.display = d.refund.status === 'approved' ? 'inline-block' : 'none';
                new bootstrap.Modal(document.getElementById('refundActionModal')).show();
            });
    }

    function processRefundAction(id, action) {
        if (!confirm(`Confirm to ${action} this refund?`)) return;
        fetch('../api/process_refund.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({refund_id: id, action: action, admin_notes: ''})
        })
        .then(r => r.json())
        .then(d => {
            alert(d.success ? d.message : d.error);
            if (d.success) {
                bootstrap.Modal.getInstance(document.getElementById('refundActionModal')).hide();
                loadRefundRequests(currentTab);
                updatePendingCount();
            }
        });
    }

    function updatePendingCount() {
        fetch('../api/get_pending_refund_count.php')
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('pendingCount').textContent = `${d.count} Pending`;
                    const badge = document.getElementById('sidebarRefundBadge');
                    if (badge) badge.textContent = d.count;
                }
            });
    }

    document.getElementById('approveRefundBtn').onclick = () => currentRefund && processRefundAction(currentRefund.id, 'approve');
    document.getElementById('rejectRefundBtn').onclick = () => currentRefund && processRefundAction(currentRefund.id, 'reject');
    document.getElementById('processPaymentBtn').onclick = () => currentRefund && processRefundAction(currentRefund.id, 'process');

    document.addEventListener('DOMContentLoaded', () => {
        loadRefundRequests();
        updatePendingCount();
        setInterval(() => { loadRefundRequests(currentTab); updatePendingCount(); }, 30000);
    });
</script>
</html>