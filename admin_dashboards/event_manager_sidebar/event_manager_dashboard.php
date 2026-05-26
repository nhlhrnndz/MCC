<?php
// Enable error reporting for debugging
//MCC\admin_dashboards\event_manager_sidebar\event_manager_dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session must be the VERY first thing - no whitespace before
session_start();

// CORRECTED: Use the right path for database connection
include __DIR__ . '/../../db_connect.php';

// Include functions file
include __DIR__ . '/manager_functions.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'manager') {
    header("Location: ../index.php");
    exit();
}

// Verify database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn ? $conn->connect_error : "No connection object"));
}

// Get manager ID from session
$managerId = $_SESSION['admin_id'] ?? null;
$pendingCount = getPendingProposalsCount($managerId);

// Handle logout
if (isset($_GET['logout'])) {
    header("Location: ../../logout.php");
    exit();
}

// Get current page from URL or default to dashboard
$current_page = isset($_GET['page']) ? $_GET['page'] : 'main_manager';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Manager | MCC</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    :root {
      --primary: #00b8a9;
      --primary-dark: #00998c;
      --primary-light: #e3f8f6;
      --white: #ffffff;
      --form-bg: #f9fbfc;
      --text-dark: #2c3e50;
      --text-muted: #6c757d;
    }

    body {
      background: linear-gradient(to bottom, #f5f7fa, var(--primary-light));
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
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

    /* === SIDEBAR === */
    .sidebar {
      width: 280px;
      height: 100vh;
      background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      padding-top: 1.5rem;
      box-shadow: 2px 0 15px rgba(0, 184, 169, 0.3);
      z-index: 1000;
      display: flex;
      flex-direction: column;
    }

    .sidebar-header {
      padding: 0 1.5rem 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 1rem;
    }

    .sidebar-header h5 {
      color: var(--white);
      font-weight: 700;
      margin: 0;
      font-size: 1.25rem;
    }

    .sidebar-header p {
      color: rgba(255, 255, 255, 0.8);
      font-size: 0.85rem;
      margin: 0.25rem 0 0;
    }

    .user-info {
      color: rgba(255, 255, 255, 0.9);
      font-size: 0.8rem;
      margin-top: 0.5rem;
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.9);
      padding: 1rem 1.5rem;
      border-radius: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-weight: 500;
      transition: all 0.3s ease;
      position: relative;
      text-decoration: none;
      margin: 0.25rem 0.75rem;
      border-radius: 8px;
    }

    .nav-link-content {
      display: flex;
      align-items: center;
      gap: 12px;
      flex: 1;
    }

    .nav-link i {
      font-size: 1.2rem;
      width: 24px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.15);
      color: var(--white);
      transform: translateX(5px);
    }

    .nav-link.active {
      background: rgba(255, 255, 255, 0.2);
      color: var(--white);
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .nav-link.active i {
      color: var(--white);
      transform: scale(1.1);
    }

    .nav-link.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 4px;
      height: 60%;
      background: var(--white);
      border-radius: 0 4px 4px 0;
    }

    /* Notification badge styles */
    .nav-link .badge {
      font-size: 0.65rem;
      padding: 0.35rem 0.6rem;
      min-width: 1.75rem;
      height: 1.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 0.875rem;
      animation: pulse 2s infinite;
      background: rgba(255, 255, 255, 0.9) !important;
      color: var(--primary-dark) !important;
      font-weight: 700;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .nav-link.active .badge {
      background-color: #ff6b6b !important;
      color: var(--white) !important;
    }

    @keyframes pulse {
      0% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      50% {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      }
      100% {
        transform: scale(1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
    }

    /* Logout Button */
    .logout-btn {
      margin-top: auto;
      padding: 1.5rem;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
    }

    .logout-btn a {
      display: flex;
      align-items: center;
      gap: 12px;
      color: rgba(255, 255, 255, 0.9);
      font-weight: 500;
      text-decoration: none;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      transition: all 0.3s ease;
      background: rgba(255, 255, 255, 0.1);
    }

    .logout-btn a:hover {
      background: rgba(255, 255, 255, 0.2);
      color: var(--white);
      transform: translateX(5px);
    }

    .logout-btn i {
      font-size: 1.2rem;
      width: 24px;
      text-align: center;
    }

    /* Main Content */
    .main-content {
      margin-left: 280px;
      padding: 2rem;
      min-height: 100vh;
      background: transparent;
    }

    /* Content Cards */
    .content-header {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      margin-bottom: 2rem;
      border: 1px solid rgba(0, 184, 169, 0.1);
    }

    .content-card {
      background: var(--white);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid rgba(0, 184, 169, 0.1);
    }

    .welcome-card {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: var(--white);
      padding: 2rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      box-shadow: 0 4px 15px rgba(0, 184, 169, 0.3);
    }

    .header-card {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: var(--white);
      padding: 2rem;
      border-radius: 12px;
      margin-bottom: 2rem;
      box-shadow: 0 4px 15px rgba(0, 184, 169, 0.3);
    }

    .proposal-card {
      border-left: 4px solid var(--primary);
      transition: transform 0.3s ease;
      background: var(--white);
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .proposal-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0, 184, 169, 0.15);
    }

    .calendar-header {
      background-color: var(--primary-light);
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1rem;
      border: 1px solid rgba(0, 184, 169, 0.1);
    }

    .calendar-day {
      border: 1px solid rgba(0, 184, 169, 0.1);
      min-height: 120px;
      padding: 0.5rem;
      background: var(--white);
      transition: all 0.3s ease;
    }

    .calendar-day:hover {
      background: var(--primary-light);
    }

    .calendar-day.header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: var(--white);
      font-weight: bold;
      text-align: center;
    }

    .event-item {
      background-color: var(--primary-light);
      border-left: 3px solid var(--primary);
      padding: 0.25rem 0.5rem;
      margin-bottom: 0.25rem;
      font-size: 0.8rem;
      border-radius: 4px;
    }

    .today {
      background-color: var(--primary-light);
      border: 2px solid var(--primary);
    }

    .stat-card {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border-left: 4px solid var(--primary);
      text-align: center;
      transition: all 0.3s ease;
      border: 1px solid rgba(0, 184, 169, 0.1);
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 20px rgba(0, 184, 169, 0.15);
    }

    .chart-placeholder {
      background: var(--primary-light);
      border: 2px dashed rgba(0, 184, 169, 0.3);
      border-radius: 12px;
      height: 300px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary-dark);
    }

    .profile-card {
      background: var(--white);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
      border: 1px solid rgba(0, 184, 169, 0.1);
    }

    .profile-pic {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid var(--primary);
      box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
    }

    .dashboard-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }

    .stat-title {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-bottom: 0;
    }

    /* Custom notification styles */
    .notification-toast {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      min-width: 300px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      
      .sidebar.active {
        transform: translateX(0);
      }
      
      .main-content {
        margin-left: 0;
        padding: 1rem;
      }
      
      .nav-link {
        margin: 0.125rem 0.5rem;
        padding: 0.875rem 1rem;
      }
    }

    /* Animation for sidebar items */
    .nav-link {
      animation: slideInLeft 0.5s ease-out;
    }

    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* Staggered animation for nav items */
    .nav-link:nth-child(1) { animation-delay: 0.1s; }
    .nav-link:nth-child(2) { animation-delay: 0.2s; }
    .nav-link:nth-child(3) { animation-delay: 0.3s; }
    .nav-link:nth-child(4) { animation-delay: 0.4s; }
    .nav-link:nth-child(5) { animation-delay: 0.5s; }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
      <h5><i class="fas fa-tree me-2"></i>MCC Manager</h5>
      <p>Event Management Portal</p>
      <div class="user-info">
        <i class="fas fa-user-circle me-1"></i>
        Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="nav flex-column px-3">
      <a href="event_manager_dashboard.php?page=main_manager" class="nav-link <?php echo $current_page == 'main_manager' ? 'active' : ''; ?>">
        <div class="nav-link-content">
          <i class="fas fa-tachometer-alt"></i>
          <span>Dashboard</span>
        </div>
        <?php if ($pendingCount > 0): ?>
          <span class="badge"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
      </a>

      <a href="event_manager_dashboard.php?page=event_proposals" class="nav-link <?php echo $current_page == 'event_proposals' ? 'active' : ''; ?>">
        <div class="nav-link-content">
          <i class="fas fa-file-alt"></i>
          <span>Event Proposals</span>
        </div>
        <?php if ($pendingCount > 0): ?>
          <span class="badge"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
      </a>

      <a href="event_manager_dashboard.php?page=calendar" class="nav-link <?php echo $current_page == 'calendar' ? 'active' : ''; ?>">
        <div class="nav-link-content">
          <i class="fas fa-calendar-alt"></i>
          <span>Calendar View</span>
        </div>
      </a>

      <a href="event_manager_dashboard.php?page=reports" class="nav-link <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
        <div class="nav-link-content">
          <i class="fas fa-chart-bar"></i>
          <span>Event Reports</span>
        </div>
      </a>

      <a href="event_manager_dashboard.php?page=profile" class="nav-link <?php echo $current_page == 'profile' ? 'active' : ''; ?>">
        <div class="nav-link-content">
          <i class="fas fa-user-cog"></i>
          <span>Profile Settings</span>
        </div>
      </a>
    </nav>

    <!-- Logout Button -->
    <div class="logout-btn">
      <a href="event_manager_dashboard.php?logout=1">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </a>
    </div>
  </div>

  <!-- Main Content Area -->
  <div class="main-content">
    <?php
    // Include the content based on the current page
    $page_files = [
        'main_manager' => 'main_manager.php',
        'event_proposals' => 'event_proposals.php', 
        'calendar' => 'calendar_view.php',
        'reports' => 'event_reports.php',
        'profile' => 'manager_profile.php'
    ];
    
    if (isset($page_files[$current_page]) && file_exists($page_files[$current_page]) && is_file($page_files[$current_page])) {
        // Make database connection available to included files
        global $conn;
        include $page_files[$current_page];
    } else {
        // Log error and show default page
        error_log("Page file not found or inaccessible: " . ($page_files[$current_page] ?? 'unknown'));
        
        // Make database connection available to default page
        global $conn;
        include 'main_manager.php';
    }
    ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // AJAX functions for dynamic content
    function loadProposals(status = 'all', search = '') {
        fetch(`event_manager_dashboard.php?ajax=get_proposals&status=${status}&search=${search}`)
            .then(response => response.json())
            .then(data => {
                // Update the proposals table dynamically
                console.log('Proposals loaded:', data);
            });
    }

    function assignProposal(proposalId) {
        const formData = new FormData();
        formData.append('proposal_id', proposalId);
        
        fetch('assign_proposal.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Proposal assigned successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification('Failed to assign proposal: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error assigning proposal', 'error');
        });
    }

    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 8px;';
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

    // Add active state management for sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = '<?php echo $current_page; ?>';
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href').includes('page=' + currentPage)) {
                link.classList.add('active');
            }
        });
        
        // Add hover effects for better UX
        navLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(8px)';
            });
            
            link.addEventListener('mouseleave', function() {
                if (!this.classList.contains('active')) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });
    });
  </script>
</body>
</html>