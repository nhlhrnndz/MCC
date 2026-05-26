<?php
// users.php – Updated with simplified actions
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../admin_login.php");
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | MCC Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary: #00b8a9;
      --primary-dark: #00998c;
      --primary-light: #e3f8f6;
      --body-bg-light: #f5f7fa;
      --form-bg: #f9fbfc;
      --white: #ffffff;
    }
    
    body {
      background: linear-gradient(to bottom, var(--body-bg-light), var(--primary-light));
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }
    
    .main-content {
      margin-left: 260px;
      padding: 2rem;
      min-height: 100vh;
    }
    
    /* BEAUTIFUL HEADER BANNER */
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
    
    .search-bar {
      max-width: 400px;
    }
    
    .filter-btn {
      font-size: .875rem;
      padding: .375rem .75rem;
    }
    
    .filter-btn.active {
      background-color: var(--primary) !important;
      color: var(--white) !important;
      border-color: var(--primary);
    }
    
    .filter-btn:hover:not(.active) {
      border-color: var(--primary);
      color: var(--primary);
    }
    
    .stats-card {
      background: var(--white);
      border-radius: 12px;
      padding: 1.25rem;
      text-align: center;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
      transition: transform .2s;
      border: none;
    }
    
    .stats-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 4px 15px rgba(0, 184, 169, 0.15);
    }
    
    .stats-number {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--primary);
    }
    
    .stats-label {
      color: #6c757d;
      font-size: .9rem;
      margin-top: .25rem;
    }
    
    .role-badge {
      font-size: .75rem;
      padding: .25em .6em;
      border-radius: .375rem;
    }
    
    .status-active {
      background: #d4edda;
      color: #155724;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .status-active:hover {
      background: #c3e6cb;
    }
    
    .status-inactive {
      background: #f8d7da;
      color: #721c24;
      cursor: pointer;
      transition: all 0.2s;
    }
    
    .status-inactive:hover {
      background: #f1b0b7;
    }
    
    .action-btn {
      background: none;
      border: none;
      color: #6c757d;
      font-size: 1.1rem;
      padding: .25rem .5rem;
      cursor: pointer;
      transition: all 0.2s;
      border-radius: 4px;
    }
    
    .action-btn:hover {
      color: #dc3545;
      background-color: rgba(220, 53, 69, 0.1);
    }
    
    .loading {
      color: #6c757d !important;
      font-style: italic;
    }
    
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .table thead th {
      background-color: var(--primary-light);
      border-bottom: 2px solid var(--primary);
      color: var(--primary-dark);
      font-weight: 600;
      padding: 1rem;
    }
    
    .table tbody td {
      padding: 1rem;
      vertical-align: middle;
    }
    
    .table-hover tbody tr:hover {
      background-color: rgba(0, 184, 169, 0.05);
    }
    
    .badge.bg-success {
      background-color: var(--primary) !important;
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
    
    @media (max-width: 992px) {
      .main-content {
        margin-left: 0;
        padding: 1.5rem;
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
    }
  </style>
</head>
<body>

  <!-- ==== SIDEBAR ==== -->
  <?php include 'admin_dashboard.php'; ?>

  <!-- ==== MAIN CONTENT ==== -->
  <div class="main-content">
    <div class="container-fluid">

      <!-- NEW: BEAUTIFUL HEADER BANNER -->
      <div class="dashboard-banner position-relative">
        <div>
          <h1>User Management</h1>
          <p>Manage all user accounts, roles, and permissions • <?php echo date('l, F j, Y'); ?></p>
        </div>
        <i class="bi bi-people-fill banner-icon"></i>
      </div>

      <!-- Search Only -->
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <input type="text" class="form-control search-bar" placeholder="Search by name, email, username..." id="searchInput">
        <!-- Add User Button Removed -->
      </div>

      <!-- ==== ROLE FILTERS ==== -->
      <div class="mb-4">
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-outline-success filter-btn active" data-role="all">All Users</button>
          <button type="button" class="btn btn-outline-success filter-btn" data-role="User">Users</button>
          <button type="button" class="btn btn-outline-success filter-btn" data-role="Admin">Admins</button>
          <button type="button" class="btn btn-outline-success filter-btn" data-role="Event Manager">Event Managers</button>
        </div>
      </div>

      <!-- ==== USERS TABLE ==== -->
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Contact</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th class="text-end pe-4">Actions</th>
                </tr>
              </thead>
              <tbody id="usersTableBody">
                <!-- Dynamic content will be loaded here -->
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <div class="loading">Loading users...</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==== STAT CARDS ==== -->
      <div class="row mt-4 g-3" id="statsContainer">
        <div class="col-6 col-md-3">
          <div class="stats-card">
            <div class="stats-number loading">...</div>
            <div class="stats-label">Total Users</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stats-card">
            <div class="stats-number loading">...</div>
            <div class="stats-label">Active Users</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stats-card">
            <div class="stats-number loading">...</div>
            <div class="stats-label">Event Managers</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stats-card">
            <div class="stats-number loading">...</div>
            <div class="stats-label">Admins</div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ==== SCRIPTS ==== -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Fetch all users from API
    async function fetchAllUsers() {
      try {
        const response = await fetch('../api/get_all_users.php');
        const data = await response.json();
        
        if (data.error) {
          console.error('Error:', data.error);
          showError('Failed to load users');
          return;
        }

        displayUsers(data.users);
        updateStats(data.users);
        
      } catch (error) {
        console.error('Error fetching users:', error);
        showError('Failed to load users');
      }
    }

    // Display users in table
    function displayUsers(users) {
      const tbody = document.getElementById('usersTableBody');
      
      if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">No users found</td></tr>';
        return;
      }

      tbody.innerHTML = users.map(user => `
        <tr class="user-row" data-role="${escapeHtml(user.role)}" data-user-id="${user.id}">
          <td class="ps-4 fw-medium">${escapeHtml(user.name)}</td>
          <td>${escapeHtml(user.username)}</td>
          <td>${escapeHtml(user.email)}</td>
          <td>${escapeHtml(user.contact || 'N/A')}</td>
          <td>
            <span class="badge role-badge ${getRoleBadgeClass(user.role)}">
              ${escapeHtml(user.role)}
            </span>
          </td>
          <td>
            <span class="badge ${user.status === 'active' ? 'status-active' : 'status-inactive'}" 
                  onclick="toggleUserStatus(${user.id}, '${user.role}', '${user.status}')"
                  title="Click to toggle status">
              ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
            </span>
          </td>
          <td class="text-end pe-4">
            <button class="action-btn text-danger" title="Delete" onclick="deleteUser(${user.id}, '${user.role}', '${escapeHtml(user.name)}')">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    }

    // Update statistics cards
    function updateStats(users) {
      const totalUsers = users.length;
      const activeUsers = users.filter(u => u.status === 'active').length;
      const eventManagers = users.filter(u => u.role === 'Event Manager').length;
      const admins = users.filter(u => u.role === 'Admin').length;

      const statsCards = document.querySelectorAll('.stats-number');
      statsCards[0].textContent = totalUsers;
      statsCards[1].textContent = activeUsers;
      statsCards[2].textContent = eventManagers;
      statsCards[3].textContent = admins;

      // Remove loading class
      statsCards.forEach(card => card.classList.remove('loading'));
    }

    // Get badge class based on role
    function getRoleBadgeClass(role) {
      switch(role) {
        case 'Admin': return 'bg-danger text-white';
        case 'Event Manager': return 'bg-warning text-dark';
        case 'User': return 'bg-secondary text-white';
        default: return 'bg-secondary text-white';
      }
    }

    // Toggle user status
    async function toggleUserStatus(userId, role, currentStatus) {
      if (!confirm(`Are you sure you want to ${currentStatus === 'active' ? 'deactivate' : 'activate'} this user?`)) {
        return;
      }

      try {
        const response = await fetch('../api/toggle_user_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id: userId,
            role: role,
            current_status: currentStatus
          })
        });

        const data = await response.json();

        if (data.success) {
          // Reload the users to reflect the change
          fetchAllUsers();
          showSuccess(`User ${currentStatus === 'active' ? 'deactivated' : 'activated'} successfully`);
        } else {
          showError(data.error || 'Failed to update user status');
        }
      } catch (error) {
        console.error('Error toggling user status:', error);
        showError('Failed to update user status');
      }
    }

    // Delete user function
    async function deleteUser(userId, role, userName) {
      if (!confirm(`Are you sure you want to delete user "${userName}"?\n\nRole: ${role}\n\nThis action cannot be undone.`)) {
        return;
      }

      try {
        const response = await fetch('../api/delete_user.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            user_id: userId,
            role: role
          })
        });

        const data = await response.json();

        if (data.success) {
          // Remove the user row from the table
          const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
          if (userRow) {
            userRow.remove();
          }
          
          // Reload stats and refresh the table
          fetchAllUsers();
          showSuccess(`User "${userName}" deleted successfully`);
        } else {
          showError(data.error || 'Failed to delete user');
        }
      } catch (error) {
        console.error('Error deleting user:', error);
        showError('Failed to delete user. Please try again.');
      }
    }

    // Search functionality
    function setupSearch() {
      const search = document.getElementById('searchInput');
      search.addEventListener('input', () => {
        const query = search.value.toLowerCase();
        const rows = document.querySelectorAll('.user-row');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(query) ? '' : 'none';
        });
      });
    }

    // Role filter functionality
    function setupRoleFilters() {
      document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
          document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          
          const selectedRole = this.dataset.role;
          const rows = document.querySelectorAll('.user-row');
          
          rows.forEach(row => {
            const userRole = row.dataset.role;
            if (selectedRole === 'all' || userRole === selectedRole) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        });
      });
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
      if (typeof unsafe !== 'string') return unsafe;
      return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    // Show success message
    function showSuccess(message) {
      // Create a custom alert with the new color scheme
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
      alertDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        background-color: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
      `;
      alertDiv.innerHTML = `
        <strong>Success!</strong> ${message}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
      `;
      document.body.appendChild(alertDiv);
      
      // Auto remove after 3 seconds
      setTimeout(() => {
        if (alertDiv.parentNode) {
          alertDiv.remove();
        }
      }, 3000);
    }

    // Show error message
    function showError(message) {
      // Create a custom alert
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
      alertDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        border-radius: 8px;
      `;
      alertDiv.innerHTML = `
        <strong>Error!</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.body.appendChild(alertDiv);
      
      // Auto remove after 5 seconds
      setTimeout(() => {
        if (alertDiv.parentNode) {
          alertDiv.remove();
        }
      }, 5000);
      
      // Update table with error message
      const tbody = document.getElementById('usersTableBody');
      tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${message}</td></tr>`;
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
      fetchAllUsers();
      setupSearch();
      setupRoleFilters();
    });
  </script>
</body>
</html>