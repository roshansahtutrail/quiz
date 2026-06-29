<?php
require_once '../includes/config.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="pabson-logo.svg">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-quiz"></i>PABSON QUIZ APP</h2>
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
            <?php if ($auth->getCurrentAdmin()['role'] === 'super_admin'): ?>
                <li><a href="admins.php"><i class="fas fa-shield-alt"></i> Admin Management</a></li>
            <?php endif; ?>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="backup.php" class="active"><i class="fas fa-database"></i> Backup</a></li>
            <li><hr style="opacity: 0.2; margin: 10px 0;"></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="navbar-top">
            <h3><i class="fas fa-database"></i> Database Backup</h3>
        </div>

        <!-- Backup Info -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5>Create Backup</h5>
            </div>
            <div class="card-modern-body">
                <p>Create a backup of the entire database to protect your quiz data.</p>
                <button type="button" class="btn btn-success" onclick="alert('Backup functionality coming soon')">
                    <i class="fas fa-download"></i> Download Database Backup
                </button>
                <button type="button" class="btn btn-primary" onclick="alert('Backup functionality coming soon')">
                    <i class="fas fa-refresh"></i> Create New Backup
                </button>
            </div>
        </div>

        <!-- Restore Backup -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5>Restore from Backup</h5>
            </div>
            <div class="card-modern-body">
                <p>Restore the database from a previously saved backup file.</p>
                <form enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="backupFile" class="form-label">Select Backup File (.sql)</label>
                        <input type="file" class="form-control" id="backupFile" accept=".sql" disabled>
                    </div>
                    <button type="button" class="btn btn-warning" disabled onclick="alert('Restore functionality coming soon')">
                        <i class="fas fa-upload"></i> Restore Database
                    </button>
                </form>
            </div>
        </div>

        <!-- Backup History -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5>Backup History</h5>
            </div>
            <div class="card-modern-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Backup Date</th>
                            <th>File Size</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No backups yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
