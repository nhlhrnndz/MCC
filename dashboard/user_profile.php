<div class="container-fluid">
    <!-- ========================================= -->
    <!-- PAGE HEADER                               -->
    <!-- ========================================= -->
    <div class="dashboard-header">
        <div class="welcome-card p-4 position-relative mb-4">
            <h1 class="fw-bold mb-2">Profile Settings</h1>
            <p class="mb-0 opacity-75">Manage your account and security settings</p>
            <i class="fas fa-user-circle welcome-icon"></i>
        </div>
    </div>

    <?php
    // Simulated user data (pwede mo palitan sa database connection later)
    $stored_password = "current123";
    $message = "";

    // Handle form submit
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        // ✅ Handle profile info (demo)
        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';

        // ✅ Handle password change
        if (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $message .= "<br>❌ Please fill out all password fields!";
            } elseif ($current_password !== $stored_password) {
                $message .= "<br>❌ Incorrect current password!";
            } elseif ($new_password !== $confirm_password) {
                $message .= "<br>❌ New passwords do not match!";
            } else {
                $stored_password = $new_password;
                $message .= "<br>✅ Password updated successfully!";
            }
        }
    }
    ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#profile">Profile Information</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#security">Security</a>
        </li>
    </ul>

    <?php if (!empty($message)): ?>
        <div class="alert <?= strpos($message, '❌') !== false ? 'alert-danger' : 'alert-success' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Profile Information Tab -->
        <div class="tab-pane fade show active" id="profile">
            <form method="POST">
                <div class="card p-4">
                    <h5 class="fw-semibold mb-3">
                        <i class="fas fa-user-circle text-success"></i> Account Information
                    </h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="fullname" class="form-control" placeholder="Full Name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="Phone Number">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success px-4 mt-2 w-100">
                        <i class="fas fa-save"></i> Save Profile Information
                    </button>
                </div>
            </form>
        </div>

        <!-- Security Tab -->
        <div class="tab-pane fade" id="security">
            <form method="POST">
                <div class="card p-4">
                    <h5 class="fw-semibold mb-3">
                        <i class="fas fa-shield-alt text-success"></i> Change Password
                    </h5>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Current Password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="New Password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password">
                    </div>

                    <button type="submit" name="update_password" class="btn btn-success w-100">
                        <i class="fas fa-sync-alt"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
}

/* Welcome Card Styles */
.welcome-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 184, 169, 0.3);
}

.welcome-icon {
    font-size: 4rem;
    opacity: 0.2;
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
}

.dashboard-header {
    margin-bottom: 30px;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-3px);
}

.nav-tabs .nav-link.active {
    border-color: var(--primary) var(--primary) #fff;
    color: var(--primary);
    font-weight: 500;
}

.nav-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
}

.nav-tabs .nav-link:hover {
    color: var(--primary);
    border-color: transparent;
}

.btn-success {
    background-color: var(--primary);
    border-color: var(--primary);
}

.btn-success:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 184, 169, 0.3);
}

.text-success {
    color: var(--primary) !important;
}

.alert-success {
    background-color: var(--primary-light);
    border-color: var(--primary);
    color: var(--primary-dark);
}
</style>