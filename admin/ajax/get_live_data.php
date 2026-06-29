<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

$auth = new Auth();
if (!$auth->checkAdminPermission()) {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$page = $_GET['page'] ?? 'dashboard';
$roundId = $_GET['round'] ?? null;

$response = [];
try {
    $team_model = new TeamModel();
    $round_model = new RoundModel();
    $leaderboard = new LeaderboardModel();
    $result_model = new ResultModel();
    $db = Database::getInstance();

    if ($page === 'dashboard') {
        $response['stats'] = [
            'total_teams' => $team_model->getActiveCount(),
            'total_rounds' => $round_model->getCount(),
            'active_round' => $round_model->getActiveRound(),
        ];
        $response['top_teams'] = $leaderboard->getTopTeams(10);
    } elseif ($page === 'leaderboard') {
        $response['leaderboard'] = $leaderboard->getLeaderboard();
    } elseif ($page === 'analytics') {
        $response['stats'] = $leaderboard->getStatistics();
        $response['active_round'] = $round_model->getActiveRound();
        $response['top_teams'] = $leaderboard->getTopTeams(10);
        $response['school_standings'] = $leaderboard->getSchoolStandings();

        $db->query('SELECT COUNT(*) as count FROM round_results');
        $response['total_submissions'] = $db->single()['count'] ?? 0;

        $active = $round_model->getActiveRound();
        if ($active) {
            $active_id = $active['id'];
            $db->query('SELECT COUNT(DISTINCT team_id) as count FROM round_results WHERE round_id = ?', [$active_id]);
            $response['participated_teams'] = $db->single()['count'] ?? 0;
            $db->query('SELECT COUNT(*) as count FROM teams t WHERE t.status = ? AND NOT EXISTS (SELECT 1 FROM round_results rr WHERE rr.team_id = t.id AND rr.round_id = ?)', ['active', $active_id]);
            $response['pending_teams'] = $db->single()['count'] ?? 0;
        }

        $db->query('SELECT r.name as round_name, COUNT(rr.id) as submissions FROM rounds r LEFT JOIN round_results rr ON rr.round_id = r.id GROUP BY r.id, r.name ORDER BY submissions DESC, r.name ASC');
        $response['round_participation'] = $db->resultSet();

        $db->query('SELECT percentage FROM overall_results');
        $percent_rows = $db->resultSet();
        $score_distribution = [ '90%+' => 0, '80-89%' => 0, '70-79%' => 0, '60-69%' => 0, 'Below 60%' => 0 ];
        foreach ($percent_rows as $row) {
            $p = (float)$row['percentage'];
            if ($p >= 90) $score_distribution['90%+']++;
            elseif ($p >= 80) $score_distribution['80-89%']++;
            elseif ($p >= 70) $score_distribution['70-79%']++;
            elseif ($p >= 60) $score_distribution['60-69%']++;
            else $score_distribution['Below 60%']++;
        }
        $response['score_distribution'] = $score_distribution;

    } elseif ($page === 'results') {
        if ($roundId) {
            $response['results'] = $result_model->getAllResults($roundId);
        } else {
            $response['results'] = $result_model->getAllResults();
        }
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

?>
