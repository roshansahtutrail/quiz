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
    $question_id = $_POST['question_id'] ?? 0;
    $round_id = $_POST['round_id'] ?? 0;
    $selected_answer = $_POST['selected_answer'] ?? '';
    $skip = isset($_POST['skip']) ? intval($_POST['skip']) : 0;

    if (!$team_id || !$question_id || !$round_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    if (!$selected_answer && !$skip) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No answer or skip data provided']);
        exit;
    }

    $answer_model = new AnswerModel();
    $result = $answer_model->saveAnswer($team_id, $question_id, $round_id, $selected_answer, $skip);

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    Logger::log('Save answer error: ' . $e->getMessage(), 'error');
}
?>
