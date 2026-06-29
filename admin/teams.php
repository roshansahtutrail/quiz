<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

$auth = new Auth();
if (!$auth->canManageContent()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$team_model = new TeamModel();
$admin = $auth->getCurrentAdmin();

// Get pagination and search
$search = trim(Security::sanitizeInput($_GET['search'] ?? ''));
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

if ($search !== '') {
    $teams = $team_model->searchPaginated($search, $limit, $offset);
    $total_teams = $team_model->getSearchCount($search);
    $total_pages = max(1, ceil($total_teams / $limit));
} else {
    $teams = $team_model->getAll($limit, $offset);
    $total_teams = $team_model->getCount();
    $total_pages = max(1, ceil($total_teams / $limit));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Management - <?php echo APP_NAME; ?></title>
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
            <li><a href="teams.php" class="active"><i class="fas fa-users"></i> Teams</a></li>
            <li><a href="rounds.php"><i class="fas fa-circle-notch"></i> Rounds</a></li>
            <li><a href="questions.php"><i class="fas fa-question"></i> Questions</a></li>
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
        <div class="navbar-top d-flex align-items-center justify-content-between">
            <h3>Teams Management</h3>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" id="btnPrintTeams">
                    <i class="fas fa-print"></i> Print Teams
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                    <i class="fas fa-plus"></i> Add Team
                </button>
            </div>
        </div>

        <div id="teamAlert" class="alert d-none" role="alert"></div>

        <!-- Table -->
        <div class="card-modern mb-3">
            <div class="card-modern-body">
                <form method="GET" class="row g-3 align-items-end mb-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label">Search Teams</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo Security::escapeOutput($search); ?>" placeholder="Search by school, team, leader, or username">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary mt-4"><i class="fas fa-search"></i> Search</button>
                        <a href="teams.php" class="btn btn-outline-secondary mt-4"><i class="fas fa-redo"></i> Reset</a>
                    </div>
                </form>
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div>
                        <button class="btn btn-danger" id="deleteSelectedBtn" disabled>
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                        <span id="selectedCount" class="ms-3 text-muted">0 selected</span>
                    </div>
                </div>
                <table class="table table-modern table-hover" id="teamsTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="selectAllTeams"></th>
                            <th>#</th>
                            <th>School</th>
                            <th>Team Name</th>
                            <th>Leader</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teams as $index => $team): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input team-checkbox" value="<?php echo $team['id']; ?>">
                                </td>
                                <td><?php echo $index + (($page - 1) * $limit) + 1; ?></td>
                                <td><?php echo Security::escapeOutput($team['school_name']); ?></td>
                                <td><?php echo Security::escapeOutput($team['team_name']); ?></td>
                                <td><?php echo Security::escapeOutput($team['leader_name']); ?></td>
                                <td><?php echo Security::escapeOutput($team['username']); ?></td>
                                <td><?php echo Security::escapeOutput($team['email'] ?? ''); ?></td>
                                <td>
                                    <span class="badge-status <?php echo $team['status']; ?>">
                                        <?php echo ucfirst($team['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-edit-team" data-team-id="<?php echo $team['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btn-reset-password" data-team-id="<?php echo $team['id']; ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-delete-team" data-team-id="<?php echo $team['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="teams.php?page=<?php echo $i; ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Team Modal -->
    <div class="modal fade" id="addTeamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Add New Team</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addTeamForm">
                        <div class="mb-3">
                            <label class="form-label">School Name</label>
                            <input type="text" class="form-control" name="school_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team Name</label>
                            <input type="text" class="form-control" name="team_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Leader Name</label>
                            <input type="text" class="form-control" name="leader_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Team</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Team Modal -->
    <div class="modal fade" id="editTeamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Edit Team</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editTeamForm">
                        <input type="hidden" id="team_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">School Name</label>
                            <input type="text" class="form-control" id="edit_school_name" name="school_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Team Name</label>
                            <input type="text" class="form-control" id="edit_team_name" name="team_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Leader Name</label>
                            <input type="text" class="form-control" id="edit_leader_name" name="leader_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                        <button type="submit" class="btn btn-warning">Update Team</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Reset Team Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm">
                        <input type="hidden" id="password_team_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="password" required minlength="6" placeholder="Enter new password (minimum 6 characters)">
                            <small class="form-text text-muted d-block mt-2">Password must be at least 6 characters long.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" onclick="document.getElementById('resetPasswordForm').dispatchEvent(new Event('submit'))">Reset Password</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        document.getElementById('btnPrintTeams')?.addEventListener('click', function() {
            const rows = document.querySelectorAll('#teamsTable tbody tr');
            if (!rows.length) {
                Swal.fire('Info', 'There are no teams to print.', 'info');
                return;
            }

            let printHtml = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Teams</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
    h2 { margin-bottom: 5px; }
    p { margin-top: 0; color: #555; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; vertical-align: top; }
    th { background: #f8f9fa; }
    tbody tr:nth-child(even) { background: #f4f6f8; }
</style>
</head>
<body>
    <h1> <?php echo APP_NAME; ?></h1>
    <h3>Team List</h3>
    <p>Printed on ${new Date().toLocaleString()}</p>
    <table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>School</th>
                <th>Team Name</th>
                <th>Leader</th>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>`;

            rows.forEach((row, index) => {
                const cells = row.querySelectorAll('td');
                const serialNo = index + 1;
                const school = cells[2]?.textContent.trim() || '';
                const teamName = cells[3]?.textContent.trim() || '';
                const leader = cells[4]?.textContent.trim() || '';
                const username = cells[5]?.textContent.trim() || '';
                const email = cells[6]?.textContent.trim() || '';
                const status = cells[7]?.textContent.trim() || '';

                printHtml += `
            <tr>
                <td>${serialNo}</td>
                <td>${school}</td>
                <td>${teamName}</td>
                <td>${leader}</td>
                <td>${username}</td>
                <td>${email}</td>
                <td>${status}</td>
            </tr>`;
            });

            printHtml += `
        </tbody>
    </table>
</body>
</html>`;

            const printWindow = window.open('', '', 'height=800,width=1000');
            if (!printWindow) {
                alert('Unable to open print preview. Please allow pop-ups for this site.');
                return;
            }

            printWindow.document.write(printHtml);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
