<?php
session_start();
include 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/user_dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = "❌ Please enter both username and password";
    } else {
        // Use the same query structure as your old login file
        $sql = "SELECT id, username, password_hash, status FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if ($user['status'] !== 'active') {
                $error = "❌ Account is deactivated. Please contact support.";
            } elseif (password_verify($password, $user['password_hash'])) {
                // Login successful - set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['logged_in'] = true;

                // Get additional user info
                $info_sql = "SELECT full_name, email, contact_number FROM users WHERE id = ?";
                $info_stmt = $conn->prepare($info_sql);
                $info_stmt->bind_param("i", $user['id']);
                $info_stmt->execute();
                $info_result = $info_stmt->get_result();

                if ($info_result->num_rows > 0) {
                    $info = $info_result->fetch_assoc();
                    $_SESSION['full_name'] = $info['full_name'];
                    $_SESSION['email'] = $info['email'];
                    $_SESSION['contact_number'] = $info['contact_number'];
                }
                $info_stmt->close();
                $stmt->close();

                // Redirect to dashboard
                header("Location: dashboard/user_dashboard.php");
                exit();
            } else {
                $error = "❌ Invalid username or password";
            }
        } else {
            $error = "❌ Invalid username or password";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Login | Malaruhatan Country Club</title>

  <link rel="stylesheet" href="bootstrap5/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root {
      --primary: #00b8a9;
      --primary-dark: #00998c;
      --primary-light: #e3f8f6;
      --text-dark: #1f2d3d;
      --text-light: #4f5b66;
      --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
      --shadow-hover: 0 15px 40px rgba(0, 0, 0, 0.12);
      --transition: all 0.3s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e3f8f6 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow-x: hidden;
      animation: fadeIn 1s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    /* Floating elements background */
    .floating-elements {
      position: fixed;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: 0;
      overflow: hidden;
    }

    .floating-element {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.7);
      filter: blur(20px);
      animation: float 15s infinite ease-in-out;
    }

    .floating-element:nth-child(1) {
      width: 180px;
      height: 180px;
      top: 15%;
      left: 10%;
      animation-delay: 0s;
    }

    .floating-element:nth-child(2) {
      width: 250px;
      height: 250px;
      bottom: 10%;
      right: 8%;
      animation-delay: 2s;
    }

    .floating-element:nth-child(3) {
      width: 150px;
      height: 150px;
      bottom: 25%;
      left: 35%;
      animation-delay: 4s;
    }

    @keyframes float {
      0%, 100% { 
        transform: translateY(0) rotate(0deg); 
      }
      33% { 
        transform: translateY(-30px) rotate(120deg); 
      }
      66% { 
        transform: translateY(15px) rotate(240deg); 
      }
    }

    /* Main container */
    .login-container {
      display: flex;
      width: 100%;
      max-width: 1100px;
      background: white;
      border-radius: 24px;
      box-shadow: var(--shadow);
      overflow: hidden;
      position: relative;
      z-index: 1;
      animation: slideUp 0.8s ease forwards;
      opacity: 0;
    }

    @keyframes slideUp {
      from { 
        transform: translateY(30px); 
        opacity: 0; 
      }
      to { 
        transform: translateY(0); 
        opacity: 1; 
      }
    }

    /* Left panel */
    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .left-panel::before {
      content: "";
      position: absolute;
      width: 200%;
      height: 200%;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
      top: 0;
      left: 0;
      animation: slide 25s linear infinite;
    }

    @keyframes slide {
      0% { transform: translateX(-50%) translateY(-50%); }
      100% { transform: translateX(0) translateY(0); }
    }

    .brand-container {
      display: flex;
      flex-direction: collection;
      align-items: center;
      margin-bottom: 40px;
    }

    .brand-logo {
      width: 120px;
      height: 120px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      font-weight: 700;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      margin-bottom: 25px;
    }

    .brand-name {
      font-size: 36px;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 10px;
      position: relative;
    }

    .brand-subtitle {
      font-size: 18px;
      opacity: 0.9;
      position: relative;
    }

    /* Right panel */
    .right-panel {
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .login-icon {
      width: 70px;
      height: 70px;
      background: var(--primary-light);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--primary);
      font-size: 24px;
      margin: 0 auto 20px;
      box-shadow: 0 5px 15px rgba(0, 184, 169, 0.2);
    }

    .login-header h2 {
      font-size: 28px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .login-header p {
      color: var(--text-light);
      font-size: 15px;
    }

    /* Form styles */
    .form-group {
      margin-bottom: 24px;
      position: relative;
    }

    .form-label {
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 8px;
      display: block;
    }

    .form-control {
      border-radius: 12px;
      padding: 14px 50px 14px 16px;
      font-size: 15px;
      border: 1.5px solid #e1e5e9;
      transition: var(--transition);
      background: #f9fbfc;
    }

    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(0, 184, 169, 0.15);
      background: white;
    }

    /* Error message styling */
    .alert-danger {
      border-radius: 12px;
      border: none;
      background: #ffe6e6;
      color: #d63031;
      border-left: 4px solid #d63031;
    }

    /* Improved password toggle styling */
    .password-input-container {
      position: relative;
    }

    .password-toggle {
      position: absolute;
      right: 16px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--text-light);
      cursor: pointer;
      transition: var(--transition);
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
    }

    .password-toggle:hover {
      color: var(--primary);
      background-color: rgba(0, 184, 169, 0.1);
    }

    .password-toggle:active {
      transform: translateY(-50%) scale(0.95);
    }

    .forgot-password {
      color: var(--primary);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: var(--transition);
    }

    .forgot-password:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    .btn-login {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 14px;
      font-size: 16px;
      font-weight: 600;
      transition: var(--transition);
      box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
      width: 100%;
      margin-top: 10px;
    }

    .btn-login:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 184, 169, 0.4);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .signup-link {
      text-align: center;
      margin-top: 30px;
      color: var(--text-light);
      font-size: 15px;
    }

    .signup-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .signup-link a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    /* Admin Link */
    .admin-link {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .admin-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
      transition: var(--transition);
    }

    .admin-link a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    /* Responsive styles */
    @media (max-width: 992px) {
      .login-container {
        flex-direction: column;
        max-width: 500px;
      }
      
      .left-panel, .right-panel {
        padding: 40px 30px;
      }
    }

    @media (max-width: 576px) {
      .left-panel, .right-panel {
        padding: 30px 20px;
      }
      
      .brand-name {
        font-size: 28px;
      }
      
      .brand-logo {
        width: 100px;
        height: 100px;
        font-size: 36px;
      }
      
      .login-header h2 {
        font-size: 24px;
      }
    }
  </style>
</head>

<body>

<!-- Floating background elements -->
<div class="floating-elements">
  <div class="floating-element"></div>
  <div class="floating-element"></div>
  <div class="floating-element"></div>
</div>

<div class="login-container">

  <!-- Left Panel -->
  <div class="left-panel">
    <div class="brand-container">
      <div class="brand-logo">MCC</div>
      <h1 class="brand-name">Malaruhatan Country Club</h1>
      <p class="brand-subtitle"></p>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="right-panel">
    <div class="login-header">
      <div class="login-icon">
        <i class="fas fa-user"></i>
      </div>
      <h2>Welcome Back</h2>
      <p>Sign in to your account to continue</p>
    </div>

    <!-- Error Display -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Enter your username" required 
               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      </div>

      <div class="form-group">
        <div class="d-flex justify-content-between">
          <label class="form-label">Password</label>
          <a href="#" class="forgot-password">Forgot password?</a>
        </div>
        <div class="password-input-container">
          <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
          <button type="button" class="password-toggle" id="togglePassword">
            <i class="far fa-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-login">Sign In</button>
    </form>

    <div class="signup-link">
      Don't have an account? <a href="signup_user.php">Sign up</a>
    </div>
  </div>

</div>

<script src="bootstrap5/js/bootstrap.bundle.min.js"></script>
<script>
  // Password visibility toggle
  document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
      this.setAttribute('aria-label', 'Hide password');
    } else {
      passwordInput.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
      this.setAttribute('aria-label', 'Show password');
    }
    
    // Focus back on the password field for better UX
    passwordInput.focus();
  });

  // Ctrl+K keyboard shortcut for admin login
  document.addEventListener('keydown', function(event) {
    // Check if Control (or Command on Mac) + K is pressed
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
      // Prevent the browser's default search behavior
      event.preventDefault();
      
      // Redirect to admin login page
      window.location.href = 'admin_login.php';
    }
  });
</script>
</body>
</html>