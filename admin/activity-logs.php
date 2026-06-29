<?php
/**
 * Activity Logs Page
 * View all admin and system activity
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$admin = $auth->getCurrentAdmin();
$activity_log_model = new ActivityLog();

// Get activity logs from database
$logs = [];
try {
    $db = Database::getInstance();
    
    // Query activity logs with admin info, ordered by created_at descending
    $query = "SELECT al.*, 
                     CONCAT(a.first_name, ' ', a.last_name) as admin_name,
                     a.username as admin_username
              FROM activity_logs al
              LEFT JOIN admins a ON al.admin_id = a.id
              ORDER BY al.created_at DESC LIMIT 500";
    $db->query($query);
    
    $logs = $db->resultSet();
} catch (Exception $e) {
    $logs = [];
    Logger::log('Error fetching activity logs: ' . $e->getMessage(), 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?php echo APP_NAME; ?></title>
    <link rel="icon" href="pabson-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
            <li><a href="activity-logs.php" class="active"><i class="fas fa-history"></i> Activity Logs</a></li>
            <?php if ($admin['role'] === 'super_admin'): ?>
                <li><a href="admins.php"><i class="fas fa-shield-alt"></i> Admin Management</a></li>
            <?php endif; ?>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            <li><hr style="opacity: 0.2; margin: 10px 0;"></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="navbar-top">
            <h3><i class="fas fa-history"></i> Activity Logs</h3>
        </div>

        <!-- Logs Table -->
        <div class="card-modern">
            <div class="card-modern-body">
                <table class="table table-striped" id="logsTable">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Entity</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><small><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                    <td>
                                        <small>
                                            <?php if ($log['admin_name'] && $log['admin_name'] !== ' '): ?>
                                                <span class="badge bg-primary"><?php echo Security::escapeOutput($log['admin_name']); ?></span><br>
                                                <code style="font-size: 0.8em;"><?php echo Security::escapeOutput($log['admin_username']); ?></code>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">System</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($log['action']); ?></span></td>
                                    <td><?php echo ucfirst($log['module']); ?></td>
                                    <td><?php echo ucfirst($log['entity_type']); ?></td>
                                    <td>
                                        <small>
                                            <?php if ($log['new_values']): ?>
                                                <button class="btn btn-xs btn-outline-primary" data-bs-toggle="tooltip" title="<?php echo Security::escapeOutput(substr($log['new_values'], 0, 100)); ?>">
                                                    View
                                                </button>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No activity logs found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
