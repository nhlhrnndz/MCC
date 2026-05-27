<?php
session_start();
include 'db_connect.php';

$login_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $login_error = "Please enter both username and password.";
    } else {
        // Query user
        $sql = "SELECT * FROM admin_users WHERE username = ? AND status = 'active' LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // If account found
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // =============================================
                // BYPASS for fnb_manager - Remove this section later
                // =============================================
                if ($username === 'fnb_manager' && $password === 'password') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['fullname'];
                    $_SESSION['admin_role'] = $user['role'];
                    
                    header("Location: admin_dashboards/fnb_manager/fnb_admin.php");
                    exit();
                }
                // =============================================
                // END OF BYPASS
                // =============================================

                if (password_verify($password, $user['password'])) {
                    // Save session - BOTH naming conventions for compatibility
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['fullname'] = $user['fullname'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['fullname'];
                    $_SESSION['admin_role'] = $user['role'];

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header("Location: admin_dashboards/administrator_sidebar/main_admin.php");
                        exit();
                    } elseif ($user['role'] === 'manager') {
                        header("Location: admin_dashboards/event_manager_sidebar/event_manager_dashboard.php");
                        exit();
                    } elseif ($user['role'] === 'fnb_manager') {
                        header("Location: admin_dashboards/fnb_manager/fnb_admin.php");
                        exit();
                    }
                } else {
                    $login_error = "Incorrect password.";
                }
            } else {
                $login_error = "Account not found or inactive.";
            }
            $stmt->close();
        } else {
            $login_error = "Database error. Please try again.";
        }
    }
}

// Show success message if redirected from signup
$success_message = "";
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Account created successfully! Please log in.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator Login | MCC</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #e3f8f6 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: 'Segoe UI', sans-serif;
      color: #333;
    }

    .login-card {
      background-color: #f9fbfc;
      border-radius: 18px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.07);
      padding: 40px 35px;
      width: 100%;
      max-width: 430px;
    }

    .icon {
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #e3f8f6;
      width: 68px;
      height: 68px;
      border-radius: 14px;
      margin-bottom: 18px;
      color: #00b8a9;
      font-size: 32px;
    }

    .restricted-box {
      background-color: #fff8e6;
      border-left: 5px solid #ffb300;
      color: #8a5a00;
      padding: 12px 15px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-size: 0.95rem;
    }

    .btn-login {
      background-color: #00b8a9;
      color: white;
      width: 100%;
      padding: 11px;
      border-radius: 10px;
      font-weight: 600;
      transition: 0.25s ease-in-out;
      border: none;
    }

    .btn-login:hover {
      background-color: #00998c;
    }

    .footer-text {
      text-align: center;
      margin-top: 18px;
      font-size: 0.9rem;
      color: #6c757d;
    }

    .footer-text a {
      color: #00b8a9;
      text-decoration: none;
      font-weight: 600;
    }

    .system-version {
      margin-top: 18px;
      text-align: center;
      font-size: 0.8rem;
      color: #888;
    }
  </style>
</head>

<body>

  <div class="login-card">

    <div class="icon">
      <i class="fa-solid fa-user-shield"></i>
    </div>

    <h4 class="fw-bold mb-1">Administrator Login</h4>
    <p class="text-muted mb-3">Access the MCC admin dashboard</p>

    <div class="restricted-box">
      <i class="fa-solid fa-circle-exclamation me-2"></i>
      <strong>Restricted Area</strong><br>
      Authorized personnel only. All login attempts are recorded.
    </div>

    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($login_error)) : ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($login_error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold">Username</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="fa-solid fa-user"></i></span>
          <input type="text" name="username" class="form-control" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
          <input type="password" name="password" class="form-control" required>
        </div>
      </div>

      <button type="submit" class="btn btn-login">
        <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
      </button>
    </form>

    <div class="footer-text mt-3">
      No Admin Account? <a href="admin_signup.php">Create One</a>
    </div>

    <div class="system-version">MCC Secure Login System</div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>