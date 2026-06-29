<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$team_model = new TeamModel();
$round_model = new RoundModel();
$leaderboard = new LeaderboardModel();
$db = Database::getInstance();

$stats = $leaderboard->getStatistics();
$active_round = $round_model->getActiveRound();
$top_teams = $leaderboard->getTopTeams(10);
$school_standings = $leaderboard->getSchoolStandings();

$db->query('SELECT COUNT(*) as count FROM round_results');
$total_submissions = $db->single()['count'] ?? 0;

$active_round_id = $active_round ? $active_round['id'] : null;
$participated_teams = 0;
$pending_teams = 0;
if ($active_round_id) {
    $db->query('SELECT COUNT(DISTINCT team_id) as count FROM round_results WHERE round_id = ?', [$active_round_id]);
    $participated_teams = $db->single()['count'] ?? 0;
    $db->query('SELECT COUNT(*) as count FROM teams t WHERE t.status = ? AND NOT EXISTS (SELECT 1 FROM round_results rr WHERE rr.team_id = t.id AND rr.round_id = ?)', ['active', $active_round_id]);
    $pending_teams = $db->single()['count'] ?? 0;
}
$db->query('SELECT AVG(percentage) as average_score FROM overall_results');
$average_score = round($db->single()['average_score'] ?? 0, 2);

$db->query(
    'SELECT r.name as round_name, COUNT(rr.id) as submissions
     FROM rounds r
     LEFT JOIN round_results rr ON rr.round_id = r.id
     GROUP BY r.id, r.name
     ORDER BY submissions DESC, r.name ASC'
);
$round_participation = $db->resultSet();

$db->query('SELECT percentage FROM overall_results');
$percent_rows = $db->resultSet();
$score_distribution = [
    '90%+' => 0,
    '80-89%' => 0,
    '70-79%' => 0,
    '60-69%' => 0,
    'Below 60%' => 0,
];
foreach ($percent_rows as $row) {
    $percentage = (float) $row['percentage'];
    if ($percentage >= 90) {
        $score_distribution['90%+']++;
    } elseif ($percentage >= 80) {
        $score_distribution['80-89%']++;
    } elseif ($percentage >= 70) {
        $score_distribution['70-79%']++;
    } elseif ($percentage >= 60) {
        $score_distribution['60-69%']++;
    } else {
        $score_distribution['Below 60%']++;
    }
}

$top_school_labels = array_map(fn($row) => $row['school_name'], array_slice($school_standings, 0, 6));
$top_school_values = array_map(fn($row) => round((float) $row['avg_percentage'], 2), array_slice($school_standings, 0, 6));
$round_labels = array_map(fn($row) => $row['round_name'], $round_participation);
$round_values = array_map(fn($row) => (int) $row['submissions'], $round_participation);
$score_labels = array_keys($score_distribution);
$score_values = array_values($score_distribution);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 24px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 170px;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
        }

        .stat-card-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 18px;
        }

        .stat-card-title {
            color: #475569;
            letter-spacing: 0.08em;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-card-value {
            font-size: 2.4rem;
            font-weight: 700;
            color: #111827;
        }

        .stat-card.primary .stat-card-icon {
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
        }

        .stat-card.success .stat-card-icon {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .stat-card.warning .stat-card-icon {
            background: rgba(249, 115, 22, 0.12);
            color: #f97316;
        }

        .stat-card.info .stat-card-icon {
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
        }

        .stat-card.danger .stat-card-icon {
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
        }
    </style>
    <link rel="icon" href="pabson-logo.svg">
</head>
<body data-page="analytics">
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
            <li><a href="analytics.php" class="active"><i class="fas fa-chart-bar"></i> Analytics</a></li>
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

    <div class="main-content">
        <div class="navbar-top">
            <h3><i class="fas fa-chart-bar"></i> Analytics Dashboard</h3>
        </div>

        <div class="dashboard-grid">
            <div class="stat-card primary">
                <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                <div class="stat-card-title">Total Teams</div>
                <div class="stat-card-value analytics-stat" data-key="total_teams"><?php echo $stats['total_teams']; ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-card-icon"><i class="fas fa-circle-notch"></i></div>
                <div class="stat-card-title">Total Rounds</div>
                <div class="stat-card-value analytics-stat" data-key="total_rounds"><?php echo $stats['total_rounds']; ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-card-icon"><i class="fas fa-question"></i></div>
                <div class="stat-card-title">Total Questions</div>
                <div class="stat-card-value analytics-stat" data-key="total_questions"><?php echo $stats['total_questions']; ?></div>
            </div>
            <div class="stat-card info">
                <div class="stat-card-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-card-title">Participated Teams</div>
                <div class="stat-card-value analytics-stat" data-key="participated_teams"><?php echo $stats['participated_teams']; ?></div>
            </div>
            <div class="stat-card danger">
                <div class="stat-card-icon"><i class="fas fa-user-clock"></i></div>
                <div class="stat-card-title">Pending Teams</div>
                <div class="stat-card-value analytics-stat" data-key="pending_teams"><?php echo $pending_teams; ?></div>
            </div>
            <div class="stat-card danger">
                <div class="stat-card-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-card-title">Average Score</div>
                <div class="stat-card-value analytics-stat" data-key="average_score"><?php echo $average_score; ?>%</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-chart-area"></i> Performance Overview</h5>
                    </div>
                    <div class="card-modern-body">
                        <div class="row gx-3 gy-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h6 class="mb-2">Active Round</h6>
                                    <p class="mb-0"><?php echo $active_round ? Security::escapeOutput($active_round['name']) : 'No active round'; ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <h6 class="mb-2">Team Participation</h6>
                                    <p class="mb-0"><?php echo $stats['participated_teams']; ?> teams have submitted results</p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <canvas id="scoreDistributionChart" height="220"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-school"></i> Top Schools</h5>
                    </div>
                    <div class="card-modern-body">
                        <?php if (!empty($school_standings)): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach (array_slice($school_standings, 0, 6) as $school): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?php echo Security::escapeOutput($school['school_name']); ?></span>
                                        <span class="badge bg-primary rounded-pill"><?php echo round((float) $school['avg_percentage'], 1); ?>%</span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <div>No school standings yet</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-modern mt-4">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-list-ul"></i> Round Submissions</h5>
                    </div>
                    <div class="card-modern-body">
                        <canvas id="roundParticipationChart" height="260"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-trophy"></i> Top Performing Teams</h5>
                    </div>
                    <div class="card-modern-body">
                        <table class="table table-modern table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 70px;">Rank</th>
                                    <th>Team</th>
                                    <th>School</th>
                                    <th style="width: 100px;">Score</th>
                                    <th style="width: 100px;">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($top_teams)): ?>
                                    <?php foreach ($top_teams as $team): ?>
                                        <tr>
                                            <td><div class="badge-rank"><?php echo $team['rank'] ? Security::escapeOutput($team['rank']) : '-'; ?></div></td>
                                            <td><?php echo Security::escapeOutput($team['team_name']); ?></td>
                                            <td><?php echo Security::escapeOutput($team['school_name']); ?></td>
                                            <td><strong><?php echo (int) $team['total_marks']; ?></strong></td>
                                            <td><?php echo round((float) $team['percentage'], 1); ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No team results available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="fas fa-info-circle"></i> Summary</h5>
                    </div>
                    <div class="card-modern-body">
                        <div class="mb-3">
                            <strong>Total Submissions</strong>
                            <div class="text-muted"><?php echo $total_submissions; ?> quiz responses recorded</div>
                        </div>
                        <div class="mb-3">
                            <strong>Participating Teams</strong>
                            <div class="text-muted"><?php echo $stats['participated_teams']; ?> teams</div>
                        </div>
                        <div class="mb-3">
                            <strong>Average Score</strong>
                            <div class="text-muted"><?php echo $average_score; ?>%</div>
                        </div>
                        <div class="mb-3">
                            <strong>Active Round</strong>
                            <div class="text-muted"><?php echo $active_round ? Security::escapeOutput($active_round['name']) : 'None'; ?></div>
                        </div>
                        <div class="alert alert-secondary mt-3">
                            <i class="fas fa-chart-line"></i> Data refreshes automatically when results are updated.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        const scoreDistributionCtx = document.getElementById('scoreDistributionChart');
        new Chart(scoreDistributionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($score_labels); ?>,
                datasets: [{
                    label: 'Teams by average score bucket',
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 1,
                    data: <?php echo json_encode($score_values); ?>,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });

        const roundParticipationCtx = document.getElementById('roundParticipationChart');
        new Chart(roundParticipationCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($round_labels); ?>,
                datasets: [{
                    label: 'Submitted Results',
                    backgroundColor: '#10b981',
                    borderColor: '#059669',
                    borderWidth: 1,
                    data: <?php echo json_encode($round_values); ?>,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
