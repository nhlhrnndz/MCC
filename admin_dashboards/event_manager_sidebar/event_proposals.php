<?php
//admin_dashboards\event_manager_sidebar\event_proposals.php
// This file contains only the event proposals content
$current_page = 'event_proposals';

// Status configuration matching the workflow
$statusConfig = [
    'pending' => [
        'label' => 'Pending Review',
        'color' => 'bg-warning text-dark',
        'icon' => 'fas fa-clock',
        'description' => 'Waiting for manager assignment'
    ],
    'under_review' => [
        'label' => 'Under Review',
        'color' => 'bg-info text-white',
        'icon' => 'fas fa-search',
        'description' => 'Currently being reviewed by manager'
    ],
    'approved' => [
        'label' => 'Approved - Awaiting Deposit',
        'color' => 'bg-primary text-white',
        'icon' => 'fas fa-check',
        'description' => 'Approved, waiting for client payment'
    ],
    'rejected' => [
        'label' => 'Rejected',
        'color' => 'bg-danger text-white',
        'icon' => 'fas fa-times',
        'description' => 'Proposal has been rejected'
    ],
    'payment_pending_verification' => [
        'label' => 'Payment Pending Verification',
        'color' => 'bg-info text-white',
        'icon' => 'fas fa-credit-card',
        'description' => 'Waiting for payment verification'
    ],
    'confirmed' => [
        'label' => 'Confirmed',
        'color' => 'bg-success text-white',
        'icon' => 'fas fa-check-circle',
        'description' => 'Deposit paid, event confirmed'
    ],
    'balance_pending_verification' => [
        'label' => 'Balance Pending Verification',
        'color' => 'bg-info text-white',
        'icon' => 'fas fa-credit-card',
        'description' => 'Waiting for balance payment verification'
    ],
    'fully_paid' => [
        'label' => 'Fully Paid',
        'color' => 'bg-success text-white',
        'icon' => 'fas fa-dollar-sign',
        'description' => 'Full payment received'
    ],
    'completed' => [
        'label' => 'Completed',
        'color' => 'bg-secondary text-white',
        'icon' => 'fas fa-flag',
        'description' => 'Event completed successfully'
    ]
];

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Database functions
function getProposals($statusFilter = 'all', $searchTerm = '') {
    global $conn;
    
    // Build the query - no JOIN needed since client data is in the same table
    $query = "SELECT p.*, 
                     p.full_name as user_name, 
                     p.email as user_email 
              FROM event_proposals p 
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($statusFilter !== 'all') {
        $query .= " AND p.status = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }
    
    if (!empty($searchTerm)) {
        $query .= " AND (p.event_title LIKE ? OR p.full_name LIKE ? OR p.proposal_id LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= str_repeat('s', 3);
    }
    
    $query .= " ORDER BY p.submitted DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return [];
}

function getStatusCounts() {
    global $conn;
    $query = "SELECT status, COUNT(*) as count FROM event_proposals GROUP BY status";
    $result = $conn->query($query);
    $counts = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $counts[$row['status']] = $row['count'];
        }
    }
    
    return $counts;
}

// Get data from database
$proposals = getProposals($statusFilter, $searchTerm);
$statusCounts = getStatusCounts();

// Calculate total count for "All" filter
$totalProposals = array_sum($statusCounts);

// Get manager ID for assignment functionality
$managerId = $_SESSION['admin_id'] ?? null;
?>

<!-- Header -->
<div class="header-card">
    <div class="row align-items-center">
        <div class="col">
            <h1><i class="fas fa-file-alt me-3"></i>Event Proposals</h1>
            <p class="mb-0">Review and manage event proposals throughout the workflow</p>
        </div>
        <div class="col-auto">
            <span class="badge bg-light text-dark fs-6">
                <?php 
                $pendingCount = $statusCounts['pending'] ?? 0;
                $paymentPendingCount = $statusCounts['payment_pending_verification'] ?? 0;
                $balancePendingCount = $statusCounts['balance_pending_verification'] ?? 0;
                $totalAttention = $pendingCount + $paymentPendingCount + $balancePendingCount;
                echo $totalAttention . ' Need Attention';
                ?>
            </span>
        </div>
    </div>
</div>

<!-- Filter Tabs and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="event_proposals">
            
            <div class="col-md-8">
                <div class="btn-group flex-wrap" role="group">
                    <button type="submit" name="status" value="all" 
                        class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        All (<?= $totalProposals ?>)
                    </button>
                    <button type="submit" name="status" value="pending" 
                        class="btn <?= $statusFilter === 'pending' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Pending (<?= $statusCounts['pending'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="payment_pending_verification" 
                        class="btn <?= $statusFilter === 'payment_pending_verification' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Payment Verification (<?= $statusCounts['payment_pending_verification'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="balance_pending_verification" 
                        class="btn <?= $statusFilter === 'balance_pending_verification' ? 'btn-primary' : 'btn-outline-primary' ?> d-none">
                        Balance Verification (<?= $statusCounts['balance_pending_verification'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="under_review" 
                        class="btn <?= $statusFilter === 'under_review' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Under Review (<?= $statusCounts['under_review'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="approved" 
                        class="btn <?= $statusFilter === 'approved' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Approved (<?= $statusCounts['approved'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="confirmed" 
                        class="btn <?= $statusFilter === 'confirmed' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Confirmed (<?= $statusCounts['confirmed'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="fully_paid" 
                        class="btn <?= $statusFilter === 'fully_paid' ? 'btn-primary' : 'btn-outline-primary' ?> d-none">
                        Fully Paid (<?= $statusCounts['fully_paid'] ?? 0 ?>)
                    </button>
                    <button type="submit" name="status" value="completed" 
                        class="btn <?= $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline-primary' ?>">
                        Completed (<?= $statusCounts['completed'] ?? 0 ?>)
                    </button>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by name, event, or ID..." 
                           value="<?= htmlspecialchars($searchTerm) ?>">
                    <button type="submit" class="btn btn-outline-secondary">Search</button>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="?page=event_proposals&status=<?= $statusFilter ?>" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Proposals Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Proposal ID</th>
                        <th>Event Title</th>
                        <th>Client</th>
                        <th>Event Date</th>
                        <th>Venue</th>
                        <th class="text-center">Guests</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($proposals)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <?= empty($searchTerm) ? 'No proposals found' : 'No proposals match your search criteria' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($proposals as $proposal): 
                            // Map hidden statuses to visible ones for display
                            $displayStatus = $proposal['status'];
                            if ($displayStatus === 'fully_paid') {
                                $displayStatus = 'confirmed'; // Show as confirmed instead of fully paid
                            } elseif ($displayStatus === 'balance_pending_verification') {
                                $displayStatus = 'confirmed'; // Show as confirmed instead of balance verification
                            }
                            
                            $statusInfo = $statusConfig[$displayStatus] ?? $statusConfig['pending'];
                            $eventDate = date('M j, Y', strtotime($proposal['event_date'] ?? $proposal['arrival_date']));
                            $isAssignedToMe = $proposal['assigned_manager_id'] == $managerId;
                            $canAssign = $proposal['status'] == 'pending' && !$proposal['assigned_manager_id'];
                            $canTakeOver = $proposal['status'] == 'pending' && $proposal['assigned_manager_id'] && !$isAssignedToMe;
                            $canComplete = ($proposal['status'] === 'confirmed' || $proposal['status'] === 'fully_paid') && $isAssignedToMe;
                        ?>
                            <tr>
                                <td>
                                    <span class="text-primary fw-semibold">#<?= htmlspecialchars($proposal['proposal_id']) ?></span>
                                    <?php if (($proposal['status'] === 'payment_pending_verification' || $proposal['status'] === 'balance_pending_verification') && $proposal['payment_proof']): ?>
                                        <br><small class="text-warning"><i class="fas fa-credit-card"></i> Payment Proof Submitted</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($proposal['event_title']) ?></div>
                                        <div class="text-muted small mt-1"><?= htmlspecialchars($proposal['event_type']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div><?= htmlspecialchars($proposal['user_name']) ?></div>
                                        <div class="text-muted small mt-1"><?= htmlspecialchars($proposal['user_email']) ?></div>
                                    </div>
                                </td>
                                <td><?= $eventDate ?></td>
                                <td><?= htmlspecialchars($proposal['venue_preference']) ?></td>
                                <td class="text-center"><?= $proposal['expected_guests'] ?></td>
                                <td>
                                    <span class="badge <?= $statusInfo['color'] ?> d-flex align-items-center gap-1" style="width: fit-content;">
                                        <i class="<?= $statusInfo['icon'] ?>"></i>
                                        <?= $statusInfo['label'] ?>
                                        <?php if ($proposal['status'] === 'fully_paid'): ?>
                                            <i class="fas fa-dollar-sign text-success"></i>
                                        <?php elseif ($proposal['status'] === 'balance_pending_verification'): ?>
                                            <i class="fas fa-clock text-warning"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($proposal['assigned_manager_id']): ?>
                                        <?php if ($isAssignedToMe): ?>
                                            <span class="badge bg-success">You</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Another Manager</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                                                onclick="openProposalModal('<?= $proposal['id'] ?>')">
                                            <i class="fas fa-eye"></i>
                                            Review
                                        </button>
                                        
                                        <?php if ($canAssign): ?>
                                            <button class="btn btn-sm btn-success d-flex align-items-center gap-1"
                                                    onclick="assignToMe('<?= $proposal['id'] ?>')">
                                                <i class="fas fa-user-check"></i>
                                                Assign to Me
                                            </button>
                                        <?php elseif ($canTakeOver): ?>
                                            <button class="btn btn-sm btn-warning d-flex align-items-center gap-1"
                                                    onclick="takeOverProposal('<?= $proposal['id'] ?>')">
                                                <i class="fas fa-redo"></i>
                                                Take Over
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($canComplete): ?>
                                            <button class="btn btn-sm btn-info d-flex align-items-center gap-1"
                                                    onclick="markEventCompleted('<?= $proposal['id'] ?>')">
                                                <i class="fas fa-flag"></i>
                                                Mark Completed
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Proposal Details Modal -->
<div class="modal fade" id="proposalModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proposal Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="proposalModalContent" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading proposal details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Proof Modal -->
<div class="modal fade" id="paymentProofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="paymentProofContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="#" id="downloadPaymentProof" class="btn btn-primary" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Payment Verification Modal -->
<div class="modal fade" id="paymentVerificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Verify Payment Proof</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="paymentVerificationContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Proposal Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Proposal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="approveModalContent">
                    <p>Please review the final quote and deposit details:</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Final Quote Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="finalQuoteAmount" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deposit Amount (50%)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="depositAmount" readonly>
                        </div>
                        <div class="form-text">50% of final quote amount</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Deposit Due Date *</label>
                        <input type="date" class="form-control" id="depositDueDate" required>
                        <div class="form-text">Typically 7 days from today</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Additional Notes (Optional)</label>
                        <textarea class="form-control" id="approveNotes" rows="3" placeholder="Any special instructions or comments..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="approveProposal()">Approve Proposal</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Proposal Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Proposal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rejectModalContent">
                    <p>Please provide a reason for rejection:</p>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason *</label>
                        <textarea class="form-control" id="rejectionReason" rows="5" placeholder="Please explain why this proposal is being rejected..." required></textarea>
                        <div class="form-text">This will be sent to the client</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="rejectProposal()">Reject Proposal</button>
            </div>
        </div>
    </div>
</div>

<!-- Event Completion Modal -->
<div class="modal fade" id="completeEventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Event as Completed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="completeEventModalContent">
                    <p>Please confirm that the event has been successfully completed:</p>
                    
                    <div class="mb-3">
                        <label class="form-label">Event Completion Date *</label>
                        <input type="date" class="form-control" id="completionDate" value="<?= date('Y-m-d') ?>" required>
                        <div class="form-text">The date when the event was successfully completed</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Event Notes (Optional)</label>
                        <textarea class="form-control" id="completionNotes" rows="4" placeholder="Any notes about the event completion, feedback, or remarks..."></textarea>
                        <div class="form-text">These notes will be saved for future reference</div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Marking this event as completed will:
                        <ul class="mb-0 mt-1">
                            <li>Finalize the event status</li>
                            <li>Archive the proposal</li>
                            <li>Notify the client about completion</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="completeEvent()">Mark as Completed</button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables to store current proposal ID
let currentProposalId = null;

function openProposalModal(proposalId) {
    currentProposalId = proposalId;
    const modalContent = document.getElementById('proposalModalContent');
    
    console.log('Opening proposal modal for ID:', proposalId);
    
    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading proposal details...</p>
        </div>
    `;
    
    // Fetch actual proposal details from backend
    fetch('get_proposal_details.php?id=' + proposalId)
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            if (data.success) {
                displayProposalDetails(data.proposal);
            } else {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading proposal details: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to load proposal details. Error: ${error.message}
                    <br><small>Check browser console for details</small>
                </div>
            `;
        });
    
    const modal = new bootstrap.Modal(document.getElementById('proposalModal'));
    modal.show();
}

function displayProposalDetails(proposal) {
    const modalContent = document.getElementById('proposalModalContent');
    
    // Format decorations array
    let decorationsHtml = '<span class="text-muted">None</span>';
    if (proposal.decorations && proposal.decorations !== 'null' && proposal.decorations !== '[]') {
        try {
            const decorations = Array.isArray(proposal.decorations) ? proposal.decorations : JSON.parse(proposal.decorations);
            if (Array.isArray(decorations) && decorations.length > 0) {
                decorationsHtml = decorations.map(deco => 
                    `<span class="badge bg-light text-dark me-1 mb-1">${deco}</span>`
                ).join('');
            }
        } catch (e) {
            decorationsHtml = `<span class="text-muted">${proposal.decorations}</span>`;
        }
    }
    
    // Format costs
    const formatCurrency = (amount) => {
        if (!amount || amount === '0.00' || amount === '0') return '₱0.00';
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    // Payment proof section
    let paymentProofHtml = '';
    const hasPaymentProof = proposal.payment_proof || proposal.has_payment_proof;
    if (hasPaymentProof) {
        const isBalancePayment = proposal.status === 'balance_pending_verification';
        const paymentType = isBalancePayment ? 'Balance' : 'Deposit';
        
        paymentProofHtml = `
            <div class="detail-section border-warning">
                <h6 class="text-warning"><i class="fas fa-credit-card me-2"></i>${paymentType} Payment Proof</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label-sm text-muted">Payment Proof Status</label>
                            <div class="mt-2">
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Proof Uploaded
                                </span>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="viewPaymentProof(${proposal.id})">
                                        <i class="fas fa-search"></i> View Payment Proof
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="downloadPaymentProof(${proposal.id})">
                                        <i class="fas fa-download"></i> Download
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label-sm text-muted">Payment Status</label>
                            <p class="mb-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock"></i> Pending Verification
                                </span>
                            </p>
                        </div>
                        ${isBalancePayment ? `
                        <div class="mb-3">
                            <label class="form-label-sm text-muted">Balance Amount</label>
                            <p class="mb-0 fw-bold text-primary">${formatCurrency(proposal.balance_amount)}</p>
                        </div>
                        ` : `
                        <div class="mb-3">
                            <label class="form-label-sm text-muted">Deposit Amount</label>
                            <p class="mb-0 fw-bold text-primary">${formatCurrency(proposal.deposit_amount)}</p>
                        </div>
                        ${proposal.deposit_due_date ? `
                        <div class="mb-3">
                            <label class="form-label-sm text-muted">Deposit Due Date</label>
                            <p class="mb-0">${formatDate(proposal.deposit_due_date)}</p>
                        </div>
                        ` : ''}
                        `}
                    </div>
                </div>
                ${proposal.status === 'payment_pending_verification' || proposal.status === 'balance_pending_verification' ? `
                <div class="mt-3 p-3 bg-light rounded">
                    <h6 class="text-dark mb-2">Verify ${isBalancePayment ? 'Balance' : 'Deposit'} Payment</h6>
                    <div class="btn-group">
                        <button class="btn btn-success btn-sm" onclick="verifyPayment('${proposal.id}', 'approved', '${isBalancePayment ? 'balance' : 'deposit'}')">
                            <i class="fas fa-check"></i> Approve ${isBalancePayment ? 'Balance' : 'Deposit'}
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="verifyPayment('${proposal.id}', 'rejected', '${isBalancePayment ? 'balance' : 'deposit'}')">
                            <i class="fas fa-times"></i> Reject ${isBalancePayment ? 'Balance' : 'Deposit'}
                        </button>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    }

    // Financial summary section
    let financialSummaryHtml = '';
    if (proposal.final_quote_amount) {
        const depositAmount = parseFloat(proposal.deposit_amount) || 0;
        const balanceAmount = parseFloat(proposal.balance_amount) || (parseFloat(proposal.final_quote_amount) - depositAmount);
        const paidAmount = (proposal.deposit_paid ? depositAmount : 0) + (proposal.balance_paid ? balanceAmount : 0);
        const totalAmount = parseFloat(proposal.final_quote_amount);
        
        financialSummaryHtml = `
            <div class="detail-section bg-light">
                <h6><i class="fas fa-money-bill-wave me-2"></i>Payment Summary</h6>
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="mb-2">
                            <label class="form-label-sm text-muted">Final Quote</label>
                            <p class="mb-0 fw-bold fs-5 text-primary">${formatCurrency(proposal.final_quote_amount)}</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="mb-2">
                            <label class="form-label-sm text-muted">Paid Amount</label>
                            <p class="mb-0 fw-bold fs-5 ${paidAmount >= totalAmount ? 'text-success' : 'text-warning'}">
                                ${formatCurrency(paidAmount)}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="mb-2">
                            <label class="form-label-sm text-muted">Remaining</label>
                            <p class="mb-0 fw-bold fs-5 ${totalAmount - paidAmount <= 0 ? 'text-success' : 'text-danger'}">
                                ${formatCurrency(totalAmount - paidAmount)}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="progress mt-2" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" 
                         style="width: ${totalAmount > 0 ? (paidAmount / totalAmount) * 100 : 0}%" 
                         aria-valuenow="${totalAmount > 0 ? (paidAmount / totalAmount) * 100 : 0}" 
                         aria-valuemin="0" aria-valuemax="100">
                        ${totalAmount > 0 ? Math.round((paidAmount / totalAmount) * 100) : 0}% Paid
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas ${proposal.deposit_paid ? 'fa-check-circle text-success' : 'fa-clock'}"></i>
                            Deposit: ${formatCurrency(depositAmount)} ${proposal.deposit_paid ? '(Paid)' : '(Pending)'}
                        </small>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">
                            <i class="fas ${proposal.balance_paid ? 'fa-check-circle text-success' : 'fa-clock'}"></i>
                            Balance: ${formatCurrency(balanceAmount)} ${proposal.balance_paid ? '(Paid)' : '(Pending)'}
                        </small>
                    </div>
                </div>
            </div>
        `;
    }

    // Get display status (map hidden statuses to visible ones)
    let displayStatus = proposal.status;
    let statusBadgeClass = getStatusBadgeClass(proposal.status);
    let statusText = getStatusText(proposal.status);
    
    if (proposal.status === 'fully_paid') {
        displayStatus = 'confirmed';
        statusBadgeClass = 'bg-success text-white';
        statusText = 'Confirmed - Fully Paid';
    } else if (proposal.status === 'balance_pending_verification') {
        displayStatus = 'confirmed';
        statusBadgeClass = 'bg-info text-white';
        statusText = 'Confirmed - Balance Verification';
    }

    // Check if event can be marked as completed
    const canCompleteEvent = (proposal.status === 'confirmed' || proposal.status === 'fully_paid');

    modalContent.innerHTML = `
        <div class="proposal-details">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col">
                    <h4 class="text-primary">${escapeHtml(proposal.event_title)}</h4>
                    <p class="text-muted mb-0">Proposal ID: ${proposal.proposal_id} | Event Type: ${escapeHtml(proposal.event_type)}</p>
                </div>
                <div class="col-auto">
                    <span class="badge ${statusBadgeClass} fs-6">
                        ${statusText}
                    </span>
                </div>
            </div>

            ${financialSummaryHtml}

            ${paymentProofHtml}

            <!-- Review Checklist -->
            <div class="detail-section bg-light">
                <h6><i class="fas fa-clipboard-check me-2"></i>Review Checklist</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input review-check" type="checkbox" id="checkVenue">
                            <label class="form-check-label" for="checkVenue">
                                ✅ Venue availability for ${formatDate(proposal.event_date || proposal.arrival_date)}
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input review-check" type="checkbox" id="checkCapacity">
                            <label class="form-check-label" for="checkCapacity">
                                ✅ Guest count (${proposal.expected_guests}) vs venue capacity
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mb-2">
                            <input class="form-check-input review-check" type="checkbox" id="checkRequirements">
                            <label class="form-check-label" for="checkRequirements">
                                ✅ Special requirements feasibility
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input review-check" type="checkbox" id="checkBudget">
                            <label class="form-check-label" for="checkBudget">
                                ✅ Budget alignment (${formatCurrency(proposal.estimated_budget)})
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input review-check" type="checkbox" id="checkConflicts">
                    <label class="form-check-label" for="checkConflicts">
                        ✅ No potential conflicts identified
                    </label>
                </div>
            </div>

            <div class="row">
                <!-- Left Column - Client & Event Details -->
                <div class="col-md-6">
                    <!-- Client Information -->
                    <div class="detail-section">
                        <h6><i class="fas fa-user me-2"></i>Client Information</h6>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Full Name</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.full_name)}</p>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Email</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.email)}</p>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Contact Number</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.contact_number)}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Event Details -->
                    <div class="detail-section">
                        <h6><i class="fas fa-calendar-alt me-2"></i>Event Details</h6>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Event Type</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.event_type)}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Expected Guests</label>
                                <p class="mb-0 fs-6">${proposal.expected_guests}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Arrival Date</label>
                                <p class="mb-0 fs-6">${formatDate(proposal.arrival_date)}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Arrival Time</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.arrival_time)}</p>
                            </div>
                            ${proposal.event_date ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Event Date</label>
                                <p class="mb-0 fs-6">${formatDate(proposal.event_date)}</p>
                            </div>
                            ` : ''}
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Venue Preference</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.venue_preference)}</p>
                            </div>
                            ${proposal.theme ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Theme</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.theme)}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Event Description -->
                    ${proposal.description ? `
                    <div class="detail-section">
                        <h6><i class="fas fa-comment me-2"></i>Event Description</h6>
                        <p class="mb-0 fs-6">${escapeHtml(proposal.description)}</p>
                    </div>
                    ` : ''}
                </div>

                <!-- Right Column - Services & Financial -->
                <div class="col-md-6">
                    <!-- Catering Details -->
                    <div class="detail-section">
                        <h6><i class="fas fa-utensils me-2"></i>Catering</h6>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Catering Requested</label>
                                <p class="mb-0">
                                    <span class="badge ${proposal.catering_request === 'yes' ? 'bg-success' : 'bg-secondary'} fs-6">
                                        ${proposal.catering_request === 'yes' ? 'Yes' : 'No'}
                                    </span>
                                </p>
                            </div>
                            ${proposal.catering_details ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Catering Details</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.catering_details)}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    <!-- Decorations -->
                    <div class="detail-section">
                        <h6><i class="fas fa-palette me-2"></i>Decorations</h6>
                        <div class="mb-2">
                            <label class="form-label-sm text-muted">Selected Decorations</label>
                            <div class="mt-1">${decorationsHtml}</div>
                        </div>
                        ${proposal.custom_decorations ? `
                        <div class="mb-2">
                            <label class="form-label-sm text-muted">Custom Decorations</label>
                            <p class="mb-0 fs-6">${escapeHtml(proposal.custom_decorations)}</p>
                        </div>
                        ` : ''}
                    </div>

                    <!-- Equipment & Special Requests -->
                    ${proposal.equipment_needed ? `
                    <div class="detail-section">
                        <h6><i class="fas fa-tools me-2"></i>Equipment Needed</h6>
                        <p class="mb-0 fs-6">${escapeHtml(proposal.equipment_needed)}</p>
                    </div>
                    ` : ''}

                    ${proposal.special_requests ? `
                    <div class="detail-section">
                        <h6><i class="fas fa-star me-2"></i>Special Requests</h6>
                        <p class="mb-0 fs-6">${escapeHtml(proposal.special_requests)}</p>
                    </div>
                    ` : ''}

                    <!-- Add-on Services -->
                    <div class="detail-section">
                        <h6><i class="fas fa-plus-circle me-2"></i>Add-on Services</h6>
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Air Conditioning</label>
                                <p class="mb-0">
                                    <span class="badge ${proposal.addon_aircon ? 'bg-success' : 'bg-secondary'} fs-6">
                                        ${proposal.addon_aircon ? 'Yes' : 'No'}
                                    </span>
                                </p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Corkage</label>
                                <p class="mb-0">
                                    <span class="badge ${proposal.addon_corkage ? 'bg-success' : 'bg-secondary'} fs-6">
                                        ${proposal.addon_corkage ? 'Yes' : 'No'}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Information -->
                    <div class="detail-section">
                        <h6><i class="fas fa-money-bill-wave me-2"></i>Financial Information</h6>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Estimated Budget</label>
                                <p class="mb-0 fs-6">${formatCurrency(proposal.estimated_budget)}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Venue Cost</label>
                                <p class="mb-0 fs-6">${formatCurrency(proposal.venue_cost)}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Catering Cost</label>
                                <p class="mb-0 fs-6">${formatCurrency(proposal.catering_cost)}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Additional Services</label>
                                <p class="mb-0 fs-6">${formatCurrency(proposal.additional_services_cost)}</p>
                            </div>
                            <div class="col-6 mb-2">
                                <label class="form-label-sm text-muted">Total Estimated</label>
                                <p class="mb-0 fs-6 fw-bold text-primary">${formatCurrency(proposal.total_estimated_cost)}</p>
                            </div>
                            ${proposal.final_quote_amount ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Final Quote Amount</label>
                                <p class="mb-0 fs-6 fw-bold text-success">${formatCurrency(proposal.final_quote_amount)}</p>
                            </div>
                            ` : ''}
                            ${proposal.deposit_amount && proposal.deposit_amount !== '0.00' ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Deposit Amount (50%)</label>
                                <p class="mb-0 fs-6 fw-bold text-warning">${formatCurrency(proposal.deposit_amount)}</p>
                            </div>
                            ` : ''}
                            ${proposal.balance_amount && proposal.balance_amount !== '0.00' ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Balance Amount (50%)</label>
                                <p class="mb-0 fs-6 fw-bold text-info">${formatCurrency(proposal.balance_amount)}</p>
                            </div>
                            ` : ''}
                            ${proposal.payment_method ? `
                            <div class="col-12 mb-2">
                                <label class="form-label-sm text-muted">Preferred Payment</label>
                                <p class="mb-0 fs-6">${escapeHtml(proposal.payment_method)}</p>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manager Decision & Actions -->
            <div class="detail-section mt-4 border-top pt-3">
                <h6><i class="fas fa-cogs me-2"></i>Manager Actions</h6>
                <div class="row">
                    <div class="col">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> Submitted: ${formatDateTime(proposal.submitted)}
                            ${proposal.updated_at && proposal.updated_at !== proposal.submitted ? 
                                ` | Updated: ${formatDateTime(proposal.updated_at)}` : ''}
                        </small>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group">
                            ${proposal.status === 'pending' || proposal.status === 'under_review' ? `
                            <!-- Option A: Approve Proposal -->
                            <button class="btn btn-success" onclick="showApproveModal('${proposal.id}', ${proposal.total_estimated_cost || 0})">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            
                            <!-- Option C: Reject Proposal -->
                            <button class="btn btn-danger" onclick="showRejectModal('${proposal.id}')">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            ` : ''}
                            
                            ${proposal.status === 'approved' ? `
                            <button class="btn btn-success" onclick="updateProposalStatus('${proposal.id}', 'confirmed')">
                                <i class="fas fa-check-circle"></i> Confirm Event
                            </button>
                            ` : ''}
                            
                            ${canCompleteEvent ? `
                            <button class="btn btn-info" onclick="showCompleteEventModal('${proposal.id}')">
                                <i class="fas fa-flag"></i> Mark Completed
                            </button>
                            ` : ''}
                            
                            ${proposal.status === 'fully_paid' ? `
                            <button class="btn btn-info" onclick="showCompleteEventModal('${proposal.id}')">
                                <i class="fas fa-flag"></i> Mark Completed
                            </button>
                            ` : ''}
                            
                            <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Mark Event Completed from table
function markEventCompleted(proposalId) {
    currentProposalId = proposalId;
    showCompleteEventModal(proposalId);
}

// Show Complete Event Modal
function showCompleteEventModal(proposalId) {
    currentProposalId = proposalId;
    const modal = new bootstrap.Modal(document.getElementById('completeEventModal'));
    modal.show();
}

// Complete Event Function
function completeEvent() {
    const completionDate = document.getElementById('completionDate').value;
    const completionNotes = document.getElementById('completionNotes').value;
    
    if (!completionDate) {
        alert('Please select the completion date');
        return;
    }
    
    fetch('update_proposal_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            proposal_id: currentProposalId,
            status: 'completed',
            completion_date: completionDate,
            completion_notes: completionNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const emailMsg = data.email_sent ? 'Email notification sent!' : 'Status updated (email failed)';
            showNotification(`Event marked as completed successfully! ${emailMsg}`, 'success');
            
            const completeModal = bootstrap.Modal.getInstance(document.getElementById('completeEventModal'));
            const proposalModal = bootstrap.Modal.getInstance(document.getElementById('proposalModal'));
            if (completeModal) completeModal.hide();
            if (proposalModal) proposalModal.hide();
            
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showNotification('Failed to complete event: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error completing event', 'error');
    });
}

// Payment Proof Viewer Function
function viewPaymentProof(proposalId) {
    const modalContent = document.getElementById('paymentProofContent');
    const downloadLink = document.getElementById('downloadPaymentProof');
    
    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading payment proof...</p>
        </div>
    `;
    
    // Set download link
    downloadLink.href = `get_payment_proof.php?id=${proposalId}&download=1`;
    downloadLink.setAttribute('download', `payment_proof_${proposalId}`);
    
    // Create iframe for better PDF support and image display
    const iframe = document.createElement('iframe');
    iframe.src = `get_payment_proof.php?id=${proposalId}`;
    iframe.style.width = '100%';
    iframe.style.height = '70vh';
    iframe.style.border = 'none';
    iframe.style.borderRadius = '8px';
    
    iframe.onload = function() {
        console.log('Payment proof loaded successfully in iframe');
    };
    
    iframe.onerror = function() {
        console.error('Failed to load payment proof in iframe');
        modalContent.innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fas fa-exclamation-triangle"></i>
                <h5>Unable to Display Payment Proof</h5>
                <p>The payment proof format may not be supported for inline viewing.</p>
                <div class="mt-3">
                    <button class="btn btn-success" onclick="downloadPaymentProof(${proposalId})">
                        <i class="fas fa-download"></i> Download Instead
                    </button>
                </div>
            </div>
        `;
    };
    
    modalContent.innerHTML = '';
    modalContent.appendChild(iframe);
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('paymentProofModal'));
    modal.show();
}

// Download payment proof function
function downloadPaymentProof(proposalId) {
    console.log('Downloading payment proof for proposal:', proposalId);
    
    const link = document.createElement('a');
    link.href = `get_payment_proof.php?id=${proposalId}&download=1`;
    link.download = `payment_proof_${proposalId}`;
    link.target = '_blank';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification('Download initiated for payment proof', 'info');
}

// Payment Verification Functions
function verifyPayment(proposalId, action, paymentType = 'deposit') {
    const isBalancePayment = paymentType === 'balance';
    const message = action === 'approved' ? 
        `Are you sure you want to approve this ${isBalancePayment ? 'balance' : 'deposit'} payment? ${isBalancePayment ? 'The event will be marked as fully paid.' : 'The event will be confirmed.'}` :
        `Are you sure you want to reject this ${isBalancePayment ? 'balance' : 'deposit'} payment? The client will be notified to submit a new payment proof.`;
    
    if (confirm(message)) {
        const updateData = {
            proposal_id: proposalId,
            action: action, // 'approved' or 'rejected'
            payment_type: paymentType // 'deposit' or 'balance'
        };
        
        fetch('../api_events/verify_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(updateData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const successMessage = action === 'approved' ? 
                    `${isBalancePayment ? 'Balance' : 'Deposit'} approved! ${isBalancePayment ? 'Event is fully paid!' : 'Event has been confirmed.'}` : 
                    `${isBalancePayment ? 'Balance' : 'Deposit'} rejected! Client has been notified.`;
                
                showNotification(successMessage, 'success');
                
                // Close modals and reload
                const proposalModal = bootstrap.Modal.getInstance(document.getElementById('proposalModal'));
                const paymentModal = bootstrap.Modal.getInstance(document.getElementById('paymentProofModal'));
                if (proposalModal) proposalModal.hide();
                if (paymentModal) paymentModal.hide();
                
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('Failed to verify payment: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error verifying payment: ' + error.message, 'error');
        });
    }
}

// Decision Modal Functions
function showApproveModal(proposalId, estimatedCost) {
    currentProposalId = proposalId;
    
    // Set default values
    const finalQuoteInput = document.getElementById('finalQuoteAmount');
    const depositInput = document.getElementById('depositAmount');
    const dueDateInput = document.getElementById('depositDueDate');
    
    // Set final quote to estimated cost by default
    finalQuoteInput.value = estimatedCost || 0;
    
    // Calculate deposit (50%)
    finalQuoteInput.addEventListener('input', function() {
        const finalQuote = parseFloat(this.value) || 0;
        depositInput.value = (finalQuote * 0.5).toFixed(2);
    });
    
    // Trigger initial calculation
    finalQuoteInput.dispatchEvent(new Event('input'));
    
    // Set deposit due date to 7 days from today
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    dueDateInput.value = dueDate.toISOString().split('T')[0];
    
    const modal = new bootstrap.Modal(document.getElementById('approveModal'));
    modal.show();
}

function showRejectModal(proposalId) {
    currentProposalId = proposalId;
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}

function approveProposal() {
    const finalQuoteAmount = document.getElementById('finalQuoteAmount').value;
    const depositDueDate = document.getElementById('depositDueDate').value;
    const notes = document.getElementById('approveNotes').value;
    
    if (!finalQuoteAmount || !depositDueDate) {
        alert('Please fill in all required fields');
        return;
    }
    
    // CORRECT PATH - based on your file structure
    fetch('../api_events/approve_proposal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            proposal_id: currentProposalId,
            final_quote_amount: finalQuoteAmount,
            deposit_due_date: depositDueDate,
            feedback: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Proposal approved successfully!', 'success');
            const approveModal = bootstrap.Modal.getInstance(document.getElementById('approveModal'));
            const proposalModal = bootstrap.Modal.getInstance(document.getElementById('proposalModal'));
            if (approveModal) approveModal.hide();
            if (proposalModal) proposalModal.hide();
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('Failed to approve proposal: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error approving proposal', 'error');
    });
}

function rejectProposal() {
    const rejectionReason = document.getElementById('rejectionReason').value;
    
    if (!rejectionReason.trim()) {
        alert('Please provide a reason for rejection');
        return;
    }
    
    fetch('../api_events/reject_proposal.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            proposal_id: currentProposalId,
            feedback: rejectionReason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Proposal rejected successfully!', 'success');
            const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
            const proposalModal = bootstrap.Modal.getInstance(document.getElementById('proposalModal'));
            if (rejectModal) rejectModal.hide();
            if (proposalModal) proposalModal.hide();
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('Failed to reject proposal: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error rejecting proposal', 'error');
    });
}

function assignToMe(proposalId) {
    console.log('Assigning proposal:', proposalId);
    
    if (confirm('Are you sure you want to assign this proposal to yourself for review?')) {
        fetch('assign_proposal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                proposal_id: proposalId,
                action: 'assign'
            })
        })
        .then(response => {
            console.log('Assign response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Assign response data:', data);
            if (data.success) {
                showNotification('Proposal assigned to you successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Failed to assign proposal: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Assign error:', error);
            showNotification('Error assigning proposal: ' + error.message, 'error');
        });
    }
}

// Helper Functions
function getStatusBadgeClass(status) {
    const statusClasses = {
        'pending': 'bg-warning text-dark',
        'under_review': 'bg-info text-white',
        'approved': 'bg-primary text-white',
        'rejected': 'bg-danger text-white',
        'payment_pending_verification': 'bg-info text-white',
        'confirmed': 'bg-success text-white',
        'balance_pending_verification': 'bg-info text-white',
        'fully_paid': 'bg-success text-white',
        'completed': 'bg-secondary text-white'
    };
    return statusClasses[status] || 'bg-secondary';
}

function getStatusText(status) {
    const statusTexts = {
        'pending': 'Pending Review',
        'under_review': 'Under Review',
        'approved': 'Approved - Awaiting Deposit',
        'rejected': 'Rejected',
        'payment_pending_verification': 'Deposit Verification',
        'confirmed': 'Confirmed - Pay Balance',
        'balance_pending_verification': 'Balance Verification',
        'fully_paid': 'Fully Paid',
        'completed': 'Completed'
    };
    return statusTexts[status] || status;
}

function formatDate(dateString) {
    if (!dateString) return 'Not specified';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'Not specified';
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function takeOverProposal(proposalId) {
    if (confirm('Are you sure you want to take over this proposal from another manager?')) {
        fetch('assign_proposal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                proposal_id: proposalId,
                action: 'takeover'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Proposal taken over successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Failed to take over proposal: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error taking over proposal', 'error');
        });
    }
}

function updateProposalStatus(proposalId, status) {
    if (confirm(`Are you sure you want to change the status to "${getStatusText(status)}"?`)) {
        fetch('update_proposal_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                proposal_id: proposalId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Proposal status updated to ${getStatusText(status)} successfully!`, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Failed to update proposal status: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error updating proposal status', 'error');
        });
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 5000);
}
</script>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
    --white: #ffffff;
    --form-bg: #f9fbfc;
}

.header-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(0, 184, 169, 0.3);
}

.header-card h1 {
    margin: 0;
    font-weight: 600;
}

.header-card p {
    opacity: 0.9;
    margin: 0.5rem 0 0 0;
}

.card {
    border: 1px solid rgba(0, 184, 169, 0.1);
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 1.5rem;
}

.card-body {
    padding: 1.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00857a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
}

.btn-outline-primary {
    color: var(--primary);
    border-color: var(--primary);
    border-radius: 8px;
    font-weight: 500;
}

.btn-outline-primary:hover {
    background: var(--primary);
    border-color: var(--primary);
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 8px;
    font-weight: 500;
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00857a 100%);
    transform: translateY(-2px);
}

.badge.bg-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
}

.table th {
    font-weight: 600;
    color: #2c3e50;
    border-bottom: 2px solid var(--primary);
    background: var(--primary-light);
}

.table td {
    vertical-align: middle;
    padding: 1rem 0.75rem;
}

.table-hover tbody tr:hover {
    background-color: var(--primary-light);
}

.detail-section {
    margin-bottom: 1.5rem;
    padding: 1.25rem;
    border: 1px solid rgba(0, 184, 169, 0.1);
    border-radius: 8px;
    background: var(--white);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.detail-section h6 {
    color: var(--primary-dark);
    border-bottom: 2px solid var(--primary);
    padding-bottom: 0.5rem;
    margin-bottom: 1rem;
    font-weight: 600;
}

.detail-section.border-warning {
    border-color: #ffc107;
    background: #fffbf0;
}

.detail-section.border-warning h6 {
    border-bottom-color: #ffc107;
}

.form-label-sm {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    display: block;
    color: #6c757d;
}

.proposal-details p {
    margin-bottom: 0.5rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

.btn-group .btn {
    font-size: 0.875rem;
}

.modal-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    border-radius: 12px 12px 0 0;
}

.modal-title {
    font-weight: 600;
}

.btn-close-white {
    filter: invert(1);
}

/* Form controls */
.form-control {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
    background: var(--form-bg);
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(0, 184, 169, 0.25);
    background: var(--white);
}

.input-group-text {
    background: var(--primary-light);
    border-color: #dee2e6;
    color: var(--primary-dark);
}

/* Progress bar */
.progress {
    border-radius: 10px;
    background: var(--primary-light);
}

.progress-bar {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 10px;
}

/* Alert styling */
.alert {
    border-radius: 8px;
    border: none;
    border-left: 4px solid transparent;
}

.alert-success {
    background-color: rgba(0, 184, 169, 0.1);
    color: #155724;
    border-left-color: var(--primary);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #721c24;
    border-left-color: #dc3545;
}

.alert-info {
    background-color: rgba(23, 162, 184, 0.1);
    color: #0c5460;
    border-left-color: #17a2b8;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #856404;
    border-left-color: #ffc107;
}

/* Responsive design */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .detail-section {
        padding: 1rem;
    }
    
    .header-card {
        padding: 1rem;
        text-align: center;
    }
    
    .header-card h1 {
        font-size: 1.5rem;
    }
}

/* Animation for better UX */
.card, .detail-section {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover effects */
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 184, 169, 0.15);
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    transition: all 0.3s ease;
}
</style>