<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account | Malaruhatan Country Club</title>

<?php
include 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $full_name = $_POST['full_name'];
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $contact_number = $_POST['contact_number'];

  // ✅ Check if passwords match
  if ($password !== $confirm_password) {
    echo "<script>alert('❌ Passwords do not match! Please try again.');</script>";
  } else {
    // ✅ Hash the password for security
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $status = "active";

    $sql = "INSERT INTO users (full_name, username, email, password_hash, contact_number, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $full_name, $username, $email, $password_hash, $contact_number, $status);

    if ($stmt->execute()) {
      echo "<script>alert('✅ Account created successfully! You can now log in.'); window.location='user_login.php';</script>";
    } else {
      echo "<script>alert('❌ Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
  }
}
?>

  <link rel="stylesheet" href="bootstrap5/css/bootstrap.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  
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
    .signup-container {
      display: flex;
      width: 100%;
      max-width: 1200px;
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
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
    }

    .brand-logo {
      width: 140px;
      height: 140px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 48px;
      font-weight: 700;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      margin-bottom: 30px;
      animation: pulse 2s infinite ease-in-out;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .brand-name {
      font-size: 42px;
      font-weight: 700;
      line-height: 1.2;
      margin-bottom: 15px;
      position: relative;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .brand-subtitle {
      font-size: 20px;
      opacity: 0.9;
      position: relative;
      letter-spacing: 1px;
      font-weight: 300;
    }

    .brand-divider {
      width: 80px;
      height: 3px;
      background: rgba(255, 255, 255, 0.5);
      margin: 25px 0;
      border-radius: 2px;
    }

    /* Right panel */
    .right-panel {
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .signup-header {
      text-align: center;
      margin-bottom: 40px;
    }

    .signup-icon {
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

    .signup-header h2 {
      font-size: 28px;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 8px;
    }

    .signup-header p {
      color: var(--text-light);
      font-size: 15px;
    }

    /* Form styles */
    .form-group {
      margin-bottom: 20px;
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

    /* Password toggle styling */
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

    .btn-signup {
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

    .btn-signup:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 184, 169, 0.4);
    }

    .btn-signup:active {
      transform: translateY(0);
    }

    .login-link {
      text-align: center;
      margin-top: 30px;
      color: var(--text-light);
      font-size: 15px;
    }

    .login-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
    }

    .login-link a:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    /* Responsive styles */
    @media (max-width: 992px) {
      .signup-container {
        flex-direction: column;
        max-width: 600px;
      }
      
      .left-panel, .right-panel {
        padding: 40px 30px;
      }
      
      .brand-logo {
        width: 120px;
        height: 120px;
        font-size: 42px;
      }
      
      .brand-name {
        font-size: 36px;
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
      
      .signup-header h2 {
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

<div class="signup-container">

  <!-- Left Panel -->
  <div class="left-panel">
    <div class="brand-container">
      <div class="brand-logo">MCC</div>
      <h1 class="brand-name">Malaruhatan Country Club</h1>
      <div class="brand-divider"></div>
      <p class="brand-subtitle">Est. 1985</p>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="right-panel">
    <div class="signup-header">
      <div class="signup-icon">
        <i class="fas fa-user-plus"></i>
      </div>
      <h2>Create Account</h2>
      <p>Join MCC and start your luxury experience</p>
    </div>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
      </div>

      <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" placeholder="Choose a unique username" required>
      </div>

      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="password-input-container">
          <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
          <button type="button" class="password-toggle" id="togglePassword">
            <i class="far fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <div class="password-input-container">
          <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Re-enter your password" required>
          <button type="button" class="password-toggle" id="toggleConfirmPassword">
            <i class="far fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Contact Number</label>
        <input type="text" name="contact_number" class="form-control" placeholder="Enter your contact number">
      </div>

      <button type="submit" class="btn-signup">Create Account</button>

      <div class="login-link">
        Already have an account? <a href="user_login.php">Log In</a>
      </div>
    </form>
  </div>

</div>

<script src="bootstrap5/js/bootstrap.bundle.min.js"></script>
<script>
  // Password visibility toggle for password field
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

  // Password visibility toggle for confirm password field
  document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const icon = this.querySelector('i');
    
    if (confirmPasswordInput.type === 'password') {
      confirmPasswordInput.type = 'text';
      icon.classList.remove('fa-eye');
      icon.classList.add('fa-eye-slash');
      this.setAttribute('aria-label', 'Hide password');
    } else {
      confirmPasswordInput.type = 'password';
      icon.classList.remove('fa-eye-slash');
      icon.classList.add('fa-eye');
      this.setAttribute('aria-label', 'Show password');
    }
    
    // Focus back on the confirm password field for better UX
    confirmPasswordInput.focus();
  });
</script>
</body>
</html>