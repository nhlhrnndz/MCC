<?php
// dashboard\event_proposal.php - FIXED VERSION WITH CANCEL & PAY NOW
ob_start();

// Check if user is logged in - USE THE SAME SESSION VARIABLE AS YOUR DASHBOARD
if (!isset($_SESSION['username'])) {
    header('Location: user_dashboard.php');
    exit();
}

// Display success message if exists
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear after displaying
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            ' . htmlspecialchars($success_message) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}
?>

<style>
  /* Header Styles */
  .header-container {
    background: linear-gradient(135deg, #00b8a9 0%, #00998c 100%);
    padding: 2rem 1rem;
    margin: -1rem -1rem 2rem -1rem;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .btn-light {
    background-color: #ffffff;
    border: none;
    color: #00b8a9;
    transition: all 0.3s ease;
    border-radius: 10px;
    padding: 12px 24px;
    font-weight: 600;
  }

  .btn-light:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  /* Existing Styles */
  .proposal-tabs {
    position: relative;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 0;
    padding-left: 0;
    display: flex;
    overflow-x: auto;
    white-space: nowrap;
    -ms-overflow-style: none;
    scrollbar-width: none;
  }

  .proposal-tabs::-webkit-scrollbar {
    display: none;
  }

  .proposal-tabs .nav-link {
    color: #333;
    font-weight: 600;
    font-size: 1rem;
    position: relative;
    padding: 8px 16px;
    border: none;
    background: none;
    transition: color 0.3s ease;
    flex-shrink: 0;
  }

  .proposal-tabs .nav-link:hover {
    color: #00b894;
  }

  .proposal-tabs .nav-link.active {
    color: #00b894;
  }

  .tab-underline {
    height: 3px;
    width: 90px;
    background: linear-gradient(to right, #00b894, #00cec9);
    border-radius: 2px;
    box-shadow: 0 0 10px rgba(0, 200, 160, 0.4);
    transition: 0.4s ease;
    position: relative;
    left: 0;
  }

  .status-section {
    display: none;
  }

  .status-section.active {
    display: block;
  }

  .btn-proposal {
    background: linear-gradient(to right, #00b894, #00cec9);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    transition: 0.3s ease;
    width: 100%;
    margin-bottom: 16px;
  }

  .btn-proposal:hover {
    background: linear-gradient(to right, #00a884, #00b7b7);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 180, 130, 0.3);
  }

  .proposal-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
    margin-bottom: 16px;
    overflow: hidden;
  }

  .proposal-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  .proposal-header {
    padding: 16px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .proposal-body {
    padding: 16px;
  }

  .proposal-id {
    font-weight: 700;
    color: #00b894;
    font-size: 1rem;
  }

  .proposal-date {
    color: #6c757d;
    font-size: 0.875rem;
  }

  .proposal-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 8px;
    line-height: 1.3;
  }

  .proposal-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
  }

  .detail-item {
    display: flex;
    flex-direction: column;
  }

  .detail-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 4px;
  }

  .detail-value {
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
  }

  .status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    align-self: flex-start;
  }

  .status-pending {
    background: #fff3cd;
    color: #856404;
  }

  .status-approved {
    background: #d1edff;
    color: #0c5460;
  }

  .status-rejected {
    background: #f8d7da;
    color: #721c24;
  }

  .status-confirmed {
    background: #d4edda;
    color: #155724;
  }

  .status-under-review {
    background: #e2e3e5;
    color: #383d41;
  }

  .status-verification {
    background: #fff3cd;
    color: #856404;
  }

  .status-fully-paid {
    background: #d4edda;
    color: #155724;
  }

  .status-completed {
    background: #d1ecf1;
    color: #0c5460;
  }

  .status-needs-changes {
    background: #f8d7da;
    color: #721c24;
  }

  .status-cancelled {
    background: #f8d7da;
    color: #721c24;
  }

  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
  }

  .empty-state i {
    font-size: 2.5rem;
    margin-bottom: 16px;
    color: #dee2e6;
  }

  .btn-action {
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-right: 8px;
    margin-bottom: 8px;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    width: auto;
    border: 1px solid;
  }

  .btn-view {
    background: #e9ecef;
    color: #495057;
    border-color: #dee2e6;
  }

  .btn-view:hover {
    background: #dee2e6;
  }

  .btn-edit {
    background: #d1edff;
    color: #0c5460;
    border-color: #bee5eb;
  }

  .btn-edit:hover {
    background: #bee5eb;
  }

  .btn-delete {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
  }

  .btn-delete:hover {
    background: #f1b0b7;
    color: #721c24;
  }

  .btn-upload {
    background: #d1edff;
    color: #0c5460;
    border-color: #bee5eb;
  }

  .btn-upload:hover {
    background: #bee5eb;
  }

  .btn-cancel {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
  }

  .btn-cancel:hover {
    background: #f1b0b7;
    color: #721c24;
  }

  .btn-pay {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
  }

  .btn-pay:hover {
    background: #c3e6cb;
    color: #155724;
  }

  .action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
  }

  .payment-status-paid {
    background: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
  }

  .payment-status-pending {
    background: #fff3cd;
    color: #856404;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
  }

  .payment-status-refunded {
    background: #f8d7da;
    color: #721c24;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
  }

  .loading-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #00b894;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }

  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  @media (min-width: 576px) {
    .btn-proposal {
      width: auto;
      margin-bottom: 0;
    }
    
    .proposal-header {
      flex-direction: row;
      justify-content: space-between;
      align-items: center;
    }
    
    .action-buttons {
      margin-top: 0;
      justify-content: flex-end;
    }
  }

  @media (min-width: 768px) {
    .proposal-details {
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .proposal-header, .proposal-body {
      padding: 20px;
    }
    
    .empty-state {
      padding: 60px 20px;
    }
    
    .empty-state i {
      font-size: 3rem;
    }
  }

  /* Delete Confirmation Modal Styles */
  #deleteConfirmationModal .modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  }

  #deleteConfirmationModal .modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  }

  #deleteConfirmationModal .btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    border: none;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  #deleteConfirmationModal .btn-danger:hover {
    background: linear-gradient(135deg, #c82333, #a71e2a);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
  }

  #deleteConfirmationModal .btn-secondary {
    background: #6c757d;
    border: none;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
  }

  #deleteConfirmationModal .btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
  }

  /* Payment Proof Modal Styles */
  #paymentProofViewModal .modal-content {
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  }

  #paymentProofViewModal .modal-header {
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #00b894, #00cec9);
    color: white;
  }

  #paymentProofViewModal .modal-header .btn-close {
    filter: invert(1);
  }

  #paymentProofViewModal img {
    max-width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 8px;
  }

  #paymentProofViewModal embed {
    border: 1px solid #dee2e6;
    border-radius: 8px;
  }

  /* ===========================================
   MISCELLANEOUS
   - Hides scrollbars across browsers for a cleaner look
   =========================================== */
  /* Hide scrollbar for Chrome, Safari and Opera */
  ::-webkit-scrollbar {
    display: none;
  }

  /* Hide scrollbar for IE, Edge and Firefox */
  html, body {
    -ms-overflow-style: none;
    scrollbar-width: none;
  }
</style>

<div class="px-2">

  <!-- Your Gorgeous Header -->
  <div class="header-container">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h1 class="display-6 fw-bold text-white mb-2">Event Proposals</h1>
          <p class="lead text-white mb-0" style="opacity: 0.9;">Submit and manage your event proposals for Malaruhatan Country Club.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
          <button class="btn btn-light btn-lg fw-semibold" onclick="window.location.href='user_dashboard.php?page=event_form_proposal'">
            <i class="fas fa-plus me-2"></i> Submit New Proposal
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- FIXED TABS - NOW CLICKABLE & WORKING -->
  <ul class="nav proposal-tabs" id="proposalTabs">
    <li class="nav-item"><a class="nav-link active" data-status="pending">Pending</a></li>
    <li class="nav-item"><a class="nav-link" data-status="under_review">Under Review</a></li>
    <li class="nav-item"><a class="nav-link" data-status="approved">Approved</a></li>
    <li class="nav-item"><a class="nav-link" data-status="verification">Verification</a></li>
    <li class="nav-item"><a class="nav-link" data-status="confirmed">Confirmed</a></li>
    <li class="nav-item"><a class="nav-link" data-status="completed">Completed</a></li>
    <li class="nav-item"><a class="nav-link" data-status="rejected">Rejected</a></li>
    <li class="nav-item"><a class="nav-link" data-status="cancelled">Cancelled</a></li>
  </ul>
  <div class="tab-underline"></div>

  <!-- FIXED SECTION IDs - NOW MATCH DATA-STATUS -->
  <div id="pending" class="status-section active mt-4"><div id="pendingProposals">Loading...</div></div>
  <div id="under_review" class="status-section mt-4"><div id="under_reviewProposals">Loading...</div></div>
  <div id="approved" class="status-section mt-4"><div id="approvedProposals">Loading...</div></div>
  <div id="verification" class="status-section mt-4"><div id="verificationProposals">Loading...</div></div>
  <div id="confirmed" class="status-section mt-4"><div id="confirmedProposals">Loading...</div></div>
  <div id="completed" class="status-section mt-4"><div id="completedProposals">Loading...</div></div>
  <div id="rejected" class="status-section mt-4"><div id="rejectedProposals">Loading...</div></div>
  <div id="cancelled" class="status-section mt-4"><div id="cancelledProposals">Loading...</div></div>

  <!-- View Proposal Modal -->
<div class="modal fade" id="viewProposalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Event Proposal Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="proposalDetails">
        <!-- Proposal details will be loaded here -->
        <div class="text-center py-4">
          <div class="spinner-border text-primary"></div>
          <p class="mt-2">Loading proposal details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this proposal?</p>
        <p class="text-muted small">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Proposal</button>
      </div>
    </div>
  </div>
</div>

<!-- Cancel Proposal Modal -->
<div class="modal fade" id="cancelProposalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-warning">Cancel Proposal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to cancel this proposal?</p>
        <div class="mb-3">
          <label for="cancellationReason" class="form-label">Cancellation Reason *</label>
          <textarea class="form-control" id="cancellationReason" rows="3" 
                    placeholder="Please provide a reason for cancellation..." required></textarea>
        </div>
        <p class="text-muted small">This action cannot be undone. The proposal will be moved to cancelled status.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning" id="confirmCancelBtn" onclick="cancelProposal()">
          Cancel Proposal
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-success" id="paymentModalLabel">Make Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="paymentMethod" class="form-label">Payment Method *</label>
          <select class="form-control" id="paymentMethod" required>
            <option value="">Select Payment Method</option>
            <option value="gcash">GCash</option>
            <option value="cash">Cash</option>
          </select>
        </div>
        <div class="mb-3">
          <label for="paymentProof" class="form-label">Payment Proof *</label>
          <input type="file" class="form-control" id="paymentProof" 
                 accept="image/jpeg,image/jpg,image/png,image/gif,application/pdf" required>
          <div class="form-text">
            Upload proof of payment (JPG, PNG, GIF, PDF, max 5MB). 
            For GCash: screenshot of transaction. For Cash: photo of receipt.
          </div>
        </div>
        <div class="alert alert-info">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Payment Instructions:</strong><br>
          <strong>GCash:</strong> Send to 09XX-XXX-XXXX<br>
          <strong>Cash:</strong> Pay at Malaruhatan Country Club office<br>
          Your booking will be confirmed after payment verification.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-success" id="submitPaymentBtn" onclick="processPayment()">
          Submit Payment Proof
        </button>
      </div>
    </div>
  </div>
</div>

</div>

<script>
// FIXED & SIMPLIFIED TAB SYSTEM - 100% WORKING
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('#proposalTabs .nav-link');
    const underline = document.querySelector('.tab-underline');

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active from all
            tabs.forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.status-section').forEach(s => s.classList.remove('active'));

            // Add active to clicked
            this.classList.add('active');
            const status = this.getAttribute('data-status');
            const section = document.getElementById(status);
            if (section) section.classList.add('active');

            // Move underline
            const tabRect = this.getBoundingClientRect();
            const containerRect = this.closest('.proposal-tabs').getBoundingClientRect();
            underline.style.width = tabRect.width + 'px';
            underline.style.left = (tabRect.left - containerRect.left) + 'px';

            // Load data
            loadProposals(status);
        });
    });

    // Initial load
    updateUnderline();
    loadProposals('pending');
    
    // Setup delete confirmation
    setupDeleteConfirmation();
});

function updateUnderline() {
    const active = document.querySelector('#proposalTabs .nav-link.active');
    const underline = document.querySelector('.tab-underline');
    if (active && underline) {
        const tabRect = active.getBoundingClientRect();
        const containerRect = active.closest('.proposal-tabs').getBoundingClientRect();
        underline.style.width = tabRect.width + 'px';
        underline.style.left = (tabRect.left - containerRect.left) + 'px';
    }
}

async function loadProposals(status) {
    const container = document.getElementById(status + 'Proposals');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div></div>';

    try {
        const response = await fetch(`event_api/get_proposals.php?status=${status}`);
        const data = await response.json();

        if (data.success && data.proposals?.length > 0) {
            container.innerHTML = data.proposals.map(p => `
                <div class="proposal-card">
                    <div class="proposal-header">
                        <div>
                            <span class="proposal-id">#${p.proposal_id}</span>
                            <span class="proposal-date">${formatDate(p.submitted)}</span>
                        </div>
                        <span class="status-badge status-${p.status}">${formatStatus(p.status)}</span>
                    </div>
                    <div class="proposal-body">
                        <h5 class="proposal-title">${escapeHtml(p.event_title)}</h5>
                        <div class="proposal-details">
                            <div class="detail-item">
                                <span class="detail-label">Event Type</span>
                                <span class="detail-value">${escapeHtml(p.event_type)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Guests</span>
                                <span class="detail-value">${p.expected_guests}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value">${formatDate(p.arrival_date)}</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Venue</span>
                                <span class="detail-value">${escapeHtml(p.venue_preference)}</span>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewProposal(${p.id})">
                                <i class="fas fa-eye me-1"></i>View
                            </button>
                            ${['pending','needs_changes'].includes(p.status) ? `
                                <button class="btn-action btn-delete" onclick="showDeleteConfirmation(${p.id})">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                            ` : ''}
                            
                            ${['pending','under_review','approved','payment_pending_verification','needs_changes'].includes(p.status) ? `
                                <button class="btn-action btn-cancel" onclick="showCancelProposalModal(${p.id})">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                            ` : ''}
                            
                            ${p.status === 'approved' ? `
                                <button class="btn-action btn-pay" onclick="showPaymentModal(${p.id}, 'deposit')">
                                    <i class="fas fa-credit-card me-1"></i>Pay Now
                                </button>
                            ` : ''}
                            
                            ${p.status === 'confirmed' ? `
                                <button class="btn-action btn-pay" onclick="showPaymentModal(${p.id}, 'balance')">
                                    <i class="fas fa-credit-card me-1"></i>Pay Balance
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h5>No ${formatStatus(status)} proposals</h5>
                    <p class="text-muted">You don't have any ${formatStatus(status).toLowerCase()} proposals yet.</p>
                </div>
            `;
        }
    } catch (err) {
        console.error('Error loading proposals:', err);
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <h5>Failed to load proposals</h5>
                <p class="text-muted">Please try refreshing the page.</p>
            </div>
        `;
    }
}

// CANCEL PROPOSAL FUNCTIONS
function showCancelProposalModal(proposalId) {
    currentProposalId = proposalId;
    const modal = new bootstrap.Modal(document.getElementById('cancelProposalModal'));
    modal.show();
}

async function cancelProposal() {
    const cancellationReason = document.getElementById('cancellationReason').value;
    
    if (!cancellationReason.trim()) {
        alert('Please provide a reason for cancellation');
        return;
    }

    const confirmBtn = document.getElementById('confirmCancelBtn');
    const originalText = confirmBtn.innerHTML;
    
    // Show loading state
    confirmBtn.innerHTML = '<span class="loading-spinner me-2"></span>Cancelling...';
    confirmBtn.disabled = true;

    try {
        const response = await fetch('event_api/cancel_proposal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                proposal_id: currentProposalId,
                cancellation_reason: cancellationReason
            })
        });

        // First check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        // Get the response text first to debug
        const responseText = await response.text();
        console.log('Raw response:', responseText);

        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid response from server: ' + responseText.substring(0, 100));
        }

        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelProposalModal'));
            modal.hide();
            
            // Show success message
            showAlert('Proposal cancelled successfully!', 'success');
            
            // Reset form
            document.getElementById('cancellationReason').value = '';
            
            // Reload current tab
            const activeTab = document.querySelector('#proposalTabs .nav-link.active');
            if (activeTab) {
                const status = activeTab.getAttribute('data-status');
                loadProposals(status);
            }
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    } catch (error) {
        console.error('Error cancelling proposal:', error);
        showAlert('Failed to cancel proposal: ' + error.message, 'danger');
    } finally {
        // Reset button
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    }
}

// PAYMENT FUNCTIONS
let currentPaymentType = 'deposit';

function showPaymentModal(proposalId, paymentType = 'deposit') {
    currentProposalId = proposalId;
    currentPaymentType = paymentType;
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    
    // Set modal title based on payment type
    const modalTitle = document.getElementById('paymentModalLabel');
    if (paymentType === 'deposit') {
        modalTitle.textContent = 'Pay Deposit (50%)';
    } else {
        modalTitle.textContent = 'Pay Balance (50%)';
    }
    
    // Reset form
    document.getElementById('paymentMethod').value = '';
    document.getElementById('paymentProof').value = '';
    
    modal.show();
}

async function processPayment() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const fileInput = document.getElementById('paymentProof');
    
    if (!paymentMethod) {
        alert('Please select a payment method');
        return;
    }

    if (!fileInput.files.length) {
        alert('Please upload payment proof');
        return;
    }

    const submitBtn = document.getElementById('submitPaymentBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<span class="loading-spinner me-2"></span>Processing...';
    submitBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('proposal_id', currentProposalId);
        formData.append('payment_proof', fileInput.files[0]);
        formData.append('payment_method', paymentMethod);

        const response = await fetch('event_api/upload_payment_proof.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            modal.hide();
            
            // Show success message
            showAlert('Payment proof uploaded successfully! Awaiting verification.', 'success');
            
            // Reload current tab
            const activeTab = document.querySelector('#proposalTabs .nav-link.active');
            if (activeTab) {
                const status = activeTab.getAttribute('data-status');
                loadProposals(status);
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error processing payment:', error);
        showAlert('Failed to upload payment proof: ' + error.message, 'danger');
    } finally {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// VIEW PROPOSAL FUNCTION
async function viewProposal(proposalId) {
    const modal = new bootstrap.Modal(document.getElementById('viewProposalModal'));
    const detailsContainer = document.getElementById('proposalDetails');
    
    // Show loading state
    detailsContainer.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading proposal details...</p>
        </div>
    `;
    
    modal.show();

    try {
        const response = await fetch(`event_api/get_proposal.php?id=${proposalId}`);
        const data = await response.json();

        if (data.success) {
            const proposal = data.proposal;
            detailsContainer.innerHTML = createProposalDetailsHTML(proposal);
        } else {
            detailsContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load proposal details: ${data.message}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading proposal:', error);
        detailsContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Network error loading proposal details.
            </div>
        `;
    }
}

// CREATE PROPOSAL DETAILS HTML
function createProposalDetailsHTML(proposal) {
    const statusClass = `status-${proposal.status}`;
    const statusText = formatStatus(proposal.status);
    
    return `
        <div class="proposal-details-view">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h4 class="text-success mb-1">${escapeHtml(proposal.event_title)}</h4>
                    <p class="text-muted mb-0">#${proposal.proposal_id}</p>
                </div>
                <span class="status-badge ${statusClass}">${statusText}</span>
            </div>

            <!-- Basic Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-success mb-3">Event Information</h6>
                    <div class="mb-2">
                        <strong>Event Type:</strong> ${escapeHtml(proposal.event_type)}
                    </div>
                    <div class="mb-2">
                        <strong>Arrival Date:</strong> ${formatDate(proposal.arrival_date)}
                    </div>
                    <div class="mb-2">
                        <strong>Arrival Time:</strong> ${formatTime(proposal.arrival_time)}
                    </div>
                    <div class="mb-2">
                        <strong>Expected Guests:</strong> ${proposal.expected_guests}
                    </div>
                    <div class="mb-2">
                        <strong>Venue:</strong> ${escapeHtml(proposal.venue_preference)}
                    </div>
                    ${proposal.theme ? `<div class="mb-2"><strong>Theme:</strong> ${escapeHtml(proposal.theme)}</div>` : ''}
                </div>
                <div class="col-md-6">
                    <h6 class="text-success mb-3">Financial Information</h6>
                    <div class="mb-2">
                        <strong>Venue Cost:</strong> ₱${proposal.venue_cost?.toLocaleString() || '0'}
                    </div>
                    <div class="mb-2">
                        <strong>Catering Cost:</strong> ₱${proposal.catering_cost?.toLocaleString() || '0'}
                    </div>
                    <div class="mb-2">
                        <strong>Additional Services:</strong> ₱${proposal.additional_services_cost?.toLocaleString() || '0'}
                    </div>
                    <div class="mb-2">
                        <strong>Total Estimated Cost:</strong> ₱${proposal.total_estimated_cost?.toLocaleString() || '0'}
                    </div>
                    ${proposal.deposit_amount ? `
                    <div class="mb-2">
                        <strong>Deposit Amount:</strong> ₱${proposal.deposit_amount?.toLocaleString() || '0'}
                    </div>
                    ` : ''}
                    ${proposal.payment_method ? `
                    <div class="mb-2">
                        <strong>Payment Method:</strong> ${escapeHtml(proposal.payment_method)}
                    </div>
                    ` : ''}
                </div>
            </div>

            <!-- Additional Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-success mb-3">Additional Details</h6>
                    ${proposal.description ? `
                        <div class="mb-3">
                            <strong>Description:</strong>
                            <p class="mb-0 mt-1">${escapeHtml(proposal.description)}</p>
                        </div>
                    ` : ''}
                    
                    <div class="mb-3">
                        <strong>Catering:</strong> ${proposal.catering_request === 'yes' ? 'Yes' : 'No'}
                    </div>
                    
                    ${proposal.decorations && proposal.decorations.length > 0 ? `
                        <div class="mb-3">
                            <strong>Decorations:</strong>
                            <div class="mt-1">${proposal.decorations.map(d => `<span class="badge bg-light text-dark me-1">${escapeHtml(d)}</span>`).join('')}</div>
                        </div>
                    ` : ''}
                    
                    ${proposal.addon_aircon || proposal.addon_corkage ? `
                        <div class="mb-3">
                            <strong>Additional Services:</strong>
                            <div class="mt-1">
                                ${proposal.addon_aircon ? '<span class="badge bg-info me-1">Air Conditioning</span>' : ''}
                                ${proposal.addon_corkage ? '<span class="badge bg-info me-1">Corkage Fee</span>' : ''}
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>

            <!-- Status Information -->
            <div class="row">
                <div class="col-12">
                    <h6 class="text-success mb-3">Status Information</h6>
                    <div class="mb-2">
                        <strong>Submitted:</strong> ${formatDateTime(proposal.submitted)}
                    </div>
                    <div class="mb-2">
                        <strong>Last Updated:</strong> ${formatDateTime(proposal.updated_at)}
                    </div>
                    ${proposal.manager_feedback ? `
                        <div class="mb-2">
                            <strong>Manager Feedback:</strong>
                            <p class="mb-0 mt-1 text-muted">${escapeHtml(proposal.manager_feedback)}</p>
                        </div>
                    ` : ''}
                    ${proposal.cancellation_reason ? `
                        <div class="mb-2">
                            <strong>Cancellation Reason:</strong>
                            <p class="mb-0 mt-1 text-danger">${escapeHtml(proposal.cancellation_reason)}</p>
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// DELETE PROPOSAL FUNCTIONS
let currentDeleteId = null;

function setupDeleteConfirmation() {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', deleteProposal);
    }
}

function showDeleteConfirmation(proposalId) {
    currentDeleteId = proposalId;
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    modal.show();
}

async function deleteProposal() {
    if (!currentDeleteId) return;

    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const originalText = confirmBtn.innerHTML;
    
    // Show loading state
    confirmBtn.innerHTML = '<span class="loading-spinner me-2"></span>Deleting...';
    confirmBtn.disabled = true;

    try {
        const response = await fetch('event_api/delete_proposal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: currentDeleteId })
        });

        const data = await response.json();

        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'));
            modal.hide();
            
            // Show success message
            showAlert('Proposal deleted successfully!', 'success');
            
            // Reload current tab
            const activeTab = document.querySelector('#proposalTabs .nav-link.active');
            if (activeTab) {
                const status = activeTab.getAttribute('data-status');
                loadProposals(status);
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error deleting proposal:', error);
        showAlert('Failed to delete proposal: ' + error.message, 'danger');
    } finally {
        // Reset button
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
        currentDeleteId = null;
    }
}

// UTILITY FUNCTIONS
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    if (!timeString) return 'N/A';
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'N/A';
    return new Date(dateTimeString).toLocaleString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true 
    });
}

function formatStatus(status) {
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the page
    const container = document.querySelector('.px-2');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

window.addEventListener('resize', updateUnderline);
</script>

<?php ob_end_flush(); ?>