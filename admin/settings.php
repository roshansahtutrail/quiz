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
    <title>Settings -<?php echo APP_NAME; ?></title>
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
            <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            <li><hr style="opacity: 0.2; margin: 10px 0;"></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="navbar-top">
            <h3><i class="fas fa-cog"></i> System Settings</h3>
        </div>

        <!-- General Settings -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5>General Settings</h5>
            </div>
            <div class="card-modern-body">
                <form>
                    <div class="mb-3">
                        <label for="appName" class="form-label">Application Name</label>
                        <input type="text" class="form-control" id="appName" value="<?php echo APP_NAME; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="appUrl" class="form-label">Application URL</label>
                        <input type="text" class="form-control" id="appUrl" value="<?php echo APP_URL; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="dbHost" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="dbHost" value="localhost" readonly>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quiz Settings -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5>Quiz Configuration</h5>
            </div>
            <div class="card-modern-body">
                <form>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="questionTime" class="form-label">Default Time per Question (seconds)</label>
                                <input type="number" class="form-control" id="questionTime" value="30">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sessionTimeout" class="form-label">Session Timeout (minutes)</label>
                                <input type="number" class="form-control" id="sessionTimeout" value="30">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" disabled><i class="fas fa-save"></i> Save Settings</button>
                </form>
            </div>
        </div>

        <!-- System Info -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5>System Information</h5>
            </div>
            <div class="card-modern-body">
                <table class="table">
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Server OS:</strong></td>
                        <td><?php echo php_uname(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td>MySQL 8+</td>
                    </tr>
                    <tr>
                        <td><strong>Framework:</strong></td>
                        <td>PHP OOP with Bootstrap 5.3</td>
                    </tr>
                </table>
            </div>
        </div>

        <?php if ($auth->getCurrentAdmin()['role'] === 'super_admin'): ?>
        <!-- Reset Quiz Data -->
        <div class="card-modern">
            <div class="card-modern-header">
                <h5><i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Reset Quiz Data</h5>
            </div>
            <div class="card-modern-body">
                <div class="alert alert-warning" role="alert">
                    <strong><i class="fas fa-warning"></i> Warning!</strong> Resetting quiz data will permanently delete all team answers, points, rankings, and results. This action cannot be undone!
                </div>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i> Use this feature when you want to clear all quiz progress and start fresh with a new competition round.
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-redo"></i> Reset All Quiz Data</h6>
                                <p class="card-text">Clear all team answers, points, positions, and results from the system.</p>
                                <button type="button" class="btn btn-danger" id="btnResetAll" data-bs-toggle="modal" data-bs-target="#resetConfirmModal">
                                    <i class="fas fa-trash-alt"></i> Reset All Data
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-sync"></i> Reset Statistics</h6>
                                <p class="card-text">View current data before resetting.</p>
                                <button type="button" class="btn btn-info" id="btnGetStats">
                                    <i class="fas fa-chart-line"></i> View Statistics
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div id="resetStats" style="display: none;">
                    <h6><i class="fas fa-bar-chart"></i> Current Data Summary</h6>
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td><strong>Team Answers on Record:</strong></td>
                                <td><span class="badge bg-primary" id="statAnswers">0</span></td>
                            </tr>
                            <tr>
                                <td><strong>Round Results:</strong></td>
                                <td><span class="badge bg-info" id="statRoundResults">0</span></td>
                            </tr>
                            <tr>
                                <td><strong>Teams with Scores:</strong></td>
                                <td><span class="badge bg-success" id="statTeamsWithScores">0</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="resetConfirmLabel">
                        <i class="fas fa-exclamation-circle"></i> Confirm Reset
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger" role="alert">
                        <strong>⚠️ CRITICAL ACTION!</strong>
                    </div>
                    <p>Are you absolutely sure you want to reset all quiz data?</p>
                    <p>This will permanently delete:</p>
                    <ul>
                        <li>✓ All team answers</li>
                        <li>✓ All round results</li>
                        <li>✓ All points and scores</li>
                        <li>✓ All leaderboard rankings</li>
                    </ul>
                    <p class="text-danger"><strong>This action CANNOT be undone!</strong></p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmReset">
                        <label class="form-check-label" for="confirmReset">
                            I understand the consequences and want to reset all data
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmReset" disabled>
                        <i class="fas fa-trash-alt"></i> Yes, Reset All Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Reset confirmation checkbox handler
        document.getElementById('confirmReset').addEventListener('change', function() {
            document.getElementById('btnConfirmReset').disabled = !this.checked;
        });

        // Get statistics button
        document.getElementById('btnGetStats').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

            fetch('<?php echo APP_URL; ?>/ajax/reset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=get_statistics'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('statAnswers').textContent = stats.team_answers || 0;
                    document.getElementById('statRoundResults').textContent = stats.round_results || 0;
                    document.getElementById('statTeamsWithScores').textContent = stats.teams_with_scores || 0;
                    document.getElementById('resetStats').style.display = 'block';
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-chart-line"></i> View Statistics';
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to load statistics', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-chart-line"></i> View Statistics';
            });
        });

        // Confirm reset button
        document.getElementById('btnConfirmReset').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';

            fetch('<?php echo APP_URL; ?>/ajax/reset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=reset_all'
            })
            .then(response => response.json())
            .then(data => {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('resetConfirmModal'));
                modal.hide();

                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh page
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
                
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Yes, Reset All Data';
                document.getElementById('confirmReset').checked = false;
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to reset quiz data', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt"></i> Yes, Reset All Data';
            });
        });

        // Reset modal when closed
        document.getElementById('resetConfirmModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('confirmReset').checked = false;
            document.getElementById('btnConfirmReset').disabled = true;
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
