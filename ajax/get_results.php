<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

header('Content-Type: application/json');

try {
    $auth = new Auth();
    if (!$auth->checkAdminPermission()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_GET['action'] ?? '';
    $db = Database::getInstance();

    switch ($action) {
        case 'overall':
            // Get overall leaderboard for all teams
            $db->query(
                'SELECT t.id as team_id, t.team_name, t.school_name, or_data.rank as rank, COALESCE(or_data.total_marks, 0) as total_marks, COALESCE(or_data.percentage, 0) as percentage, COALESCE(or_data.total_correct, 0) as total_correct, COALESCE(or_data.total_wrong, 0) as total_wrong, COALESCE(or_data.total_skipped, 0) as total_skipped
                FROM teams t
                LEFT JOIN overall_results or_data ON t.id = or_data.team_id
                ORDER BY COALESCE(or_data.rank, 9999) ASC, COALESCE(or_data.total_marks, 0) DESC, t.team_name ASC'
            );
            $teams = $db->resultSet();

            // If rank values are missing, assign sequential rank positions for scoring teams and leave zeros for no-score teams
            $currentRank = 1;
            foreach ($teams as &$team) {
                if ($team['rank'] > 0) {
                    $team['rank'] = $currentRank++;
                } else {
                    $team['rank'] = null;
                }
            }

            echo json_encode(['success' => true, 'data' => $teams]);
            break;

        case 'roundwise':
            // Get all rounds
            $db->query('SELECT id, name FROM rounds ORDER BY sequence ASC');
            $rounds = $db->resultSet();

            $roundWiseData = [];

            foreach ($rounds as $round) {
                // Get results for this round
                $db->query(
                    'SELECT rr.*, t.team_name, t.school_name 
                    FROM round_results rr 
                    JOIN teams t ON rr.team_id = t.id 
                    WHERE rr.round_id = ? 
                    ORDER BY rr.total_marks DESC, rr.completed_at ASC',
                    [$round['id']]
                );
                $results = $db->resultSet();

                if (!empty($results)) {
                    $roundWiseData[] = [
                        'round_id' => $round['id'],
                        'round_name' => $round['name'],
                        'team_count' => count($results),
                        'results' => $results
                    ];
                }
            }

            echo json_encode(['success' => true, 'data' => $roundWiseData]);
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
