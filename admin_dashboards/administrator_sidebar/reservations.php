<?php
//MCC\admin_dashboards\administrator_sidebar\reservations.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}
$current_page = 'reservations.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - MCC Admin</title>
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
        
        .stats-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            border: none;
        }
        
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 184, 169, 0.15);
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .border-left-warning { border-left: 4px solid #ffc107 !important; }
        .border-left-primary { border-left: 4px solid var(--primary) !important; }
        .border-left-danger { border-left: 4px solid #dc3545 !important; }
        .border-left-success { border-left: 4px solid #198754 !important; }
        
        .text-warning { color: #ffc107 !important; }
        .text-primary { color: var(--primary) !important; }
        .text-danger { color: #dc3545 !important; }
        .text-success { color: #198754 !important; }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d1edff;
            color: #0c5460;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-refund_pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .search-bar {
            max-width: 400px;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            background-color: var(--primary-light);
        }
        
        .badge-status {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }
        
        .action-btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            margin: 0 0.1rem;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 184, 169, 0.05);
        }
        
        .btn-success {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-success:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-success {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-success:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }
        
        .header-card {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark)) !important;
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 184, 169, 0.3);
        }
        
        .badge.bg-light {
            background-color: rgba(255, 255, 255, 0.9) !important;
            color: var(--primary-dark) !important;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 184, 169, 0.25);
        }
        
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 184, 169, 0.25);
        }
        
        .refund-summary {
            background: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }
        
        .cancellation-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'admin_dashboard.php'; ?>
        
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <!-- Header -->
                <div class="header-card">
                    <div class="row align-items-center">
                        <div class="col">
                            <h1><i class="bi bi-calendar-check me-3"></i>Reservations</h1>
                            <p class="mb-0">Manage room & sports/leisure bookings</p>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-light text-dark fs-6">Today: <?php echo date('M j, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stats-card border-left-warning">
                            <div class="stats-number text-warning loading">...</div>
                            <div class="stats-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stats-card border-left-primary">
                            <div class="stats-number text-primary loading">...</div>
                            <div class="stats-label">Confirmed</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stats-card border-left-danger">
                            <div class="stats-number text-danger loading">...</div>
                            <div class="stats-label">Cancelled</div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="stats-card border-left-success">
                            <div class="stats-number text-success loading">...</div>
                            <div class="stats-label">Total Revenue</div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control border-start-0 search-bar" placeholder="Search by name, ref, facility..." id="searchInput">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                                    <select class="form-select" style="width: auto;" id="statusFilter">
                                        <option value="all">All Status</option>
                                        <option value="pending">Pending Approval</option>
                                        <option value="pending_payment">Awaiting GCash</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <select class="form-select" style="width: auto;" id="facilityFilter">
                                        <option value="all">All Types</option>
                                        <option value="Guest Room">Guest Room</option>
                                        <option value="Private Pool">Private Pool</option>
                                        <option value="Tennis Court">Tennis Court</option>
                                        <option value="Badminton Court">Badminton</option>
                                        <option value="Function Hall">Function Hall</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="reservationsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>User</th>
                                        <th>Booking</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Guests/Hrs</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="reservationsTableBody">
                                    <tr><td colspan="9" class="text-center py-5"><div class="spinner-border text-success"></div> Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reservation Details Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reservation Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reservationModalBody">Loading...</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Cancellation Modal -->
    <div class="modal fade" id="adminCancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Cancel Booking
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="adminCancelModalBody">
                    <!-- Dynamic content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                        <i class="bi bi-x-circle me-2"></i>Confirm Cancellation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentCancellationData = null;

        // FETCH ALL RESERVATIONS
        async function fetchReservations() {
            try {
                console.log("Fetching reservations...");
                const res = await fetch('../api/get_reservations.php');
                console.log("Response status:", res.status);
                
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                
                const data = await res.json();
                console.log("API Response:", data);
                
                if (data.error) {
                    throw new Error(data.error);
                } else if (!data.reservations) {
                    throw new Error("API returned invalid data structure");
                } else {
                    displayReservations(data.reservations);
                    updateStats(data.stats || {});
                }
            } catch (e) {
                console.error("Fetch error:", e);
                document.getElementById('reservationsTableBody').innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">Failed to load data: ${e.message}</td></tr>`;
            }
        }

        function displayReservations(reservations) {
            const tbody = document.getElementById('reservationsTableBody');
            tbody.innerHTML = '';

            reservations.forEach(reservation => {
                const row = document.createElement('tr');
                row.className = 'reservation-row';
                row.dataset.status = reservation.status;
                row.dataset.facility = reservation.facility || '';
                
                let actionButton = '';
                
                if (reservation.status === 'pending') {
                    actionButton = `
                        <div class="btn-group">
                            <button class="btn btn-success btn-sm" onclick="approveReservation(${reservation.id}, '${reservation.type}')">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="rejectReservation(${reservation.id}, '${reservation.type}')">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>
<button class="btn btn-info btn-sm" onclick="viewReservation(${reservation.id}, '${reservation.facility_name ? 'facility' : 'room'}')">
    View
</button>
                        </div>
                    `;
                } else if (reservation.status === 'confirmed') {
                    actionButton = `
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm" onclick="cancelReservation(${reservation.id}, '${reservation.type}')">
                                <i class="bi bi-x-circle"></i> Cancel
<button class="btn btn-info btn-sm" onclick="viewReservation(${reservation.id}, '${reservation.facility_name ? 'facility' : 'room'}')">
    View
</button>
                        </div>
                    `;
                } else if (reservation.status === 'cancelled') {
                    actionButton = `<span class="badge bg-danger">Cancelled</span>`;
                }

                row.innerHTML = `
                    <td class="ps-4">${reservation.id || 'N/A'}</td>
                    <td>${reservation.user_name || 'N/A'}<br><small class="text-muted">${reservation.user_email || ''}</small></td>
                    <td>${reservation.facility || 'N/A'}</td>
                    <td>${reservation.date || 'N/A'} ${reservation.end_date ? '→ ' + reservation.end_date : ''}</td>
                    <td>${reservation.time || '-'}</td>
                    <td>${reservation.guests || 'N/A'}</td>
                    <td>${getStatusBadge(reservation.status)}</td>
                    <td>${reservation.total || 'N/A'}</td>
                    <td class="text-end pe-4">${actionButton}</td>
                `;
                
                tbody.appendChild(row);
            });
        }

        function getStatusBadge(status) {
            const statusClass = getStatusClass(status);
            const statusText = getStatusText(status);
            return `<span class="badge ${statusClass} badge-status">${statusText}</span>`;
        }

        // STATUS STYLING & TEXT
        function getStatusClass(s) {
            const map = {
                pending: 'status-pending',
                pending_payment: 'bg-warning text-dark',
                confirmed: 'status-confirmed',
                cancelled: 'status-cancelled',
                completed: 'status-completed',
                refund_pending: 'status-refund_pending'
            };
            return map[s] || 'bg-secondary';
        }
        
        function getStatusText(s) {
            const map = {
                pending: 'Pending Approval',
                pending_payment: 'Awaiting GCash',
                confirmed: 'Confirmed',
                cancelled: 'Cancelled',
                completed: 'Completed',
                refund_pending: 'Refund Pending'
            };
            return map[s] || s;
        }

        // APPROVE RESERVATION
        async function approveReservation(id, type) {
            if (!confirm('Are you sure you want to approve this booking?')) {
                return;
            }

            try {
                const response = await fetch('../api/approve_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id, type })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    fetchReservations();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Network error: ' + error.message);
            }
        }

        // REJECT RESERVATION
        async function rejectReservation(id, type) { 
            if(confirm('Reject this booking?')) await act('../api/reject_reservation.php', {id, type}); 
        }

        // CANCEL RESERVATION WITH REFUND OPTIONS
        async function cancelReservation(id, type) { 
            try {
                const response = await fetch(`../api/get_reservation_details.php?id=${id}&type=${type}`);
                const data = await response.json();
                
                if (!data.success) {
                    alert('Failed to load reservation details: ' + (data.error || 'Unknown error'));
                    return;
                }

                const reservation = data.reservation;
                const amountPaid = reservation.gcash_amount_paid || reservation.amount_paid || 0;
                
                showCancellationModal(id, type, reservation, amountPaid);
                
            } catch (error) {
                console.error('Error loading reservation:', error);
                alert('Failed to load reservation details');
            }
        }

        // SHOW CANCELLATION MODAL
        function showCancellationModal(id, type, reservation, amountPaid) {
            currentCancellationData = { id, type, reservation, amountPaid };
            
            let modalContent = `
                <div class="mb-3">
                    <strong>Booking Details:</strong><br>
                    ${type === 'room' ? 
                        `Room: ${reservation.room_type} #${reservation.room_number || 'N/A'}<br>
                         Guest: ${reservation.full_name}` : 
                        `Facility: ${reservation.facility_name}<br>
                         Date: ${reservation.booking_date}`
                    }
                </div>`;
            
            if (amountPaid > 0) {
                modalContent += `
                    <div class="alert alert-info">
                        <strong>Payment Information:</strong><br>
                        Amount Paid: ₱${parseFloat(amountPaid).toLocaleString()}<br>
                        Payment Method: ${reservation.payment_method || reservation.payment_type || 'N/A'}
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Refund Amount</strong></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="refundAmount" 
                                   value="${amountPaid}" min="0" max="${amountPaid}" step="0.01">
                        </div>
                        <div class="form-text">
                            Quick options: 
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('refundAmount').value = '0'">No Refund</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('refundAmount').value = '${amountPaid * 0.5}'">50% Refund</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('refundAmount').value = '${amountPaid}'">Full Refund</button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Cancellation Fee</strong></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="cancellationFee" 
                                   value="0" min="0" max="${amountPaid}" step="0.01">
                        </div>
                    </div>`;
            } else {
                modalContent += `
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        No payment has been made for this booking.
                    </div>`;
            }
            
            modalContent += `
                <div class="mb-3">
                    <label class="form-label"><strong>Cancellation Reason</strong></label>
                    <select class="form-select" id="cancellationReason">
                        <option value="Guest request">Guest request</option>
                        <option value="No payment received">No payment received</option>
                        <option value="Double booking">Double booking</option>
                        <option value="Property issue">Property issue</option>
                        <option value="Weather conditions">Weather conditions</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><strong>Admin Notes</strong></label>
                    <textarea class="form-control" id="adminNotes" rows="3" 
                              placeholder="Additional notes for internal use..."></textarea>
                </div>
                
                <div id="refundSummary" class="refund-summary" style="display: none;">
                    <strong>Refund Summary:</strong><br>
                    <span id="refundSummaryText"></span>
                </div>`;
            
            document.getElementById('adminCancelModalBody').innerHTML = modalContent;
            
            // Setup event listeners for refund calculation
            const refundAmountInput = document.getElementById('refundAmount');
            const cancellationFeeInput = document.getElementById('cancellationFee');
            const refundSummary = document.getElementById('refundSummary');
            const refundSummaryText = document.getElementById('refundSummaryText');
            
            if (refundAmountInput && cancellationFeeInput) {
                const updateRefundSummary = () => {
                    const refund = parseFloat(refundAmountInput.value) || 0;
                    const fee = parseFloat(cancellationFeeInput.value) || 0;
                    
                    if (refund > 0) {
                        refundSummary.style.display = 'block';
                        refundSummaryText.innerHTML = `
                            Refund to Guest: <strong class="text-success">₱${refund.toLocaleString()}</strong><br>
                            Cancellation Fee: <strong class="text-danger">₱${fee.toLocaleString()}</strong><br>
                            Net Amount: <strong>₱${(refund - fee).toLocaleString()}</strong>
                        `;
                    } else {
                        refundSummary.style.display = 'none';
                    }
                };
                
                refundAmountInput.addEventListener('input', updateRefundSummary);
                cancellationFeeInput.addEventListener('input', updateRefundSummary);
                updateRefundSummary();
            }
            
            // Setup confirm button
            document.getElementById('confirmCancelBtn').onclick = processAdminCancellation;
            
            const modal = new bootstrap.Modal(document.getElementById('adminCancelModal'));
            modal.show();
        }

        // PROCESS ADMIN CANCELLATION
        async function processAdminCancellation() {
            if (!currentCancellationData) return;
            
            const { id, type, amountPaid } = currentCancellationData;
            const refundAmount = parseFloat(document.getElementById('refundAmount')?.value) || 0;
            const cancellationFee = parseFloat(document.getElementById('cancellationFee')?.value) || 0;
            const cancellationReason = document.getElementById('cancellationReason').value;
            const adminNotes = document.getElementById('adminNotes').value;

            // Validate amounts
            if (refundAmount < 0 || cancellationFee < 0) {
                alert('Amounts cannot be negative');
                return;
            }

            if (refundAmount + cancellationFee > amountPaid) {
                alert('Refund amount + cancellation fee cannot exceed amount paid');
                return;
            }

            const confirmMessage = refundAmount > 0 ? 
                `Are you sure you want to cancel this booking and process a refund of ₱${refundAmount.toLocaleString()}?` :
                'Are you sure you want to cancel this booking without refund?';

            if (!confirm(confirmMessage)) {
                return;
            }

            try {
                const response = await fetch('../api/cancel_reservation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id,
                        type,
                        refund_amount: refundAmount,
                        cancellation_fee: cancellationFee,
                        cancellation_reason: cancellationReason,
                        admin_notes: adminNotes
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('adminCancelModal'));
                    if (modal) modal.hide();
                    
                    // Show success message
                    alert(result.message);
                    
                    // Refresh the reservations list
                    fetchReservations();
                    
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Cancellation error:', error);
                alert('Network error: ' + error.message);
            }
        }

        // GENERIC ACTION FUNCTION
        async function act(url, body) {
            try {
                const r = await fetch(url, {
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'}, 
                    body: JSON.stringify(body)
                });
                const d = await r.json();
                if(d.success) { 
                    alert('Success!'); 
                    fetchReservations(); 
                } else {
                    alert(d.error || 'Failed');
                }
            } catch (error) {
                console.error('Action error:', error);
                alert('Action failed: ' + error.message);
            }
        }

// VIEW RESERVATION DETAILS — FIXED & ROBUST
// VIEW RESERVATION DETAILS — SIMPLIFIED
function viewReservation(id, type) {
    fetch(`../api/get_reservation_details.php?id=${id}&type=${type}`)
        .then(r => {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.json();
        })
        .then(d => {
            console.log("API Response:", d);
            
            if (d.success && d.reservation) {
                showModal(d.reservation, type, d.screenshot || null);
            } else {
                alert('Failed to load booking: ' + (d.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            alert('Connection failed. Please try again.');
        });
}

function showModal(r, type, screenshotUrl = null) {
    let html = `<div class="row g-3">`;

    if (type === 'room') {
        html += `
            <div class="col-6"><strong>Ref:</strong> ${escapeHtml(r.reservation_ref || 'N/A')}</div>
            <div class="col-6"><strong>Room:</strong> ${escapeHtml(r.room_type)} #${r.room_number || '—'}</div>
            <div class="col-6"><strong>Check-in:</strong> ${r.checkin_date || 'N/A'}</div>
            <div class="col-6"><strong>Check-out:</strong> ${r.checkout_date || 'N/A'}</div>
            <div class="col-12"><strong>Guest:</strong> ${escapeHtml(r.full_name)} – ${escapeHtml(r.email)}</div>
            <div class="col-12"><strong>Total Amount:</strong> ₱${parseFloat(r.total_amount || 0).toLocaleString()}</div>
            <div class="col-12"><strong>Amount Paid:</strong> ₱${parseFloat(r.amount_paid || 0).toLocaleString()}</div>`;
    } else {
        // Facility
        html += `
            <div class="col-6"><strong>Ref:</strong> ${escapeHtml(r.payment_reference || 'N/A')}</div>
            <div class="col-6"><strong>Facility:</strong> ${escapeHtml(r.facility_name || 'N/A')}</div>
            <div class="col-6"><strong>Date:</strong> ${r.booking_date || 'N/A'}</div>
            <div class="col-6"><strong>Time:</strong> ${r.booking_time || '—'}</div>
            <div class="col-6"><strong>Guests:</strong> ${r.guest_count || '—'}</div>
            <div class="col-6"><strong>Hours:</strong> ${r.hours || '—'}</div>
            <div class="col-12"><strong>Total Amount:</strong> ₱${parseFloat(r.total_amount || 0).toLocaleString()}</div>
            <div class="col-12"><strong>Amount Paid:</strong> ₱${parseFloat(r.amount_paid || 0).toLocaleString()}</div>`;
    }

    // Status info
    html += `<div class="col-12"><strong>Status:</strong> ${escapeHtml(r.status || 'N/A')}</div>`;

    // === GCASH SCREENSHOT ===
    const isGCash = (r.payment_method || '').toLowerCase().includes('gcash') || 
                    (r.payment_type || '').toLowerCase().includes('gcash');

    if (screenshotUrl) {
        html += `
            <div class="col-12 mt-4">
                <hr>
                <h6>GCash Payment Proof</h6>
                <div class="text-center">
                    <img src="${screenshotUrl}" 
                         class="img-fluid rounded border shadow-sm" 
                         style="max-height: 320px; cursor: zoom-in;"
                         onerror="this.onerror=null; this.src='/MCC/assets/image-not-found.png'; this.alt='Image not found';"
                         onclick="enlargeImage('${screenshotUrl}')">
                    <br><small class="text-muted">Click to enlarge</small>
                </div>
            </div>`;
    } else if (isGCash && (!r.amount_paid || r.amount_paid == 0)) {
        html += `
            <div class="col-12 mt-4">
                <hr>
                <h6>GCash Payment</h6>
                <p class="text-muted mb-0">Awaiting payment screenshot...</p>
            </div>`;
    }

    html += `</div>`;
    document.getElementById('reservationModalBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('reservationModal')).show();
}

        // ENLARGE IMAGE
        function enlargeImage(src) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position:fixed; top:0; left:0; width:100%; height:100%; 
                background:rgba(0,0,0,0.95); display:flex; align-items:center; 
                justify-content:center; z-index:9999; cursor:zoom-out;
            `;
            overlay.onclick = () => overlay.remove();

            const img = new Image();
            img.src = src;
            img.style.cssText = `
                max-width:94%; max-height:94%; border-radius:12px; 
                box-shadow:0 20px 60px rgba(0,0,0,0.8); object-fit:contain;
            `;

            overlay.appendChild(img);
            document.body.appendChild(overlay);
        }

        // UPDATE STATISTICS
        function updateStats(s) {
            const c = document.querySelectorAll('.stats-number');
            c[0].textContent = s.pending || '0'; c[0].classList.remove('loading');
            c[1].textContent = s.confirmed || '0'; c[1].classList.remove('loading');
            c[2].textContent = s.cancelled || '0'; c[2].classList.remove('loading');
            c[3].textContent = '₱' + (s.revenue || 0).toLocaleString(); c[3].classList.remove('loading');
        }

        // ESCAPE HTML
        function escapeHtml(t) { 
            return typeof t==='string' ? t.replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])) : t; 
        }

        // SEARCH & FILTER
        document.getElementById('searchInput').addEventListener('input', filter);
        document.getElementById('statusFilter').addEventListener('change', filter);
        document.getElementById('facilityFilter').addEventListener('change', filter);
        
        function filter() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            const s = document.getElementById('statusFilter').value;
            const f = document.getElementById('facilityFilter').value;
            
            document.querySelectorAll('.reservation-row').forEach(row => {
                const matchSearch = row.textContent.toLowerCase().includes(q);
                const matchStatus = s==='all' || row.dataset.status === s;
                const matchFacility = f==='all' || row.dataset.facility.includes(f);
                row.style.display = matchSearch && matchStatus && matchFacility ? '' : 'none';
            });
        }

        // START
        document.addEventListener('DOMContentLoaded', fetchReservations);
    </script>
</body>
</html>