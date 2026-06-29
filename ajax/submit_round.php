<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/QuestionModel.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isTeamLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $team = $auth->getCurrentTeam();
    $team_id = $_POST['team_id'] ?? $team['id'];
    $round_id = $_POST['round_id'] ?? 0;

    if (!$team_id || !$round_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    // Calculate and save round results using AnswerModel
    $answer_model = new AnswerModel();
    $result = $answer_model->calculateRoundResults($team_id, $round_id);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Error submitting round']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    Logger::log('Submit round error: ' . $e->getMessage(), 'error');
}
?>
