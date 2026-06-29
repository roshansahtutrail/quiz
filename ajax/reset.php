<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/ResetModel.php';

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    $auth = new Auth();

    // Only super_admin can reset
    $admin = $auth->getCurrentAdmin();
    if (!$admin || $admin['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only super admin can reset quiz data']);
        exit;
    }

    $reset_model = new ResetModel();
    $log = new ActivityLog();

    switch ($action) {
        case 'reset_all':
            $result = $reset_model->resetAllQuizData();
            if ($result['success']) {
                // Log the action
                $log->log('reset', 'quiz', 'all_data', 0, null, ['action' => 'Reset all quiz data']);
            }
            http_response_code($result['success'] ? 200 : 500);
            echo json_encode($result);
            break;

        case 'reset_round':
            $round_id = intval($_POST['round_id'] ?? 0);
            if ($round_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid round ID']);
                exit;
            }
            $result = $reset_model->resetRoundResults($round_id);
            if ($result['success']) {
                $log->log('reset', 'round', 'results', $round_id, null, ['action' => 'Reset round results', 'round_id' => $round_id]);
            }
            http_response_code($result['success'] ? 200 : 500);
            echo json_encode($result);
            break;

        case 'reset_team':
            $team_id = intval($_POST['team_id'] ?? 0);
            if ($team_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid team ID']);
                exit;
            }
            $result = $reset_model->resetTeamResults($team_id);
            if ($result['success']) {
                $log->log('reset', 'team', 'results', $team_id, $team_id, ['action' => 'Reset team results', 'team_id' => $team_id]);
            }
            http_response_code($result['success'] ? 200 : 500);
            echo json_encode($result);
            break;

        case 'get_statistics':
            $stats = $reset_model->getResetStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
