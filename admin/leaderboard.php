<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$leaderboard = new LeaderboardModel();
$top_teams = $leaderboard->getLeaderboard();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard -<?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="pabson-logo.svg">
</head>
<body data-page="leaderboard">
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
            <li><a href="leaderboard.php" class="active"><i class="fas fa-podium"></i> Leaderboard</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
            <?php if ($auth->getCurrentAdmin()['role'] === 'super_admin'): ?>
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
        <!-- Navbar -->
        <div class="navbar-top">
            <h3><i class="fas fa-trophy"></i> Leaderboard</h3>
        </div>

        <!-- Leaderboard Table -->
        <div class="card-modern">
            <div class="card-modern-body">
                <table class="table table-modern table-hover">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Rank</th>
                            <th>Team Name</th>
                            <th>School</th>
                            <th style="width: 100px;">Score</th>
                            <th style="width: 100px;">Percentage</th>
                            <th style="width: 100px;">Correct</th>
                            <th style="width: 100px;">Wrong</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboardBody">
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Leaderboard Update
        let lastData = null;
        const POLL_INTERVAL = 3000; // Update every 3 seconds

        function loadLeaderboard() {
            fetch('ajax/get_live_data.php?page=leaderboard')
                .then(response => response.json())
                .then(data => {
                    if (data.leaderboard) {
                        updateLeaderboardTable(data.leaderboard);
                    }
                })
                .catch(error => {
                    console.error('Error loading leaderboard:', error);
                });
        }

        function updateLeaderboardTable(teams) {
            const tbody = document.getElementById('leaderboardBody');
            
            // Check if data has changed
            const currentData = JSON.stringify(teams);
            if (currentData === lastData) {
                return; // No change, skip update
            }
            lastData = currentData;

            // Clear existing rows
            tbody.innerHTML = '';

            // Add new rows with animation
            teams.forEach((team, index) => {
                const row = document.createElement('tr');
                row.style.animation = 'fadeIn 0.3s ease-in';
                row.style.animationDelay = `${index * 0.05}s`;
                
                const rankBadge = team.rank ? team.rank : '-';
                const rankClass = getRankBadgeClass(team.rank);
                
                row.innerHTML = `
                    <td>
                        <div class="badge-rank ${rankClass}">${rankBadge}</div>
                    </td>
                    <td>${escapeHtml(team.team_name)}</td>
                    <td>${escapeHtml(team.school_name)}</td>
                    <td><strong>${team.total_marks}</strong></td>
                    <td>${parseFloat(team.percentage).toFixed(2)}%</td>
                    <td><span class="badge bg-success">${team.total_correct}</span></td>
                    <td><span class="badge bg-danger">${team.total_wrong}</span></td>
                `;
                
                tbody.appendChild(row);
            });

            // Show update notification
            showUpdateNotification();
        }

        function getRankBadgeClass(rank) {
            if (rank === 1) return 'rank-gold';
            if (rank === 2) return 'rank-silver';
            if (rank === 3) return 'rank-bronze';
            return '';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showUpdateNotification() {
            // Remove existing notification
            const existing = document.querySelector('.live-update-indicator');
            if (existing) existing.remove();

            // Add new notification
            const indicator = document.createElement('div');
            indicator.className = 'live-update-indicator';
            indicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Live Updated';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 10px 20px;
                border-radius: 5px;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            `;
            document.body.appendChild(indicator);

            // Remove after 2 seconds
            setTimeout(() => {
                indicator.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => indicator.remove(), 300);
            }, 2000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .badge-rank {
                display: inline-block;
                width: 35px;
                height: 35px;
                line-height: 35px;
                text-align: center;
                border-radius: 50%;
                font-weight: bold;
                background: #6c757d;
                color: white;
            }
            .rank-gold {
                background: linear-gradient(135deg, #ffd700, #ffb700);
                color: #333;
            }
            .rank-silver {
                background: linear-gradient(135deg, #c0c0c0, #a0a0a0);
                color: #333;
            }
            .rank-bronze {
                background: linear-gradient(135deg, #cd7f32, #a05a2c);
                color: white;
            }
        `;
        document.head.appendChild(style);

        // Initial load
        loadLeaderboard();

        // Start polling
        setInterval(loadLeaderboard, POLL_INTERVAL);
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
