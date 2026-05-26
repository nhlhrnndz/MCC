<?php
// This file contains only the profile content
$current_page = 'profile';

// Database functions
function getManagerProfile($managerId) {
    global $conn;
    $query = "SELECT * FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $managerId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        return [
            'fullname' => $result['fullname'],
            'username' => $result['username'],
            'email' => $result['email'],
            'contact' => $result['contact'],
            'role' => $result['role'],
            'created_at' => $result['created_at'],
            'updated_at' => $result['updated_at']
        ];
    }
    
    return [
        'fullname' => '',
        'username' => '',
        'email' => '',
        'contact' => '',
        'role' => '',
        'created_at' => '',
        'updated_at' => ''
    ];
}

function getProfileStats($managerId) {
    global $conn;
    
    // Events managed count
    $query1 = "SELECT COUNT(*) as events_managed FROM event_proposals WHERE assigned_manager_id = ?";
    $stmt1 = $conn->prepare($query1);
    $stmt1->bind_param("i", $managerId);
    $stmt1->execute();
    $eventsManaged = $stmt1->get_result()->fetch_assoc()['events_managed'];
    
    return [
        'events_managed' => $eventsManaged
    ];
}

function updateManagerProfile($managerId, $data) {
    global $conn;
    $query = "UPDATE admin_users SET 
              fullname = ?, email = ?, contact = ?, updated_at = NOW() 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", 
        $data['fullname'], $data['email'], 
        $data['contact'], $managerId
    );
    return $stmt->execute();
}

function updateManagerPassword($managerId, $currentPassword, $newPassword) {
    global $conn;
    
    // First verify current password
    $query = "SELECT password FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $managerId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (password_verify($currentPassword, $result['password'])) {
        // Update with new hashed password
        $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateQuery = "UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", $newHashedPassword, $managerId);
        return $updateStmt->execute();
    }
    
    return false;
}

// Get manager data
$managerId = $_SESSION['admin_id'] ?? null;
$profile = getManagerProfile($managerId);
$stats = getProfileStats($managerId);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $profileData = [
            'fullname' => $_POST['fullname'] ?? '',
            'email' => $_POST['email'] ?? '',
            'contact' => $_POST['contact'] ?? ''
        ];
        
        if (updateManagerProfile($managerId, $profileData)) {
            $successMessage = "Profile updated successfully!";
            // Refresh profile data
            $profile = getManagerProfile($managerId);
            // Update session name
            $_SESSION['admin_name'] = $profileData['fullname'];
        } else {
            $errorMessage = "Error updating profile. Please try again.";
        }
    }
    
    if (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if ($newPassword === $confirmPassword) {
            if (updateManagerPassword($managerId, $currentPassword, $newPassword)) {
                $successMessage = "Password updated successfully!";
            } else {
                $errorMessage = "Current password is incorrect.";
            }
        } else {
            $errorMessage = "New passwords do not match.";
        }
    }
}
?>

<div class="content-card">
    <!-- Header -->
    <div class="header-card mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1><i class="fas fa-user-circle me-3"></i>Profile Settings</h1>
                <p class="mb-0">Manage your account information and preferences</p>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $successMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $errorMessage; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-md-4 mb-4">
            <div class="profile-card text-center">
                <div class="mb-3">
                    <img src="https://via.placeholder.com/120" 
                         alt="Profile" class="profile-pic">
                </div>
                <h4 class="text-primary"><?php echo htmlspecialchars($profile['fullname']); ?></h4>
                <p class="text-muted"><?php echo ucfirst($profile['role']); ?></p>
                <div class="mb-3">
                    <span class="badge bg-primary">Active</span>
                    <span class="badge bg-secondary"><?php echo ucfirst($profile['role']); ?></span>
                </div>
            </div>

            <!-- Account Details -->
            <div class="profile-card mt-3">
                <h6 class="text-primary mb-3">Account Details</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <small class="text-muted">Member since:</small>
                        <br><strong><?php echo !empty($profile['created_at']) ? date('F Y', strtotime($profile['created_at'])) : 'Not available'; ?></strong>
                    </li>
                    <li class="mb-2">
                        <small class="text-muted">Last updated:</small>
                        <br><strong><?php echo !empty($profile['updated_at']) ? date('M j, Y g:i A', strtotime($profile['updated_at'])) : 'Not available'; ?></strong>
                    </li>
                    <li>
                        <small class="text-muted">Events managed:</small>
                        <br><strong class="text-success"><?php echo $stats['events_managed']; ?> events</strong>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="col-md-8">
            <div class="profile-card">
                <h4 class="mb-4 text-primary">Edit Profile Information</h4>
                
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="fullname" 
                               value="<?php echo htmlspecialchars($profile['fullname']); ?>" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo htmlspecialchars($profile['email']); ?>" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" class="form-control" name="contact" 
                               value="<?php echo htmlspecialchars($profile['contact']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($profile['role']); ?>" disabled>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i> Save Changes
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i> Reset
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password -->
            <div class="profile-card mt-4">
                <h5 class="mb-4 text-primary">Change Password</h5>
                <form method="POST">
                    <input type="hidden" name="update_password" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" 
                               minlength="8" required>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-key me-2"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --primary: #00b8a9;
    --primary-dark: #00998c;
    --primary-light: #e3f8f6;
    --form-bg: #f9fbfc;
    --white: #ffffff;
}

.profile-card {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 1rem;
    border: 1px solid rgba(0, 184, 169, 0.1);
}

.profile-pic {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary);
    box-shadow: 0 4px 8px rgba(0, 184, 169, 0.2);
}

.content-card {
    background: var(--white);
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.header-card {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
}

.header-card h1 {
    margin: 0;
    font-weight: 600;
}

.header-card p {
    opacity: 0.9;
    margin: 0.5rem 0 0 0;
}

.form-label {
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.75rem;
    transition: all 0.3s ease;
    background: var(--form-bg);
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(0, 184, 169, 0.25);
    background: var(--white);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00857a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, #00857a 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 184, 169, 0.3);
}

.btn-outline-secondary {
    border: 1px solid #6c757d;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: #6c757d;
    border-color: #6c757d;
    transform: translateY(-2px);
}

.alert {
    border-radius: 8px;
    border: none;
    border-left: 4px solid transparent;
}

.alert-success {
    background-color: rgba(0, 184, 169, 0.1);
    color: #155724;
    border-left-color: var(--primary);
}

.alert-danger {
    background-color: rgba(220, 53, 69, 0.1);
    color: #721c24;
    border-left-color: #dc3545;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-weight: 500;
}

.badge.bg-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%) !important;
}

.badge.bg-secondary {
    background: #6c757d !important;
}

.list-unstyled li {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 184, 169, 0.1);
}

.list-unstyled li:last-child {
    border-bottom: none;
}

.form-text {
    font-size: 0.875rem;
    color: #6c757d;
}

.text-primary {
    color: var(--primary) !important;
}

.text-success {
    color: var(--primary) !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }
    
    .profile-card {
        padding: 1rem;
    }
    
    .profile-pic {
        width: 100px;
        height: 100px;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
    }
    
    .header-card {
        padding: 1rem;
        text-align: center;
    }
    
    .header-card h1 {
        font-size: 1.5rem;
    }
}

/* Animation for better UX */
.profile-card, .content-card {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hover effects */
.profile-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 184, 169, 0.15);
    transition: all 0.3s ease;
}
</style>