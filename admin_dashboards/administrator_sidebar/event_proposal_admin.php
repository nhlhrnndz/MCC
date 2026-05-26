<?php
session_start();
// Add your authentication check here
$current_page = 'event_proposal_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Proposals - MCC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to bottom, #f5f7fa, #e3f8f6);
            min-height: 100vh;
        }
        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }
        .header-card {
            background: linear-gradient(135deg, #00b8a9, #00998c);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .card {
            background-color: #f9fbfc;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .btn-primary {
            background-color: #00b8a9;
            border-color: #00b8a9;
        }
        .btn-primary:hover {
            background-color: #00998c;
            border-color: #00998c;
        }
        .badge-confirmed {
            background-color: #00b8a9;
            color: white;
        }
        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #00b8a9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
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
                            <h1><i class="bi bi-file-earmark-text me-3"></i>Confirmed Event Proposals</h1>
                            <p class="mb-0">View and manage confirmed event proposals</p>
                        </div>
                        <div class="col-auto">
                            <span class="badge bg-light text-dark fs-6" id="confirmed-count">
                                <span class="loading-spinner"></span> Loading...
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Confirmed Proposals</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Proposal ID</th>
                                        <th>Event Name</th>
                                        <th>Submitted By</th>
                                        <th>Event Date</th>
                                        <th>Budget</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="proposals-table-body">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <div class="loading-spinner mx-auto mb-2"></div>
                                            Loading proposals...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to load confirmed proposals
        function loadConfirmedProposals() {
            fetch('../api/get_event_confirmed.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const tableBody = document.getElementById('proposals-table-body');
                        const countElement = document.getElementById('confirmed-count');
                        
                        // Update count
                        countElement.textContent = data.count + ' Confirmed';
                        
                        // Clear existing rows
                        tableBody.innerHTML = '';
                        
                        // Check if there are any proposals
                        if (data.proposals.length === 0) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No confirmed proposals found
                                    </td>
                                </tr>
                            `;
                            return;
                        }
                        
                        // Populate table with data
                        data.proposals.forEach(proposal => {
                            const row = document.createElement('tr');
                            
                            // Format date
                            const eventDate = new Date(proposal.event_date);
                            const formattedDate = eventDate.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            });
                            
                            // Format budget
                            const formattedBudget = new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD'
                            }).format(proposal.total_estimated_cost || 0);
                            
                            row.innerHTML = `
                                <td>${proposal.proposal_id}</td>
                                <td>${proposal.event_title}</td>
                                <td>${proposal.full_name}</td>
                                <td>${formattedDate}</td>
                                <td>${formattedBudget}</td>
                                <td><span class="badge badge-confirmed">Confirmed</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProposal(${proposal.id})">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="manageProposal(${proposal.id})">
                                        <i class="bi bi-gear"></i> Manage
                                    </button>
                                </td>
                            `;
                            
                            tableBody.appendChild(row);
                        });
                    } else {
                        console.error('Error loading proposals:', data.message);
                        document.getElementById('proposals-table-body').innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center text-danger">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    Error loading proposals: ${data.message}
                                </td>
                            </tr>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('proposals-table-body').innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center text-danger">
                                <i class="bi bi-exclamation-triangle"></i>
                                Error loading proposals. Please try again later.
                            </td>
                        </tr>
                    `;
                    document.getElementById('confirmed-count').textContent = 'Error';
                });
        }
        
        // Placeholder functions for actions
        function viewProposal(id) {
            // Redirect to view proposal page or open modal
            window.location.href = `view_proposal.php?id=${id}`;
        }
        
        function manageProposal(id) {
            // Redirect to manage proposal page
            window.location.href = `manage_proposal.php?id=${id}`;
        }
        
        // Load proposals when page loads
        document.addEventListener('DOMContentLoaded', loadConfirmedProposals);
    </script>
</body>
</html>