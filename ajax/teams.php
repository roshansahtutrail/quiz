<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $auth = new Auth();

    // Authentication check
    if (!$auth->canManageContent()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $team_model = new TeamModel();
    $log = new ActivityLog();

    switch ($action) {
        case 'create':
            $data = [
                'school_name' => Security::sanitizeInput($_POST['school_name'] ?? ''),
                'team_name' => Security::sanitizeInput($_POST['team_name'] ?? ''),
                'leader_name' => Security::sanitizeInput($_POST['leader_name'] ?? ''),
                'username' => Security::sanitizeInput($_POST['username'] ?? ''),
                'email' => Security::sanitizeInput($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? ''
            ];

            // Validate
            if (empty($data['school_name']) || empty($data['team_name']) || empty($data['username']) || empty($data['password'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                exit;
            }

            if ($team_model->usernameExists($data['username'])) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }

            $data['password'] = Security::hashPassword($data['password']);
            $teamId = $team_model->insert('teams', $data);

            $log->log('create', 'teams', 'team', $teamId, null, $data);

            echo json_encode(['success' => true, 'message' => 'Team created successfully', 'team_id' => $teamId]);
            break;

        case 'update':
            $teamId = $_POST['id'] ?? 0;
            $data = [
                'school_name' => Security::sanitizeInput($_POST['school_name'] ?? ''),
                'team_name' => Security::sanitizeInput($_POST['team_name'] ?? ''),
                'leader_name' => Security::sanitizeInput($_POST['leader_name'] ?? ''),
                'email' => Security::sanitizeInput($_POST['email'] ?? '')
            ];

            if (!$teamId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Team ID is required']);
                exit;
            }

            $team_model->update($teamId, $data);
            $log->log('update', 'teams', 'team', $teamId, null, $data);

            echo json_encode(['success' => true, 'message' => 'Team updated successfully']);
            break;

        case 'delete':
            $teamId = $_POST['id'] ?? 0;
            $teamIds = $_POST['ids'] ?? $_POST['ids[]'] ?? null;

            if ($teamIds !== null) {
                if (!is_array($teamIds)) {
                    $teamIds = [$teamIds];
                }

                $sanitizedIds = array_filter(array_map('intval', $teamIds));
                if (!empty($sanitizedIds)) {
                    $team_model->deleteMultiple($sanitizedIds);
                    $log->log('delete', 'teams', 'bulk', null, null, ['team_ids' => $sanitizedIds]);
                    echo json_encode(['success' => true, 'message' => 'Selected teams deleted successfully']);
                    break;
                }
            }

            if (!$teamId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Team ID is required']);
                exit;
            }

            $team_model->delete($teamId);
            $log->log('delete', 'teams', 'team', $teamId);

            echo json_encode(['success' => true, 'message' => 'Team deleted successfully']);
            break;

        case 'edit':
            $teamId = $_GET['id'] ?? 0;

            if (!$teamId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Team ID is required']);
                exit;
            }

            $team = $team_model->getById($teamId);
            if (!$team) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Team not found']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $team]);
            break;

        case 'reset_password':
            $teamId = $_POST['id'] ?? 0;
            $newPassword = $_POST['password'] ?? '';

            if (!$teamId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Team ID is required']);
                exit;
            }

            if (strlen($newPassword) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit;
            }

            $team_model->changePassword($teamId, $newPassword);
            $log->log('reset_password', 'teams', 'team', $teamId);

            echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    Logger::log('Teams AJAX error: ' . $e->getMessage(), 'error');
}
?>
