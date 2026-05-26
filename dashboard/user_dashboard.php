<?php 

//MCC\dashboard\user_dashboard.php
ob_start();
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: user_login.php");
    exit();
}
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
    /* Your existing CSS styles here */
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
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      overflow-y: auto;
      width: 250px;
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
    /* ... rest of your CSS ... */
  </style>
</head>

<body>
  <!-- Sidebar Navigation -->
  <div class="sidebar p-3">
    <h4 class="text-center fw-bold mb-4">
      <i class="fas fa-tree me-2"></i>MCC Portal
    </h4>

    <a href="user_dashboard.php?page=user_dashboard_content" 
       class="<?= (!isset($_GET['page']) || $_GET['page'] == 'user_dashboard_content') ? 'active' : '' ?>">
       <i class="fas fa-home"></i> Dashboard
    </a>

    <a href="user_dashboard.php?page=reservation" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'reservation') ? 'active' : '' ?>">
       <i class="fas fa-calendar-check"></i> Reservations
    </a>

    <a href="user_dashboard.php?page=event_proposal" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'event_proposal') ? 'active' : '' ?>">
       <i class="fas fa-bolt"></i> Event Proposals
    </a>

    <a href="user_dashboard.php?page=sport_and_leisure" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'sport_and_leisure') ? 'active' : '' ?>">
       <i class="fas fa-trophy"></i> Sports & Leisure
    </a>

    <a href="user_dashboard.php?page=user_profile" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'user_profile') ? 'active' : '' ?>">
       <i class="fas fa-user"></i> Profile
    </a>

    <a href="user_dashboard.php?page=user_notification" 
       class="<?= (isset($_GET['page']) && $_GET['page'] == 'user_notification') ? 'active' : '' ?>">
       <i class="fas fa-bell"></i> Notifications
    </a>

    <hr style="border-color: rgba(255,255,255,0.3);">
<a href="../logout.php" style="margin-top: 20px;">
  <i class="fas fa-sign-out-alt"></i> Logout
</a>
  </div>

  <!-- Main Content Area -->
  <div class="content">
    <?php
    // Default page if none specified
    $page = $_GET['page'] ?? 'user_dashboard_content';
    
    // Security: Only allow alphanumeric and underscore in page names
    $page = preg_replace('/[^a-zA-Z0-9_]/', '', $page);
    
    // Since all files are in the same dashboard folder, no subfolder needed
    $file = $page . ".php";
    
    // Check if file exists and include it
    if (file_exists($file)) {
        include $file;
    } else {
        // Show error page with helpful message
        echo '
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <div class="alert alert-warning mt-5">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h3>Page Not Found</h3>
                        <p class="mb-3">The page you are looking for does not exist.</p>
                        <p class="text-muted small">Tried to load: ' . htmlspecialchars($file) . '</p>
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Scripts -->
  <script>
    // Add active class to current page link
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = '<?php echo $page; ?>';
        const navLinks = document.querySelectorAll('.sidebar a');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href').includes('page=' + currentPage)) {
                link.classList.add('active');
            }
        });
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
  </script>
</body>
</html>