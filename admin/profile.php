<?php
/**
 * Admin Panel - Profile Edit Page
 * Allow admins to edit their profile information
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$admin = $auth->getCurrentAdmin();
$admin_model = new AdminModel();
$admin_full = $admin_model->getById($admin['id']);
$activity_log = new ActivityLog();

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $first_name = Security::sanitizeInput($_POST['first_name'] ?? '');
        $last_name = Security::sanitizeInput($_POST['last_name'] ?? '');
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate
        if (!$first_name || !$last_name || !$email) {
            $error = 'First name, last name, and email are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            // Check if changing password
            if ($new_password) {
                if (!$current_password) {
                    $error = 'Current password is required to change password';
                } elseif (!Security::verifyPassword($current_password, $admin_full['password'])) {
                    $error = 'Current password is incorrect';
                } elseif (strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'Passwords do not match';
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
                    $admin_model->update($admin['id'], [
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'password' => $hashed_password,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    $message = 'Profile updated successfully!';
                    $activity_log->log('profile_update', 'admin', 'profile', $admin['id'], ['password' => '*'], ['password' => '*']);
                    // Refresh admin info
                    $_SESSION['admin_name'] = $first_name . ' ' . $last_name;
                    $admin_full = $admin_model->getById($admin['id']);
                }
            } else {
                // Just update basic info
                $admin_model->update($admin['id'], [
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                $message = 'Profile updated successfully!';
                $activity_log->log('profile_update', 'admin', 'profile', $admin['id'], [], ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email]);
                $_SESSION['admin_name'] = $first_name . ' ' . $last_name;
                $admin_full = $admin_model->getById($admin['id']);
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        Logger::log('Profile update error: ' . $e->getMessage(), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - <?php echo APP_NAME; ?></title>
    <link rel="icon" href="pabson-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #2563eb;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar-brand {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        .sidebar-brand h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .sidebar-menu {
            padding: 20px 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: white;
            color: white;
        }
        .sidebar-menu i {
            margin-right: 15px;
            width: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .form-section h5 {
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary-color);
        }
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        .btn-save {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 58, 138, 0.3);
            color: white;
        }
        .btn-back {
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: #e5e7eb;
            color: #1f2937;
        }
        .alert {
            border-radius: 5px;
            border: none;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <<h2><i class="fas fa-quiz"></i>PABSON QUIZ APP</h2>
        </div>
        <ul class="sidebar-menu list-unstyled">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <?php if ($auth->canManageContent()): ?>
                <li><a href="teams.php"><i class="fas fa-users"></i> Teams</a></li>
                <li><a href="rounds.php"><i class="fas fa-circle-notch"></i> Rounds</a></li>
                <li><a href="questions.php"><i class="fas fa-question"></i> Questions</a></li>
            <?php endif; ?>
            <li><a href="results.php"><i class="fas fa-trophy"></i> Results</a></li>
            <li><a href="leaderboard.php"><i class="fas fa-podium"></i> Leaderboard</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
            <?php if ($admin['role'] === 'super_admin'): ?>
                <li><a href="admins.php"><i class="fas fa-shield-alt"></i> Admin Management</a></li>
            <?php endif; ?>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            <li><hr style="opacity: 0.2; margin: 10px 0;"></li>
            <li><a href="profile.php" class="active"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h3 style="margin: 0; color: var(--primary-color);"><i class="fas fa-user-edit"></i> Edit Profile</h3>
                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 0.9rem;">Update your profile information</p>
            </div>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <div class="profile-card">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <!-- Basic Information -->
                <div class="form-section">
                    <h5><i class="fas fa-user"></i> Basic Information</h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="<?php echo Security::escapeOutput($admin_full['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="<?php echo Security::escapeOutput($admin_full['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?php echo Security::escapeOutput($admin_full['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" disabled 
                               value="<?php echo Security::escapeOutput($admin_full['username']); ?>">
                        <small class="form-text text-muted">Username cannot be changed</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" disabled 
                               value="<?php echo ucfirst(str_replace('_', ' ', $admin_full['role'])); ?>">
                        <small class="form-text text-muted">Contact super admin to change role</small>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="form-section">
                    <h5><i class="fas fa-lock"></i> Change Password (Optional)</h5>
                    <p class="text-muted small">Leave blank to keep current password</p>

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" 
                                   placeholder="At least 6 characters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password">
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="dashboard.php" style="padding: 10px 20px; border-radius: 5px; background: #f3f4f6; color: #374151; text-decoration: none; display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
