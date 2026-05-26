<?php
session_start();
// Use relative path instead of absolute path
include 'C:\xampp\htdocs\MCC\db_connect.php';

$signup_error = "";
$signup_success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);
    $role = trim($_POST['role']);

    // Validate inputs
    if (empty($fullname) || empty($username) || empty($email) || empty($password) || empty($role)) {
        $signup_error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $signup_error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $signup_error = "Password must be at least 6 characters long.";
    } else {
        // Check if username or email already exists
        $check_sql = "SELECT id FROM admin_users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        
        if ($check_stmt) {
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $signup_error = "Username or email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO admin_users (fullname, username, email, contact, password, role, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'active')";

                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("ssssss", $fullname, $username, $email, $contact, $hashed, $role);

                    if ($stmt->execute()) {
                        $signup_success = "Account created successfully! Redirecting to login...";
                        echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'admin_login.php?success=1';
                                }, 2000);
                              </script>";
                    } else {
                        $signup_error = "Error creating account: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $signup_error = "Database error: " . $conn->error;
                }
            }
            $check_stmt->close();
        } else {
            $signup_error = "Database preparation error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup - MCC Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #30b661;
            --primary-hover: #28a156;
            --bg-light: #f8f9fa;
            --text-dark: #333;
            --text-muted: #6c757d;
            --border: #ddd;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

        body { 
            background-color: var(--bg-light); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            padding: 20px; 
        }

        .container { max-width: 500px; width: 100%; }
        
        .card { 
            background: #fff; 
            padding: 35px; 
            border-radius: 18px; 
            box-shadow: 0 6px 18px rgba(0,0,0,0.07); 
        }

        .logo { text-align: center; margin-bottom: 15px; }
        
        .logo i { 
            font-size: 45px; 
            color: var(--primary); 
            background-color: #e7f7ed; 
            padding: 15px; 
            border-radius: 12px; 
        }

        .form-title { 
            font-size: 22px; 
            font-weight: 700; 
            text-align: center; 
            margin-top: 10px; 
            color: var(--text-dark); 
        }
        
        .form-subtitle { 
            text-align: center; 
            font-size: 14px; 
            color: var(--text-muted); 
            margin-bottom: 25px; 
        }

        .form-group { margin-bottom: 18px; }
        
        label { 
            font-size: 14px; 
            font-weight: 600; 
            margin-bottom: 6px; 
            color: var(--text-dark); 
            display: block; 
        }

        .form-control, select {
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border); 
            border-radius: 10px; 
            font-size: 15px;
            transition: 0.2s;
        }

        .form-control:focus, select:focus { 
            border-color: var(--primary); 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(48, 182, 97, 0.1);
        }

        .form-row { display: flex; gap: 15px; }
        
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: var(--primary); 
            color: #fff; 
            border-radius: 10px; 
            border: none; 
            font-weight: 600; 
            cursor: pointer; 
            transition: 0.25s; 
        }
        
        .btn:hover { 
            background: var(--primary-hover); 
            transform: translateY(-1px);
        }

        .link-text { 
            margin-top: 18px; 
            text-align: center; 
            font-size: 14px; 
        }
        
        .link-text a { 
            color: var(--primary); 
            font-weight: 600; 
            text-decoration: none; 
        }
        
        .link-text a:hover { 
            text-decoration: underline; 
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }

        @media(max-width:480px) { 
            .form-row { 
                flex-direction: column; 
                gap: 0;
            } 
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <i class="fa-solid fa-user-plus"></i>
            </div>

            <h2 class="form-title">Create Admin Account</h2>
            <p class="form-subtitle">Register a new administrator or event manager</p>

            <?php if (!empty($signup_error)) : ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($signup_error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($signup_success)) : ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($signup_success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="signupForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control" 
                               value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>" 
                               required>
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                               required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" name="contact" class="form-control" 
                           value="<?= isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : '' ?>" 
                           required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                        <option value="manager" <?= (isset($_POST['role']) && $_POST['role'] === 'manager') ? 'selected' : '' ?>>Event Manager</option>
                    </select>
                </div>

                <button type="submit" class="btn">
                    <i class="fa-solid fa-user-plus me-2"></i> Create Account
                </button>
            </form>

            <div class="link-text">
                Already have an account? <a href="admin_login.php">Sign In</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side password validation
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>