<?php
session_start();
// Add your authentication check here
$current_page = 'admin_profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - MCC Admin</title>
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
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            background: var(--white);
        }
        
        .card-header {
            background-color: var(--white);
            border-bottom: 1px solid var(--primary-light);
            padding: 1.25rem;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }
        
        .badge.bg-primary {
            background-color: var(--primary) !important;
        }
        
        .badge.bg-success {
            background-color: var(--primary) !important;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(0, 184, 169, 0.25);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
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
    <div class="d-flex">
        <?php include 'admin_dashboard.php'; ?>
        
        <div class="main-content flex-grow-1">
            <div class="container-fluid">
                <!-- NEW: BEAUTIFUL HEADER BANNER -->
                <div class="dashboard-banner position-relative">
                    <div>
                        <h1>Admin Settings</h1>
                        <p>Manage your account and system preferences • <?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <i class="bi bi-gear-fill banner-icon"></i>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="mb-4">
                                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                        <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                                    </div>
                                </div>
                                <h4><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?></h4>
                                <p class="text-muted">System Administrator</p>
                                <div class="mb-3">
                                    <span class="badge bg-primary">Super Admin</span>
                                    <span class="badge bg-success">Active</span>
                                </div>
                                <!-- Photo change button removed -->
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Administrator'); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" value="admin@mcc.com">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" value="+1 (555) 123-4567">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Department</label>
                                        <input type="text" class="form-control" value="Administration">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg"></i> Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>