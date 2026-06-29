<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$admin = $auth->getCurrentAdmin();
$team_model = new TeamModel();
$round_model = new RoundModel();
$result_model = new ResultModel();
$leaderboard = new LeaderboardModel();

// Get statistics
$stats = [
    'total_teams' => $team_model->getActiveCount(),
    'total_rounds' => $round_model->getCount(),
    'active_round' => $round_model->getActiveRound(),
];

// Get leaderboard data
$top_teams = $leaderboard->getTopTeams(10);
$total_stats = $leaderboard->getStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard- <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="pabson-logo.svg">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #2563eb;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
        }
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: linear-gradient(135deg, #081a69 0%, #2268ff 100%);
            color: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            overflow-y: auto;
            z-index: 1000;
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
        .sidebar-menu li {
            margin: 0;
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
            text-align: center;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            padding-top: 30px;
        }
        .navbar-top {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1100;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .navbar-top-left h3 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        .navbar-top-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }
        .stat-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        .stat-card-title {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
        }
        .stat-card.primary .stat-card-icon {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        .stat-card.success .stat-card-icon {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .stat-card.warning .stat-card-icon {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        .stat-card.danger .stat-card-icon {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card-modern {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .card-modern-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .card-modern-header h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .card-modern-body {
            padding: 20px;
        }
        .table-modern {
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-modern thead th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #333;
            padding: 15px 12px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .table-modern tbody td {
            border: none;
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .table-modern tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-status.active {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .badge-status.inactive {
            background-color: rgba(156, 163, 175, 0.1);
            color: #6b7280;
        }
        .badge-status.locked {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        .badge-rank {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body data-page="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-quiz"></i>PABSON QUIZ APP</h2>
        </div>
        <ul class="sidebar-menu list-unstyled">
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
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
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar Top -->
        <div class="navbar-top">
            <div class="navbar-top-left">
                <h3>Dashboard</h3>
            </div>
            <div class="navbar-top-right">
                <button class="btn btn-primary" id="btnPrintResults" style="margin-right: 20px;">
                    <i class="fas fa-print"></i> Print Results
                </button>
                <div class="user-info">
                    <div class="user-avatar"><?php echo substr($admin['name'], 0, 1); ?></div>
                    <div>
                        <div style="color: #333; font-weight: 600;"><?php echo Security::escapeOutput($admin['name']); ?></div>
                        <div style="color: #999; font-size: 0.85rem;">
                            <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card primary">
                <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                <div class="stat-card-title">Total Teams</div>
                <div class="stat-card-value"><span id="totalTeamsValue"><?php echo $stats['total_teams']; ?></span></div>
            </div>

            <div class="stat-card success">
                <div class="stat-card-icon"><i class="fas fa-circle-notch"></i></div>
                <div class="stat-card-title">Total Rounds</div>
                <div class="stat-card-value"><span id="totalRoundsValue"><?php echo $stats['total_rounds']; ?></span></div>
            </div>

            <div class="stat-card warning">
                <div class="stat-card-icon"><i class="fas fa-play-circle"></i></div>
                <div class="stat-card-title">Active Round</div>
                <div class="stat-card-value"><span id="activeRoundValue"><?php echo $stats['active_round'] ? Security::escapeOutput($stats['active_round']['name']) : 'None'; ?></span></div>
            </div>

            <div class="stat-card danger">
                <div class="stat-card-icon"><i class="fas fa-question"></i></div>
                <div class="stat-card-title">Total Questions</div>
                <div class="stat-card-value"><span id="totalQuestionsValue"><?php echo $total_stats['total_questions']; ?></span></div>
            </div>
        </div>

        <!-- Top Teams Section -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-trophy"></i> Top Teams</h5>
                    </div>
                    <div class="card-modern-body">
                        <table class="table table-modern table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Team Name</th>
                                    <th>School</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="topTeamsBody">
                                <?php foreach ($top_teams as $team): ?>
                                    <tr>
                                        <td>
                                            <div class="badge-rank"><?php echo $team['rank'] ? Security::escapeOutput($team['rank']) : '-'; ?></div>
                                        </td>
                                        <td><?php echo Security::escapeOutput($team['team_name']); ?></td>
                                        <td><?php echo Security::escapeOutput($team['school_name']); ?></td>
                                        <td>
                                            <strong><?php echo $team['total_marks']; ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo round($team['percentage'], 2); ?>%</small>
                                        </td>
                                        <td>
                                            <span class="badge-status active">Active</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($top_teams)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> No teams yet
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-lg-4">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-chart-pie"></i> Quick Stats</h5>
                    </div>
                    <div class="card-modern-body">
                        <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;">
                            <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">Participated Teams</div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #667eea;">
                                <?php echo $total_stats['participated_teams']; ?>
                            </div>
                        </div>
                        <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0;">
                            <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">Total Questions</div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #10b981;">
                                <?php echo $total_stats['total_questions']; ?>
                            </div>
                        </div>
                        <div>
                            <div style="color: #666; font-size: 0.9rem; margin-bottom: 5px;">Active Rounds</div>
                            <div style="font-size: 1.8rem; font-weight: 700; color: #f59e0b;">
                                <?php echo $stats['active_round'] ? '1' : '0'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <div class="modal fade" id="printResultsModal" tabindex="-1" aria-labelledby="printResultsLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printResultsLabel">
                        <i class="fas fa-print"></i> Print Competition Results
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" id="printTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="overallTab" data-bs-toggle="tab" data-bs-target="#overallContent" type="button" role="tab" aria-controls="overallContent" aria-selected="true">
                                <i class="fas fa-trophy"></i> Overall Leaderboard
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="roundWiseTab" data-bs-toggle="tab" data-bs-target="#roundWiseContent" type="button" role="tab" aria-controls="roundWiseContent" aria-selected="false">
                                <i class="fas fa-circle-notch"></i> Round-Wise Results
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="printTabContent">
                        <!-- Overall Leaderboard Tab -->
                        <div class="tab-pane fade show active" id="overallContent" role="tabpanel" aria-labelledby="overallTab">
                            <div id="overallLeaderboard"></div>
                        </div>

                        <!-- Round-Wise Results Tab -->
                        <div class="tab-pane fade" id="roundWiseContent" role="tabpanel" aria-labelledby="roundWiseTab">
                            <div id="roundWiseResults"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnActualPrint">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                background: white;
                font-size: 12px;
                line-height: 1.5;
            }
            
            /* Hide all non-print elements */
            .sidebar, .navbar-top, .dashboard-grid, .row, 
            .modal-dialog .modal-header, .modal-dialog .modal-footer,
            .nav-tabs, .btn, button {
                display: none !important;
            }
            
            /* Show only the modal body content */
            .modal {
                display: block !important;
                position: static !important;
                width: 100% !important;
                height: 100% !important;
                background: white !important;
            }
            
            .modal-dialog {
                display: block !important;
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            
            .modal-content {
                display: block !important;
                border: none !important;
                box-shadow: none !important;
                background: white !important;
            }
            
            .modal-body {
                display: block !important;
                padding: 0 !important;
                border: none !important;
                position: static !important;
            }
            
            /* Make tab content visible */
            .tab-pane {
                display: block !important;
            }
            
            .print-header {
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #333;
                padding-bottom: 15px;
                page-break-after: avoid;
            }
            
            .print-header h2 {
                margin: 0 0 10px 0;
                font-size: 18px;
                font-weight: bold;
            }
            
            .print-header h3 {
                margin: 0 0 10px 0;
                font-size: 16px;
                font-weight: bold;
            }
            
            .print-header p {
                margin: 5px 0;
                color: #333;
                font-size: 11px;
            }
            
            .print-table {
                border-collapse: collapse;
                width: 100%;
                margin-bottom: 20px;
                page-break-inside: avoid;
            }
            
            .print-table th {
                background-color: #f0f0f0 !important;
                border: 1px solid #999;
                padding: 8px;
                text-align: left;
                font-weight: bold;
                font-size: 11px;
            }
            
            .print-table td {
                border: 1px solid #999;
                padding: 8px;
                font-size: 11px;
            }
            
            .print-table tbody tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            
            .print-content {
                page-break-after: always;
                margin-bottom: 20px;
            }
            
            .print-content:last-child {
                page-break-after: avoid;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Print Results Button Click
            const printResultsBtn = document.getElementById('btnPrintResults');
            const actualPrintBtn = document.getElementById('btnActualPrint');

            if (printResultsBtn) {
                printResultsBtn.addEventListener('click', function() {
                    const modal = new bootstrap.Modal(document.getElementById('printResultsModal'));
                    modal.show();
                    loadResultsData();
                });
            }

            if (actualPrintBtn) {
                actualPrintBtn.addEventListener('click', function() {
                    const overallContent = document.getElementById('overallLeaderboard').innerHTML;
                    const roundWiseContent = document.getElementById('roundWiseResults').innerHTML;

                    const printWindow = window.open('', '', 'height=800,width=1000');
                    if (!printWindow) {
                        alert('Unable to open print preview. Please allow pop-ups for this site.');
                        return;
                    }

                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <title>Quiz Competition Results</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    margin: 0;
                                    padding: 20px;
                                    background: white;
                                    color: #333;
                                }
                                .print-header {
                                    text-align: center;
                                    margin-bottom: 30px;
                                    border-bottom: 3px solid #333;
                                    padding-bottom: 20px;
                                    page-break-after: avoid;
                                }
                                .print-header h2 {
                                    margin: 0 0 10px 0;
                                    font-size: 24px;
                                    font-weight: bold;
                                }
                                .print-header h3 {
                                    margin: 0 0 10px 0;
                                    font-size: 18px;
                                }
                                .print-header p {
                                    margin: 5px 0;
                                    font-size: 12px;
                                    color: #666;
                                }
                                .print-content {
                                    page-break-after: always;
                                    margin-bottom: 40px;
                                }
                                .print-content:last-child {
                                    page-break-after: avoid;
                                }
                                .print-table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin-bottom: 20px;
                                    page-break-inside: avoid;
                                }
                                .print-table thead tr {
                                    background-color: #667eea;
                                    color: white;
                                }
                                .print-table th {
                                    border: 1px solid #999;
                                    padding: 12px;
                                    text-align: left;
                                    font-weight: bold;
                                    font-size: 12px;
                                }
                                .print-table td {
                                    border: 1px solid #ddd;
                                    padding: 10px;
                                    font-size: 11px;
                                }
                                .print-table tbody tr {
                                    background-color: #fff;
                                }
                                .print-table tbody tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                table.table {
                                    border: none;
                                    margin: 0;
                                    padding: 0;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="print-content">
                                ${overallContent}
                            </div>
                            ${roundWiseContent}
                        </body>
                        </html>
                    `);
                    printWindow.document.close();
                    setTimeout(() => {
                        printWindow.print();
                    }, 500);
                });
            }
        });

        // Load Results Data
        function loadResultsData() {
            document.getElementById('overallLeaderboard').innerHTML = '<p class="text-muted">Loading overall leaderboard...</p>';
            document.getElementById('roundWiseResults').innerHTML = '<p class="text-muted">Loading round-wise results...</p>';

            // Load Overall Leaderboard
            fetch('<?php echo APP_URL; ?>/ajax/get_results.php?action=overall')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOverallLeaderboard(data.data);
                    } else {
                        document.getElementById('overallLeaderboard').innerHTML = '<p class="text-muted">Failed to load overall leaderboard.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('overallLeaderboard').innerHTML = '<p class="text-muted">Failed to load overall leaderboard.</p>';
                });

            // Load Round-Wise Results
            fetch('<?php echo APP_URL; ?>/ajax/get_results.php?action=roundwise')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRoundWiseResults(data.data);
                    } else {
                        document.getElementById('roundWiseResults').innerHTML = '<p class="text-muted">Failed to load round-wise results.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('roundWiseResults').innerHTML = '<p class="text-muted">Failed to load round-wise results.</p>';
                });
        }

        // Display Overall Leaderboard
        function displayOverallLeaderboard(teams) {
            if (!Array.isArray(teams) || teams.length === 0) {
                document.getElementById('overallLeaderboard').innerHTML = '<p class="text-muted">No teams available for overall leaderboard.</p>';
                return;
            }
            let html = `
                <div class="print-header">
                    <h2>Quiz Competition - Overall Leaderboard</h2>
                    <p>Final Rankings and Scores</p>
                    <p style="font-size: 12px;">Generated on ${new Date().toLocaleString()}</p>
                </div>
                <table class="print-table table">
                    <thead>
                        <tr style="background-color: #667eea; color: white;">
                            <th style="width: 50px;">Rank</th>
                            <th>Team Name</th>
                            <th>School Name</th>
                            <th style="width: 80px;">Total Score</th>
                            <th style="width: 80px;">Percentage</th>
                            <th style="width: 80px;">Correct</th>
                            <th style="width: 80px;">Wrong</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            teams.forEach(team => {
                const rankLabel = team.rank !== null && team.rank !== undefined ? team.rank : '-';
                html += `
                    <tr>
                        <td style="text-align: center; font-weight: bold;">${rankLabel}</td>
                        <td>${team.team_name}</td>
                        <td>${team.school_name}</td>
                        <td style="text-align: center; font-weight: bold;">${team.total_marks}</td>
                        <td style="text-align: center;">${parseFloat(team.percentage).toFixed(2)}%</td>
                        <td style="text-align: center; color: green; font-weight: bold;">${team.total_correct}</td>
                        <td style="text-align: center; color: red; font-weight: bold;">${team.total_wrong}</td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            document.getElementById('overallLeaderboard').innerHTML = html;
        }

        // Display Round-Wise Results
        function displayRoundWiseResults(rounds) {
            let html = '';

            rounds.forEach(round => {
                html += `
                    <div class="print-content" style="page-break-after: always; margin-bottom: 40px;">
                        <div class="print-header">
                            <h3>${round.round_name}</h3>
                            <p>Round Results - ${round.team_count} Teams Participated</p>
                        </div>
                        <table class="print-table table">
                            <thead>
                                <tr style="background-color: #667eea; color: white;">
                                    <th style="width: 50px;">Rank</th>
                                    <th>Team Name</th>
                                    <th>School Name</th>
                                    <th style="width: 80px;">Score</th>
                                    <th style="width: 80px;">Percentage</th>
                                    <th style="width: 80px;">Correct</th>
                                    <th style="width: 80px;">Wrong</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                let rank = 1;
                round.results.forEach(result => {
                    html += `
                        <tr>
                            <td style="text-align: center; font-weight: bold;">${rank}</td>
                            <td>${result.team_name}</td>
                            <td>${result.school_name}</td>
                            <td style="text-align: center; font-weight: bold;">${result.total_marks}</td>
                            <td style="text-align: center;">${parseFloat(result.percentage).toFixed(2)}%</td>
                            <td style="text-align: center; color: green; font-weight: bold;">${result.correct_answers}</td>
                            <td style="text-align: center; color: red; font-weight: bold;">${result.wrong_answers}</td>
                        </tr>
                    `;
                    rank++;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            });

            document.getElementById('roundWiseResults').innerHTML = html;
        }

        // Print Button
        document.getElementById('btnActualPrint').addEventListener('click', function() {
            const overallContent = document.getElementById('overallLeaderboard').innerHTML;
            const roundWiseContent = document.getElementById('roundWiseResults').innerHTML;
            
            const printWindow = window.open('', '', 'height=800,width=1000');
            if (!printWindow) {
                alert('Unable to open print preview. Please allow pop-ups for this site.');
                return;
            }
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Quiz Competition Results</title>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 20px;
                            background: white;
                            color: #333;
                        }
                        .print-header {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 3px solid #333;
                            padding-bottom: 20px;
                            page-break-after: avoid;
                        }
                        .print-header h2 {
                            margin: 0 0 10px 0;
                            font-size: 24px;
                            font-weight: bold;
                        }
                        .print-header h3 {
                            margin: 0 0 10px 0;
                            font-size: 18px;
                        }
                        .print-header p {
                            margin: 5px 0;
                            font-size: 12px;
                            color: #666;
                        }
                        .print-content {
                            page-break-after: always;
                            margin-bottom: 40px;
                        }
                        .print-content:last-child {
                            page-break-after: avoid;
                        }
                        .print-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                            page-break-inside: avoid;
                        }
                        .print-table thead tr {
                            background-color: #667eea;
                            color: white;
                        }
                        .print-table th {
                            border: 1px solid #999;
                            padding: 12px;
                            text-align: left;
                            font-weight: bold;
                            font-size: 12px;
                        }
                        .print-table td {
                            border: 1px solid #ddd;
                            padding: 10px;
                            font-size: 11px;
                        }
                        .print-table tbody tr {
                            background-color: #fff;
                        }
                        .print-table tbody tr:nth-child(even) {
                            background-color: #f9f9f9;
                        }
                        table.table {
                            border: none;
                            margin: 0;
                            padding: 0;
                        }
                    </style>
                </head>
                <body>
                    <div class="print-content">
                        ${overallContent}
                    </div>
                    ${roundWiseContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            
            // Wait for content to load, then print
            setTimeout(() => {
                printWindow.print();
            }, 500);
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
