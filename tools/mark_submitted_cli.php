<?php
require_once __DIR__ . '/../includes/config.php';
require_once MODELS_PATH . '/Models.php';

$teamId = $argv[1] ?? 1;
$roundId = $argv[2] ?? 1;
$db = Database::getInstance();
// Insert or update round_results to submitted
$db->query('SELECT id FROM round_results WHERE team_id = ? AND round_id = ?', [$teamId, $roundId]);
$existing = $db->single();
if ($existing) {
    $db->query('UPDATE round_results SET status = ?, completed_at = NOW() WHERE id = ?', ['submitted', $existing['id']]);
    echo "Updated round_results for team $teamId round $roundId to submitted\n";
} else {
    $db->query('INSERT INTO round_results (team_id, round_id, started_at, completed_at, status) VALUES (?, ?, NOW(), NOW(), ?)', [$teamId, $roundId, 'submitted']);
    echo "Inserted round_results for team $teamId round $roundId as submitted\n";
}
