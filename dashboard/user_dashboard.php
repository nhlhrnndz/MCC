<?php 
// MCC/dashboard/user_dashboard.php
ob_start();
session_start();

$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
            . '://' . $_SERVER['HTTP_HOST'] 
            . '/MCC';
$_SESSION['base_url'] = $base_url;

if (!isset($_SESSION['username'])) {
    header("Location: ../user_login.php");
    exit();
}

// Get user info for display
$username = $_SESSION['username'];
require_once __DIR__ . '/../db_connect.php';
$userQuery = $conn->prepare("SELECT id, full_name FROM users WHERE username = ?");
$userQuery->bind_param("s", $username);
$userQuery->execute();
$userResult = $userQuery->get_result();
$currentUser = $userResult->fetch_assoc();
$user_id = $currentUser ? $currentUser['id'] : 0;
$user_name = $currentUser ? $currentUser['full_name'] : $username;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard | Malaruhatan Country Club</title>

  <!-- Bootstrap and Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    body {
      background: linear-gradient(to bottom, #f5f7fa, #e3f8f6);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .sidebar {
      height: 100vh;
      background: linear-gradient(180deg, #00b8a9 0%, #00998c 100%);
      color: white;
      padding-top: 20px;
      position: fixed;
      left: 0;
      top: 0;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      overflow-y: auto;
      width: 250px;
      z-index: 1000;
    }
    .sidebar a {
      color: white;
      text-decoration: none;
      padding: 12px 20px;
      display: flex;
      align-items: center;
      border-radius: 8px;
      margin-bottom: 5px;
      transition: 0.3s;
      font-weight: 500;
    }
    .sidebar a:hover,
    .sidebar a.active {
      background-color: rgba(255,255,255,0.2);
      transform: translateX(5px);
    }
    .sidebar a i {
      margin-right: 12px;
      width: 20px;
      text-align: center;
    }
    .sidebar h4 {
      color: white;
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .content {
      margin-left: 250px;
      padding: 30px;
      min-height: 100vh;
    }
    
    /* Notification badge */
    .badge-notif {
      background-color: #ff4444;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 0.7rem;
      margin-left: auto;
    }
    
    /* Toast Notification Styles */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
    }
    .toast-notify {
        min-width: 320px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin-bottom: 10px;
        overflow: hidden;
        animation: slideIn 0.3s ease;
        border-left: 4px solid;
    }
    .toast-notify.success { border-left-color: #28a745; }
    .toast-notify.error { border-left-color: #dc3545; }
    .toast-notify.warning { border-left-color: #ffc107; }
    .toast-notify.info { border-left-color: #17a2b8; }
    
    .toast-header {
        padding: 12px 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 500;
    }
    .toast-body {
        padding: 12px 15px;
        color: #333;
        font-size: 14px;
    }
    .toast-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #999;
        transition: color 0.2s;
    }
    .toast-close:hover { color: #333; }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @media (max-width: 768px) {
      .sidebar {
        width: 70px;
      }
      .sidebar a span {
        display: none;
      }
      .sidebar a i {
        margin-right: 0;
        font-size: 1.2rem;
      }
      .sidebar h4 {
        font-size: 0.9rem;
      }
      .sidebar h4 i {
        font-size: 1rem;
      }
      .content {
        margin-left: 70px;
        padding: 15px;
      }
      .toast-notify {
        min-width: 280px;
      }
    }
  </style>
</head>

<body>

<!-- Sidebar Navigation -->
<div class="sidebar p-3">
    <h4 class="text-center fw-bold mb-4">
        <i class="fas fa-tree me-2"></i><span>MCC Portal</span>
    </h4>

    <a href="?page=user_dashboard_content" 
       class="<?= (!isset($_GET['page']) || $_GET['page'] == 'user_dashboard_content') ? 'active' : '' ?>">
       <i class="fas fa-home"></i><span> Dashboard</span>
    </a>

    <a href="?page=reservation" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'reservation') ? 'active' : '' ?>">
       <i class="fas fa-calendar-check"></i><span> Reservations</span>
    </a>

    <a href="?page=event_proposal" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'event_proposal') ? 'active' : '' ?>">
       <i class="fas fa-bolt"></i><span> Event Proposals</span>
    </a>

    <a href="?page=sport_and_leisure" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'sport_and_leisure') ? 'active' : '' ?>">
       <i class="fas fa-trophy"></i><span> Sports & Leisure</span>
    </a>

    <!-- Maintenance Reports Link -->
    <a href="?page=my_reports" 
       class="<?= (isset($_GET['page']) && ($_GET['page'] == 'my_reports' || $_GET['page'] == 'report_issue')) ? 'active' : '' ?>">
       <i class="fas fa-tools"></i><span> Maintenance Reports</span>
    </a>

    <!-- F&B MODULE LINK -->
    <a href="?page=fnb_menu" 
       class="<?= (isset($_GET['page']) && ($_GET['page'] == 'fnb_menu' || $_GET['page'] == 'fnb_my_orders')) ? 'active' : '' ?>">
       <i class="fas fa-utensils"></i><span> Food & Beverage</span>
    </a>

    <!-- Sub-items for F&B -->
    <div class="ms-4 ps-2" style="border-left: 1px solid rgba(255,255,255,0.2); margin-bottom: 5px;">
        <a href="?page=fnb_menu" 
           class="<?= (isset($_GET['page']) && $_GET['page'] == 'fnb_menu') ? 'active' : '' ?>" style="font-size: 0.85rem; padding: 6px 20px;">
           <i class="fas fa-shopping-cart fa-xs"></i><span> Order Food</span>
        </a>
        <a href="?page=fnb_my_orders" 
           class="<?= (isset($_GET['page']) && $_GET['page'] == 'fnb_my_orders') ? 'active' : '' ?>" style="font-size: 0.85rem; padding: 6px 20px;">
           <i class="fas fa-history fa-xs"></i><span> My Orders</span>
        </a>
    </div>

    <a href="?page=user_profile" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'user_profile') ? 'active' : '' ?>">
       <i class="fas fa-user"></i><span> Profile</span>
    </a>

    <a href="?page=user_notification" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'user_notification') ? 'active' : '' ?>">
       <i class="fas fa-bell"></i><span> Notifications</span>
       <?php
       if ($user_id) {
           $notifQuery = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND status = 'unread'");
           $notifQuery->bind_param("i", $user_id);
           $notifQuery->execute();
           $notifResult = $notifQuery->get_result();
           $notifCount = $notifResult->fetch_assoc()['cnt'];
           if ($notifCount > 0) {
               echo '<span class="badge-notif">' . $notifCount . '</span>';
           }
       }
       ?>
    </a>

    <hr style="border-color: rgba(255,255,255,0.3);">
    <a href="../logout.php" style="margin-top: 20px;">
      <i class="fas fa-sign-out-alt"></i><span> Logout</span>
    </a>
</div>

<!-- Main Content Area -->
<div class="content">
    <?php
    $page = $_GET['page'] ?? 'user_dashboard_content';
    
    // Security: Only allow alphanumeric and underscore in page names
    $page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

    // Page aliases — maps URL page names to actual filenames
    $pageAliases = [
        'fnb_my_orders' => 'fnb_my_order'
    ];
    if (isset($pageAliases[$page])) {
        $page = $pageAliases[$page];
    }
    
    // Define possible file paths
    $possiblePaths = [
        __DIR__ . '/' . $page . '.php',
        __DIR__ . '/fnb/' . $page . '.php',
        __DIR__ . '/maintenance/' . $page . '.php'
    ];
    
    $file = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $file = $path;
            break;
        }
    }
    
    if ($file) {
        include $file;
    } else {
        echo '
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="alert alert-warning mt-5">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h3>Page Not Found</h3>
                        <p class="mb-3">The page you are looking for does not exist.</p>
                        <p class="text-muted small">Tried to load: ' . htmlspecialchars($page) . '</p>
                        <a href="user_dashboard.php?page=user_dashboard_content" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Return to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>';
    }
    ?>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Toast System -->
<script>
    window.Toast = {
        container: null,
        
        init() {
            this.container = document.getElementById('toastContainer');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                this.container.id = 'toastContainer';
                document.body.appendChild(this.container);
            }
        },
        
        show(message, type = 'success') {
            this.init();
            const toastId = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
            
            const icons = {
                success: '✓',
                error: '✗',
                warning: '⚠',
                info: 'ℹ'
            };
            
            const titles = {
                success: 'Success',
                error: 'Error',
                warning: 'Warning',
                info: 'Information'
            };
            
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            
            const toast = document.createElement('div');
            toast.className = `toast-notify ${type}`;
            toast.id = toastId;
            toast.innerHTML = `
                <div class="toast-header">
                    <strong style="color: ${colors[type]}">${icons[type]} ${titles[type]}</strong>
                    <button class="toast-close" onclick="Toast.close('${toastId}')">&times;</button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            
            this.container.appendChild(toast);
            
            setTimeout(() => {
                this.close(toastId);
            }, 5000);
        },
        
        close(toastId) {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (toast && toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = '<?php echo $page; ?>';
        const navLinks = document.querySelectorAll('.sidebar a');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.includes('page=')) {
                const linkPage = href.split('page=')[1];
                if (linkPage === currentPage) {
                    link.classList.add('active');
                }
            }
        });
        
        const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
        alerts.forEach(alert => {
            setTimeout(() => {
                if (alert && alert.remove) {
                    alert.remove();
                }
            }, 5000);
        });
    });
</script>
</body>
</html>