<?php
require_once __DIR__ . '/includes/config.php';

try {
    $db = Database::getInstance();
    
    // First, check if teams exist
    $db->query('SELECT id FROM teams ORDER BY id LIMIT 10');
    $teams = $db->resultSet();
    
    if (empty($teams)) {
        throw new Exception('No teams found in database');
    }
    
    // Get team IDs
    $team_ids = array_column($teams, 'id');
    
    // Clear existing data for these teams
    $placeholders = implode(',', array_fill(0, count($team_ids), '?'));
    $db->query("DELETE FROM overall_results WHERE team_id IN ($placeholders)", $team_ids);
    
    // Insert sample data with actual team IDs
    $scores = [85.00, 78.00, 72.00, 65.00, 58.00];
    $rank = 1;
    
    foreach ($team_ids as $idx => $team_id) {
        $percentage = $scores[$idx % count($scores)];
        $total_marks = (int)($percentage);
        $correct = (int)($total_marks / 5);
        $wrong = (int)($total_marks / 10);
        
        $row = [
            'team_id' => $team_id,
            'total_marks' => $total_marks,
            'total_correct' => $correct,
            'total_wrong' => $wrong,
            'total_skipped' => 0,
            'rounds_completed' => 2,
            'percentage' => $percentage,
            'rank' => $rank
        ];
        
        $db->insert('overall_results', $row);
        $rank++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sample data inserted successfully',
        'data_count' => count($team_ids)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
