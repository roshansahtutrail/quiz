<?php
/**
 * Check Round Activation
 * Called by waiting teams to check if a round has been activated
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->isTeamLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $round_model = new RoundModel();
    $active_round = $round_model->getActiveRound();

    if ($active_round) {
        echo json_encode([
            'success' => true,
            'round_active' => true,
            'round_id' => $active_round['id'],
            'round_name' => $active_round['name']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'round_active' => false
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    Logger::log('Check round activation error: ' . $e->getMessage(), 'error');
}
?>