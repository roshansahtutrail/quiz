<?php
/**
 * Quiz Results Page
 * Display quiz results by round with filtering and export options
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$admin = $auth->getCurrentAdmin();
$round_model = new RoundModel();
$result_model = new ResultModel();

// Get all rounds for filter
$rounds = $round_model->getAll();
$selected_round = $_GET['round'] ?? '';

// Get results for selected round or all rounds
$results = [];
try {
    if ($selected_round) {
        // Specific round
        $results = $result_model->getAllResults($selected_round);
    } else {
        // All rounds - get overall results for all teams
        $db = Database::getInstance();
        $db->query(
            'SELECT t.id as team_id, t.team_name, t.school_name, t.leader_name, COALESCE(or_data.rank, 0) as rank, COALESCE(or_data.total_marks, 0) as total_marks, COALESCE(or_data.percentage, 0) as percentage, COALESCE(or_data.total_correct, 0) as total_correct, COALESCE(or_data.total_wrong, 0) as total_wrong, COALESCE(or_data.total_skipped, 0) as total_skipped
            FROM teams t
            LEFT JOIN overall_results or_data ON t.id = or_data.team_id
            ORDER BY COALESCE(or_data.rank, 9999) ASC, COALESCE(or_data.total_marks, 0) DESC, t.team_name ASC'
        );
        $results = $db->resultSet();
    }
} catch (Exception $e) {
    $results = [];
}

// When a specific round is selected, prefetch earliest answer times per team
// No separate prefetch needed — start_time is included in results when a round is selected

function formatDuration($start, $end)
{
    if (empty($start) || empty($end)) return null;
    // Try parse microseconds-aware format first, fall back to seconds-only
    $s = DateTime::createFromFormat('Y-m-d H:i:s.u', $start) ?: DateTime::createFromFormat('Y-m-d H:i:s', $start);
    $e = DateTime::createFromFormat('Y-m-d H:i:s.u', $end) ?: DateTime::createFromFormat('Y-m-d H:i:s', $end);
    if (!$s || !$e) return null;
    return (float)$e->format('U.u') - (float)$s->format('U.u');
}

// Compute durations and fastest time for selected round
$durations = [];
$fastest = null;
if ($selected_round && !empty($results)) {
    foreach ($results as $r) {
        // Use time_taken_seconds from database if available, otherwise calculate manually
        if (isset($r['time_taken_seconds'])) {
            $d = (float)$r['time_taken_seconds'];
        } else {
            $start = $r['start_time'] ?? ($r['started_at'] ?? null);
            $end = $r['completed_at'] ?? null;
            $d = formatDuration($start, $end);
        }
        if (!is_null($d)) {
            $durations[$r['team_id']] = $d;
        }
    }
    if (!empty($durations)) {
        $fastest = min($durations);
    }
}

function formatGap($secondsFloat, $fastest)
{
    if (is_null($secondsFloat) || is_null($fastest)) return '-';
    $gap = $secondsFloat - $fastest;
    if ($gap < 0.001) return '0s';  // Show 0s for fastest team
    if ($gap >= 60) {
        // Format as minutes:seconds for large gaps
        $minutes = floor($gap / 60);
        $seconds = $gap % 60;
        return '+' . $minutes . 'm ' . number_format($seconds, 0) . 's';
    }
    return '+' . number_format($gap, 1) . 's';
}

function formatTimeTaken($secondsFloat)
{
    if (is_null($secondsFloat)) return 'N/A';
    
    $hours = floor($secondsFloat / 3600);
    $minutes = floor(($secondsFloat % 3600) / 60);
    $seconds = $secondsFloat % 60;
    
    if ($hours > 0) {
        return sprintf('%dh %dm %ds', $hours, $minutes, number_format($seconds, 0));
    } elseif ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, number_format($seconds, 0));
    } else {
        return sprintf('%ds', number_format($seconds, 1));
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="pabson-logo.svg">
</head>

<body data-page="results">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h2><i class="fas fa-quiz"></i> Quiz App</h2>
        </div>
        <ul class="sidebar-menu list-unstyled">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <?php if ($auth->canManageContent()): ?>
                <li><a href="teams.php"><i class="fas fa-users"></i> Teams</a></li>
                <li><a href="rounds.php"><i class="fas fa-circle-notch"></i> Rounds</a></li>
                <li><a href="questions.php"><i class="fas fa-question"></i> Questions</a></li>
            <?php endif; ?>
            <li><a href="results.php" class="active"><i class="fas fa-trophy"></i> Results</a></li>
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
        <div class="navbar-top">
            <h3><i class="fas fa-chart-bar"></i> Quiz Results</h3>
        </div>

        <!-- Filter Section -->
        <div class="card-modern">
            <div class="card-modern-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label for="round" class="form-label">Select Round:</label>
                        <select name="round" id="round" class="form-select">
                            <option value="">All Rounds</option>
                            <?php foreach ($rounds as $round): ?>
                                <option value="<?php echo $round['id']; ?>" <?php echo ($selected_round == $round['id']) ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeOutput($round['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                            <button type="button" class="btn btn-outline-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

                <!-- Results Table -->
        <div class="card-modern">
            <div class="card-modern-body">
                <table class="table table-striped" id="resultsTable">
                    <thead>
                        <tr>
                            <th>Team Name</th>
                            <?php if ($selected_round): ?>
                                <th>Round</th>
                                <th>Time (s)</th>
                                <th>Gap</th>
                                <th>Answered</th>
                                <th><span class="badge bg-success">Correct</span></th>
                                <th><span class="badge bg-danger">Wrong</span></th>
                                <th>Score</th>
                                <th>Percentage</th>
                            <?php else: ?>
                                <th>Rank</th>
                                <th>Total Correct</th>
                                <th>Total Wrong</th>
                                <th>Total Skipped</th>
                                <th>Total Score</th>
                                <th>Percentage</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="resultsBody">
                        <?php if (!empty($results)): ?>
                            <?php foreach ($results as $result): ?>
                                <tr>
                                    <td><?php echo Security::escapeOutput($result['team_name']); ?></td>
                                    <?php if ($selected_round): ?>
                                        <td><?php echo Security::escapeOutput($result['round_name'] ?? 'N/A'); ?></td>
                                            <?php $d = $durations[$result['team_id']] ?? formatDuration($result['start_time'] ?? ($result['started_at'] ?? null), $result['completed_at'] ?? null); ?>
                                        <td><?php echo formatTimeTaken($d); ?></td>
                                        <td><?php echo formatGap($d, $fastest); ?></td>
                                        <td><?php echo ($result['total_questions'] ?? 0) - ($result['skipped_answers'] ?? 0); ?></td>
                                        <td><span class="badge bg-success"><?php echo $result['correct_answers'] ?? 0; ?></span></td>
                                        <td><span class="badge bg-danger"><?php echo $result['wrong_answers'] ?? 0; ?></span></td>
                                        <td><strong><?php echo $result['total_marks'] ?? 0; ?></strong></td>
                                        <td><?php echo number_format($result['percentage'] ?? 0, 2); ?>%</td>
                                    <?php else: ?>
                                        <td><span class="badge bg-info"><?php echo $result['rank'] ?? 'N/A'; ?></span></td>
                                        <td><span class="badge bg-success"><?php echo $result['total_correct'] ?? 0; ?></span></td>
                                        <td><span class="badge bg-danger"><?php echo $result['total_wrong'] ?? 0; ?></span></td>
                                        <td><span class="badge bg-warning"><?php echo $result['total_skipped'] ?? 0; ?></span></td>
                                        <td><strong><?php echo $result['total_marks'] ?? 0; ?></strong></td>
                                        <td><?php echo number_format($result['percentage'] ?? 0, 2); ?>%</td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php $colspan = $selected_round ? 8 : 7; ?>
                            <tr>
                                <td colspan="<?php echo $colspan; ?>" class="text-center text-muted">No results found</td>
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
            // Simple table filtering and sorting
            console.log('Results page loaded');
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
