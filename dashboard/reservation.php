<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations - MCC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-light: #e3f8f6;
            --form-bg: #f9fbfc;
            --white: #ffffff;
            --primary: #00b8a9;
            --primary-dark: #00998c;
            --gray-light: #f5f7fa;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
        }

        body {
            background: linear-gradient(135deg, var(--gray-light) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .container-fluid {
            max-width: 1400px;
            padding: 30px 20px;
        }

        /* ================================ */
        /* HEADER - MATCHING DASHBOARD STYLE */
        /* ================================ */
        .dashboard-header {
            margin-bottom: 30px;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
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

        .welcome-card h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .welcome-card p {
            opacity: 0.85;
            margin-bottom: 0;
        }

        /* ================================ */
        /* REST OF THE STYLES */
        /* ================================ */
        .card {
            background: var(--white);
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .reservation-card {
            background: var(--form-bg);
            border-radius: 12px;
            border: 1px solid rgba(227, 248, 246, 0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
        }

        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 184, 169, 0.12);
            border-color: var(--primary-light);
        }

        .reservation-header {
            padding: 22px;
            background: linear-gradient(90deg, rgba(227, 248, 246, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
            border-bottom: 1px solid rgba(227, 248, 246, 0.8);
        }

        .reservation-id {
            font-weight: 700;
            font-size: 1.15rem;
            color: var(--text-primary);
            letter-spacing: -0.2px;
        }

        .reservation-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1edff; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-refund_requested { background: #e7f3ff; color: #0c5460; }
        .status-refund_approved { background: #d4edda; color: #155724; }
        .status-refund_rejected { background: #f8d7da; color: #721c24; }
        .status-refund_processed { background: #d1ecf1; color: #0c5460; }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4.5rem;
            opacity: 0.15;
            margin-bottom: 25px;
            color: var(--primary);
        }

        .empty-state h5 {
            font-weight: 500;
            margin-top: 10px;
            color: var(--text-secondary);
        }

        .nav-tabs {
            border-bottom: 2px solid var(--primary-light);
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-secondary);
            font-weight: 500;
            padding: 12px 24px;
            margin-right: 8px;
            border-radius: 10px 10px 0 0;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            background-color: rgba(227, 248, 246, 0.5);
            color: var(--text-primary);
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 184, 169, 0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 184, 169, 0.2);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 184, 169, 0.3);
        }

        /* MODAL STYLES - Professional Design */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 24px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.4rem;
        }

        .modal-body {
            padding: 30px;
            background: var(--form-bg);
            color: var(--text-primary);
        }

        .modal-footer {
            padding: 20px 30px;
            background: var(--white);
            border-top: 1px solid rgba(227, 248, 246, 0.8);
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Custom Modal Colors */
        #cancelModal .modal-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        #refundRequestModal .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        /* Form Styling */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid rgba(227, 248, 246, 0.8);
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 184, 169, 0.1);
        }

        /* Badge Styling */
        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 15px;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .welcome-icon {
                font-size: 3.5rem;
                right: 15px;
            }
            
            .btn-group {
                flex-wrap: wrap;
                gap: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- ================================ -->
        <!-- HEADER SECTION -->
        <!-- ================================ -->
        <div class="dashboard-header">
            <div class="welcome-card position-relative">
                <h1 class="fw-bold mb-2">My Reservations</h1>
                <p class="mb-0 opacity-75">View and manage all your bookings</p>
                <i class="fas fa-calendar-check welcome-icon"></i>
            </div>
        </div>

        <!-- ================================ -->
        <!-- ACTION BUTTON -->
        <!-- ================================ -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="reservation_form.php" class="btn btn-success btn-lg">
                <i class="fas fa-plus me-2"></i> Make New Reservation
            </a>
        </div>

        <!-- ================================ -->
        <!-- RESERVATIONS CONTAINER -->
        <!-- ================================ -->
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-5" id="reservationTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="showTab('pending', event)">
                            <i class="fas fa-clock me-2"></i>Pending
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showTab('confirmed', event)">
                            <i class="fas fa-check-circle me-2"></i>Confirmed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="showTab('cancelled', event)">
                            <i class="fas fa-times-circle me-2"></i>Cancelled
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div id="pending-reservations" class="tab-content active">
                    <div id="pendingReservations">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2">Loading reservations...</p>
                        </div>
                    </div>
                </div>
                <div id="confirmed-reservations" class="tab-content" style="display:none;">
                    <div id="confirmedReservations"></div>
                </div>
                <div id="cancelled-reservations" class="tab-content" style="display:none;">
                    <div id="cancelledReservations"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <!-- Reservation Details Modal -->
    <div class="modal fade" id="reservationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Reservation Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reservationModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading reservation details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Reservation Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Cancel Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-times-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center fs-5">Are you sure you want to cancel this reservation?</p>
                    <p class="text-muted text-center">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep Reservation</button>
                    <button type="button" class="btn btn-warning text-dark fw-bold" id="confirmCancelBtn">Cancel Reservation</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Request Modal -->
    <div class="modal fade" id="refundRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Request Refund</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Reason for Refund Request</label>
                        <textarea class="form-control" id="refundReason" rows="4" placeholder="Please provide a detailed reason for your refund request..."></textarea>
                        <div class="form-text mt-2">Please be specific about why you're requesting a refund.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold" id="submitRefundRequestBtn">Submit Refund Request</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentReservation = null;
        let currentCancel = null;

        function showTab(status, e) {
            e.preventDefault();
            document.querySelectorAll('#reservationTabs .nav-link').forEach(l => l.classList.remove('active'));
            e.target.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.getElementById(status + '-reservations').style.display = 'block';
            loadReservations(status);
        }

        function loadReservations(status) {
            const container = document.getElementById(status + 'Reservations');
            container.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2">Loading reservations...</p></div>`;

            fetch(`api_reservation/get_user_reservations.php?status=${status}`)
                .then(r => r.json())
                .then(data => {
                    const all = [...(data.reservations || []), ...(data.facility_bookings || [])];
                    container.innerHTML = all.length ? all.map(createCard).join('') : 
                        `<div class="empty-state">
                            <i class="fas fa-calendar-alt"></i>
                            <h5>No ${status} reservations found</h5>
                            <p class="text-muted">You don't have any ${status} reservations at the moment.</p>
                        </div>`;
                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = `<div class="text-danger text-center py-5">
                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                        <h5>Failed to load reservations</h5>
                        <p>Please try again later.</p>
                    </div>`;
                });
        }

        function createCard(data) {
            const isRoom = !!data.reservation_ref;
            const id = isRoom ? data.id : data.booking_id;
            const ref = isRoom ? data.reservation_ref : data.payment_reference;
            const amountPaid = parseFloat(data.amount_paid || 0);
            const total = parseFloat(data.total_amount || data.amount_paid || 0);
            const status = isRoom ? (data.payment_status || data.status || 'pending') : data.status;
            const refundStatus = data.refund_status || 'not_requested';
            const bookingDate = new Date(data.created_at || data.booking_date);
            const formattedDate = bookingDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            return `
                <div class="reservation-card">
                    <div class="reservation-header d-flex justify-content-between align-items-start">
                        <div>
                            <div class="reservation-id">${ref}</div>
                            <div class="reservation-date"><i class="far fa-calendar me-1"></i>${formattedDate}</div>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark me-2">
                                    <i class="fas ${isRoom ? 'fa-bed' : 'fa-dumbbell'} me-1"></i>
                                    ${isRoom ? 'Room Reservation' : 'Facility Booking'}
                                </span>
                            </div>
                        </div>
                        <div class="badge-container">
                            <span class="status-badge status-${status}">${status.toUpperCase()}</span>
                            ${refundStatus !== 'not_requested' ? `<span class="status-badge status-refund_${refundStatus}">REFUND ${refundStatus.replace('_', ' ').toUpperCase()}</span>` : ''}
                        </div>
                    </div>
                    <div class="p-4">
                        <h6 class="fw-bold">${isRoom ? data.room_type : data.facility_name}</h6>
                        ${data.guests ? `<p class="mb-2"><i class="fas fa-user-friends me-2"></i>${data.guests} guests</p>` : ''}
                        ${data.booking_date ? `<p class="mb-3"><i class="far fa-clock me-2"></i>${new Date(data.booking_date).toLocaleDateString()}</p>` : ''}
                        
                        <div class="mt-4 d-flex justify-content-between align-items-end">
                            <div>
                                <strong class="text-success fs-4">₱${amountPaid.toLocaleString()}</strong> 
                                <span class="text-muted">paid of</span>
                                <strong class="text-dark fs-5">₱${total.toLocaleString()}</strong>
                            </div>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewReservation(${id}, '${isRoom ? 'room' : 'facility'}')">
                                    <i class="fas fa-eye me-1"></i> View
                                </button>

                                <!-- PAY NOW BUTTON -->
                                ${(status === 'pending' && amountPaid < total) ? `
                                <a href="/dashboard/gcash_checkout.php?type=${isRoom ? 'room' : 'facility'}&id=${id}&ref=${encodeURIComponent(ref)}&amount=${total - amountPaid}"
                                   class="btn btn-sm btn-success" target="_self">
                                    <i class="fas fa-credit-card me-1"></i> Pay Now
                                </a>` : ''}

                                <!-- CANCEL BUTTON -->
                                ${(status === 'pending' || status === 'confirmed') ? `
                                    <button class="btn btn-sm btn-outline-danger" onclick="cancelReservation(${id}, '${isRoom ? 'room' : 'facility'}')">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </button>` : ''}

                                <!-- REQUEST REFUND BUTTON -->
                                ${(status === 'cancelled' && amountPaid > 0 && refundStatus === 'not_requested') ? `
                                    <button class="btn btn-sm btn-outline-warning text-dark" onclick="requestRefund(${id}, '${isRoom ? 'room' : 'facility'}', '${ref}')">
                                        <i class="fas fa-undo me-1"></i> Request Refund
                                    </button>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function viewReservation(id, type) {
            fetch(`api_reservation/get_reservation_details.php?id=${id}&type=${type}`)
                .then(r => r.json())
                .then(d => {
                    if (!d) {
                        document.getElementById('reservationModalBody').innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                                <h5>Reservation Not Found</h5>
                                <p>The requested reservation could not be found.</p>
                            </div>
                        `;
                    } else {
                        // Format the data for display
                        let html = `<div class="reservation-details">`;
                        html += `<h6 class="fw-bold mb-3">Reservation #${d.reservation_ref || d.payment_reference || id}</h6>`;
                        html += `<div class="row">`;
                        
                        // Display key details in a clean format
                        for (const [key, value] of Object.entries(d)) {
                            if (value && typeof value !== 'object') {
                                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                html += `
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body p-3">
                                                <small class="text-muted">${formattedKey}</small>
                                                <div class="fw-bold">${value}</div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                        }
                        
                        html += `</div></div>`;
                        document.getElementById('reservationModalBody').innerHTML = html;
                    }
                    new bootstrap.Modal(document.getElementById('reservationModal')).show();
                })
                .catch(err => {
                    document.getElementById('reservationModalBody').innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-circle text-danger fa-3x mb-3"></i>
                            <h5>Error Loading Details</h5>
                            <p>There was an error loading the reservation details.</p>
                        </div>
                    `;
                    new bootstrap.Modal(document.getElementById('reservationModal')).show();
                });
        }

        function cancelReservation(id, type) {
            currentCancel = { id, type };
            new bootstrap.Modal(document.getElementById('cancelModal')).show();
        }

        function requestRefund(id, type, ref) {
            currentReservation = { id, type, ref };
            document.getElementById('refundReason').value = '';
            new bootstrap.Modal(document.getElementById('refundRequestModal')).show();
        }

        // CANCEL BUTTON
        document.getElementById('confirmCancelBtn').addEventListener('click', function() {
            if (!currentCancel) return;
            const { id, type } = currentCancel;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cancelling...';

            const formData = new FormData();
            formData.append('id', id);
            formData.append('type', type);

            fetch('api_reservation/cancel_reservation.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        // Show success message
                        document.getElementById('cancelModal').querySelector('.modal-body').innerHTML = `
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                <h5>Reservation Cancelled</h5>
                                <p>Your reservation has been cancelled successfully.</p>
                            </div>
                        `;
                        
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(document.getElementById('cancelModal')).hide();
                            loadReservations('cancelled');
                            showTab('cancelled', { preventDefault: () => {} });
                        }, 1500);
                    } else {
                        alert(d.message || 'Error cancelling reservation');
                        this.disabled = false;
                        this.innerHTML = 'Cancel Reservation';
                    }
                })
                .catch(err => {
                    alert('Network error. Please try again.');
                    this.disabled = false;
                    this.innerHTML = 'Cancel Reservation';
                });
        });

        // REQUEST REFUND
        document.getElementById('submitRefundRequestBtn').addEventListener('click', function() {
            if (!currentReservation) return;

            const { id, type, ref } = currentReservation;
            const reason = document.getElementById('refundReason').value.trim();

            if (!reason) {
                alert('Please provide a reason for the refund request');
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';

            fetch('api_reservation/request_refund.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id: id,
                    type: type,
                    reference: ref,
                    refund_reason: reason
                })
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    // Show success message
                    document.getElementById('refundRequestModal').querySelector('.modal-body').innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h5>Refund Request Submitted</h5>
                            <p>Your refund request has been submitted successfully.</p>
                            <p class="small text-muted">You will be notified about the status of your request.</p>
                        </div>
                    `;
                    
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('refundRequestModal')).hide();
                        loadReservations('cancelled');
                    }, 2000);
                } else {
                    alert(d.message || 'Failed to submit refund request');
                    this.disabled = false;
                    this.innerHTML = 'Submit Refund Request';
                }
            })
            .catch(err => {
                alert('Network error. Please try again.');
                this.disabled = false;
                this.innerHTML = 'Submit Refund Request';
            });
        });

        // Load pending reservations on page load
        document.addEventListener('DOMContentLoaded', () => loadReservations('pending'));
    </script>
</body>
</html>