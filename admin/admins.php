<?php
/**
 * Admin Management Page - Super Admin Only
 * Manage all administrator accounts
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

$auth = new Auth();
if (!$auth->checkAdminPermission('super_admin')) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

$admin = $auth->getCurrentAdmin();
$admin_model = new AdminModel();
$activity_log = new ActivityLog();

$message = '';
$error = '';

// Handle add admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $username = Security::sanitizeInput($_POST['username'] ?? '');
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $first_name = Security::sanitizeInput($_POST['first_name'] ?? '');
        $last_name = Security::sanitizeInput($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'viewer';

        if (!$username || !$email || !$first_name || !$last_name || !$password) {
            $error = 'All fields are required';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $new_admin_id = $admin_model->create($username, $email, $hashed_password, $first_name, $last_name, $role);
            $message = 'Admin created successfully!';
            $activity_log->log('create', 'admins', 'admin', $new_admin_id, null, ['username' => $username, 'email' => $email, 'role' => $role]);
        }
    } catch (Exception $e) {
        $error = 'Error creating admin: ' . $e->getMessage();
        Logger::log('Admin create error: ' . $e->getMessage(), 'error');
    }
}

// Handle edit admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    try {
        $edit_id = (int)($_POST['edit_id'] ?? 0);
        $email = Security::sanitizeInput($_POST['email'] ?? '');
        $first_name = Security::sanitizeInput($_POST['first_name'] ?? '');
        $last_name = Security::sanitizeInput($_POST['last_name'] ?? '');
        $role = $_POST['role'] ?? 'viewer';
        $status = $_POST['status'] ?? 'active';

        if (!$email || !$first_name || !$last_name) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            $admin_model->update($edit_id, [
                'email' => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'role' => $role,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $message = 'Admin updated successfully!';
            $activity_log->log('update', 'admins', 'admin', $edit_id, null, ['email' => $email, 'role' => $role, 'status' => $status]);
        }
    } catch (Exception $e) {
        $error = 'Error updating admin: ' . $e->getMessage();
        Logger::log('Admin update error: ' . $e->getMessage(), 'error');
    }
}

// Handle delete admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    try {
        $delete_id = (int)($_POST['delete_id'] ?? 0);
        
        if ($delete_id === $admin['id']) {
            $error = 'You cannot delete your own account';
        } else {
            $admin_model->delete($delete_id);
            $message = 'Admin deleted successfully!';
            $activity_log->log('delete', 'admins', 'admin', $delete_id);
        }
    } catch (Exception $e) {
        $error = 'Error deleting admin: ' . $e->getMessage();
        Logger::log('Admin delete error: ' . $e->getMessage(), 'error');
    }
}

// Get all admins
$all_admins = $admin_model->getAll(100, 0);
$edit_admin = null;
if (isset($_GET['edit'])) {
    $edit_admin = $admin_model->getById((int)$_GET['edit']);
}

$roles = ['super_admin' => 'Super Admin', 'quiz_master' => 'Quiz Master', 'result_manager' => 'Result Manager', 'viewer' => 'Viewer'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="pabson-logo.svg">
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
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: none;
        }
        .btn-add {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 58, 138, 0.3);
            color: white;
        }
        .table {
            margin: 0;
        }
        .badge-role {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .badge-super_admin {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-quiz_master {
            background: #dbeafe;
            color: #1e40af;
        }
        .badge-result_manager {
            background: #dcfce7;
            color: #166534;
        }
        .badge-viewer {
            background: #f3f4f6;
            color: #374151;
        }
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .table tbody tr {
            transition: all 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: #f3f4f6;
            box-shadow: inset 0 0 0 2px rgba(37, 99, 235, 0.1);
        }
        .btn-outline-primary:hover,
        .btn-outline-danger:hover {
            transform: translateY(-2px);
        }
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }
        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e5e7eb;
            border-radius: 5px;
            padding: 8px 12px;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 8px 12px;
            border-radius: 5px;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--secondary-color) !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e0f2fe !important;
        }
        .admin-row {
            transition: all 0.2s ease;
        }
        .admin-row:hover {
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.05), transparent) !important;
        }
        .action-buttons-group {
            display: flex;
            gap: 6px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0 !important;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: 1.5px solid;
        }
        .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-icon-edit {
            background: #dbeafe;
            color: #1e40af;
            border-color: #93c5fd;
        }
        .btn-icon-edit:hover {
            background: #bfdbfe;
            color: #1e3a8a;
            border-color: #60a5fa;
        }
        .btn-icon-delete {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }
        .btn-icon-delete:hover {
            background: #fecaca;
            color: #7f1d1d;
            border-color: #f87171;
        }
        .badge-role {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-super_admin {
            background: #dbeafe;
            color: #1e3a8a;
        }
        .badge-admin {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-editor {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-viewer {
            background: #e5e7eb;
            color: #374151;
        }
    </style>
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
            <li><a href="admins.php" class="active"><i class="fas fa-shield-alt"></i> Admin Management</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            <li><hr style="opacity: 0.2; margin: 10px 0;"></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h3 style="margin: 0; color: var(--primary-color);"><i class="fas fa-shield-alt"></i> Admin Management</h3>
                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 0.9rem;">Manage administrator accounts</p>
            </div>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="fas fa-plus"></i> Add New Admin
            </button>
        </div>

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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover" id="adminsTable">
                        <thead style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                            <tr>
                                <th><i class="fas fa-user"></i> Name</th>
                                <th><i class="fas fa-at"></i> Username</th>
                                <th><i class="fas fa-envelope"></i> Email</th>
                                <th><i class="fas fa-shield-alt"></i> Role</th>
                                <th><i class="fas fa-circle"></i> Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_admins): ?>
                                <?php foreach ($all_admins as $adm): ?>
                                    <tr class="admin-row">
                                        <td>
                                            <strong><?php echo Security::escapeOutput($adm['first_name'] . ' ' . $adm['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo Security::escapeOutput($adm['username']); ?></td>
                                        <td><?php echo Security::escapeOutput($adm['email']); ?></td>
                                        <td>
                                            <span class="badge-role badge-<?php echo $adm['role']; ?>">
                                                <?php echo $roles[$adm['role']] ?? $adm['role']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($adm['status'] === 'active'): ?>
                                                <span style="color: #10b981;"><i class="fas fa-check-circle"></i> Active</span>
                                            <?php else: ?>
                                                <span style="color: #ef4444;"><i class="fas fa-times-circle"></i> Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="action-buttons-group">
                                                <button class="btn btn-icon btn-icon-edit" 
                                                        onclick="editAdmin(<?php echo $adm['id']; ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#editAdminModal"
                                                        title="Edit Admin">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($adm['id'] !== $admin['id']): ?>
                                                    <button class="btn btn-icon btn-icon-delete" 
                                                            onclick="deleteAdmin(<?php echo $adm['id']; ?>)"
                                                            title="Delete Admin">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No admins found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" required>
                                <option value="viewer">Viewer</option>
                                <option value="result_manager">Result Manager</option>
                                <option value="quiz_master">Quiz Master</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit Admin</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" id="edit_role" required>
                                <option value="viewer">Viewer</option>
                                <option value="result_manager">Result Manager</option>
                                <option value="quiz_master">Quiz Master</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Admin Form (Hidden) -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="delete_id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        function editAdmin(adminId) {
            // Get admin data from table row
            const row = event.target.closest('tr');
            const nameCell = row.cells[0].querySelector('strong').textContent.trim();
            const email = row.cells[2].textContent.trim();
            const roleCell = row.cells[3].textContent.trim();
            const statusCell = row.cells[4].textContent.trim();
            
            // Parse name - handle names with multiple parts
            const nameParts = nameCell.split(' ');
            const firstName = nameParts[0];
            const lastName = nameParts.slice(1).join(' ') || '';
            
            // Parse role - convert to value format
            let role = 'viewer';
            if (roleCell.includes('Super Admin')) role = 'super_admin';
            else if (roleCell.includes('Quiz Master')) role = 'quiz_master';
            else if (roleCell.includes('Result Manager')) role = 'result_manager';
            
            // Parse status
            const status = statusCell.includes('Active') ? 'active' : 'inactive';

            // Populate form
            document.getElementById('edit_id').value = adminId;
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_status').value = status;
        }

        function deleteAdmin(adminId) {
            Swal.fire({
                title: 'Delete Admin?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_id').value = adminId;
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        $(document).ready(function() {
            // Initialize DataTable
            $('#adminsTable').DataTable({
                pageLength: 10,
                language: {
                    search: 'Search admins:'
                },
                order: [[0, 'asc']]
            });
            
            // Add form validation
            const addFormInModal = document.querySelector('#addAdminModal form');
            if (addFormInModal) {
                addFormInModal.addEventListener('submit', function(e) {
                    const password = this.querySelector('input[name="password"]');
                    if (password && password.value.length < 6) {
                        e.preventDefault();
                        Swal.fire('Error', 'Password must be at least 6 characters', 'error');
                    }
                });
            }
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
