<?php
// admin_dashboards/administrator_sidebar/admin_dashboard.php
// Clean sidebar with teal theme - SIDEBAR ONLY (no HTML wrapper)

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
  .sidebar {
    width: 260px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: linear-gradient(180deg, #00b8a9 0%, #00998c 100%);
    color: white;
    padding: 30px 20px;
    box-shadow: 4px 0 20px rgba(0,0,0,0.15);
    z-index: 1000;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }

  .sidebar-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.2);
  }

  .sidebar-header h5 {
    color: white;
    font-weight: 700;
    font-size: 1.6rem;
    margin: 0;
  }

  .sidebar-header p {
    color: rgba(255,255,255,0.8);
    font-size: 0.9rem;
    margin: 8px 0 0;
  }

  .nav-link {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-radius: 12px;
    margin: 6px 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
  }

  .nav-link i {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
  }

  .nav-link:hover {
    background: rgba(255,255,255,0.2);
    transform: translateX(8px);
    color: white;
  }

  .nav-link.active {
    background: rgba(255,255,255,0.25);
    color: white;
    font-weight: 600;
    transform: translateX(8px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }

  .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 5px;
    height: 60%;
    background: white;
    border-radius: 0 3px 3px 0;
  }

  .badge-notif {
    background: white;
    color: #00998c;
    font-weight: bold;
    font-size: 0.75rem;
    padding: 4px 9px;
    border-radius: 50px;
    margin-left: auto;
  }

  .logout-btn {
    margin-top: auto;
    padding: 20px 10px 10px;
    border-top: 1px solid rgba(255,255,255,0.2);
  }

  .logout-btn a {
    color: #ff6b6b !important;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 500;
    transition: all 0.3s;
    text-decoration: none;
  }

  .logout-btn a:hover {
    background: rgba(255,107,107,0.2);
    color: white !important;
    transform: translateX(8px);
  }

  /* Responsive */
  @media (max-width: 992px) {
    .sidebar {
      width: 80px;
      padding: 30px 10px;
    }
    .sidebar-header h5, .sidebar-header p, .nav-link span, .badge-notif {
      display: none;
    }
    .nav-link {
      justify-content: center;
      padding: 16px;
      margin: 8px 5px;
    }
    .nav-link i {
      font-size: 1.4rem;
      margin: 0;
    }
    .logout-btn a span { display: none; }
    .logout-btn a { justify-content: center; }
  }
</style>

<div class="sidebar">
  <div class="sidebar-header">
    <h5>MCC Admin</h5>
    <p>Control Center</p>
  </div>

  <nav class="flex-column">
    <a href="main_admin.php" class="nav-link <?php echo ($current_page == 'main_admin.php') ? 'active' : ''; ?>">
      <i class="fas fa-tachometer-alt"></i>
      <span>Dashboard Overview</span>
    </a>

    <a href="users.php" class="nav-link <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">
      <i class="fas fa-users"></i>
      <span>User Management</span>
    </a>

    <a href="reservations.php" class="nav-link <?php echo ($current_page == 'reservations.php') ? 'active' : ''; ?>">
      <i class="fas fa-calendar-check"></i>
      <span>Reservations</span>
    </a>

    <a href="event_proposal_admin.php" class="nav-link <?php echo ($current_page == 'event_proposal_admin.php') ? 'active' : ''; ?>">
      <i class="fas fa-file-lines"></i>
      <span>Event Proposals</span>
    </a>

    <!-- NEW: Maintenance Management Link -->
    <a href="maintenance_admin.php" class="nav-link <?php echo ($current_page == 'maintenance_admin.php') ? 'active' : ''; ?>">
      <i class="fas fa-tools"></i>
      <span>Maintenance</span>
    </a>

    <a href="notification_admin.php" class="nav-link <?php echo ($current_page == 'notification_admin.php') ? 'active' : ''; ?>">
      <i class="fas fa-bell"></i>
      <span>Notifications</span>
    </a>

    <a href="admin_reports.php" class="nav-link <?php echo ($current_page == 'admin_reports.php') ? 'active' : ''; ?>">
      <i class="fas fa-chart-bar"></i>
      <span>Reports & Analytics</span>
    </a>

    <a href="refunds_request.php" class="nav-link <?php echo ($current_page == 'refunds_request.php') ? 'active' : ''; ?>">
      <i class="fas fa-money-bill-wave"></i>
      <span>Refund Requests</span>
      <span class="badge bg-warning float-end" id="sidebarRefundBadge" style="margin-left: auto; font-size: 0.7rem;">0</span>
    </a>

    <a href="admin_profile.php" class="nav-link <?php echo ($current_page == 'admin_profile.php') ? 'active' : ''; ?>">
      <i class="fas fa-cog"></i>
      <span>Settings</span>
    </a>
  </nav>

  <div class="logout-btn">
    <a href="../../logout.php">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</div>

<script>
  // Fetch pending refund count for badge
  async function fetchRefundBadgeCount() {
    try {
      const response = await fetch('../api/get_pending_refund_count.php');
      const data = await response.json();
      const badge = document.getElementById('sidebarRefundBadge');
      if (badge && data.count > 0) {
        badge.textContent = data.count;
        badge.style.display = 'inline-block';
      } else if (badge) {
        badge.style.display = 'none';
      }
    } catch (error) {
      console.error('Error fetching refund count:', error);
    }
  }
  
  document.addEventListener('DOMContentLoaded', fetchRefundBadgeCount);
</script>