<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/QuestionModel.php';

$auth = new Auth();
if (!$auth->canManageContent()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$round_model = new RoundModel();
$question_model = new QuestionModel();
$rounds = $round_model->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rounds Management -<?php echo APP_NAME; ?></title>
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
                <li><a href="rounds.php" class="active"><i class="fas fa-circle-notch"></i> Rounds</a></li>
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
            <h3>Rounds Management</h3>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoundModal">
                <i class="fas fa-plus"></i> Add Round
            </button>
        </div>

        <!-- Rounds Cards -->
        <div class="row">
            <?php foreach ($rounds as $round): 
                $round_stats = $question_model->getRoundStats($round['id']);
                $questions_count = (int)($round_stats['total_questions'] ?? 0);
                $total_points = (int)($round_stats['total_marks'] ?? 0);
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card-modern">
                        <div class="card-modern-header">
                            <h5><?php echo Security::escapeOutput($round['name']); ?></h5>
                        </div>
                        <div class="card-modern-body">
                            <div style="margin-bottom: 15px;">
                                <div style="color: #666; font-size: 0.85rem;">Sequence</div>
                                <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $round['sequence']; ?></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="color: #666; font-size: 0.85rem;">Total Questions</div>
                                <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $questions_count; ?></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="color: #666; font-size: 0.85rem;">Total Points</div>
                                <div style="font-size: 1.5rem; font-weight: 700;"><?php echo $total_points; ?></div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <div style="color: #666; font-size: 0.85rem;">Status</div>
                                <span class="badge-status <?php echo $round['status']; ?>">
                                    <?php echo ucfirst($round['status']); ?>
                                </span>
                            </div>
                            <div class="mt-3 d-flex gap-2">
                                <button class="btn btn-sm btn-info flex-grow-1 btn-edit-round" data-round-id="<?php echo $round['id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete-round" data-round-id="<?php echo $round['id']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php if ($round['status'] !== 'active'): ?>
                                <button class="btn btn-sm btn-success w-100 mt-2 btn-activate-round" data-round-id="<?php echo $round['id']; ?>">
                                    <i class="fas fa-play"></i> Activate
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-warning w-100 mt-2 btn-deactivate-round" data-round-id="<?php echo $round['id']; ?>">
                                    <i class="fas fa-pause"></i> Deactivate
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Round Modal -->
    <div class="modal fade" id="addRoundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New Round</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addRoundForm">
                        <div class="mb-3">
                            <label class="form-label">Round Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Time Per Question (seconds)</label>
                            <input type="number" class="form-control" name="time_per_question" value="30" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Round</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <script src="../assets/js/admin.js"></script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
