<?php
/**
 * Result Model
 * Version: 1.0
 */

class ResultModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Save round result
     */
    public function saveRoundResult($teamId, $roundId)
    {
        $answer = new AnswerModel();
        $result = $answer->getTeamRoundResult($teamId, $roundId);

        // Get round details
        $this->db->query('SELECT total_questions, total_marks FROM rounds WHERE id = ?', [$roundId]);
        $round = $this->db->single();

        $correctCount = $result['correct_count'] ?? 0;
        $wrongCount = $result['wrong_count'] ?? 0;
        $totalMarks = $result['total_marks'] ?? 0;
        $totalAnswered = $result['total_answered'] ?? 0;
        $totalQuestions = $round['total_questions'] ?? 0;
        // prefer explicit skipped_count from answer model when available
        $skippedCount = $result['skipped_count'] ?? ($totalQuestions - $totalAnswered);
        $percentage = $totalQuestions > 0 ? ($totalMarks / $round['total_marks']) * 100 : 0;

        // Check if result exists
        $this->db->query('SELECT id FROM round_results WHERE team_id = ? AND round_id = ?', [$teamId, $roundId]);
        $existingResult = $this->db->single();

        $resultData = [
            'team_id' => $teamId,
            'round_id' => $roundId,
            'total_marks' => $totalMarks,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctCount,
            'wrong_answers' => $wrongCount,
            'skipped_answers' => $skippedCount,
            'percentage' => $percentage,
            'status' => 'submitted',
            'completed_at' => date('Y-m-d H:i:s')
        ];

        if ($existingResult) {
            $this->db->update('round_results', $resultData, 'id = ?', [$existingResult['id']]);
        } else {
            $this->db->insert('round_results', $resultData);
        }

        // Update overall results
        $this->updateOverallResult($teamId);

        return $resultData;
    }

    /**
     * Update overall result
     */
    public function updateOverallResult($teamId)
    {
        $this->db->query(
            'SELECT 
                SUM(correct_answers) as total_correct,
                SUM(wrong_answers) as total_wrong,
                SUM(skipped_answers) as total_skipped,
                SUM(total_marks) as total_marks,
                COUNT(*) as rounds_completed
            FROM round_results 
            WHERE team_id = ? AND status = ?',
            [$teamId, 'submitted']
        );
        $result = $this->db->single();

        $totalCorrect = $result['total_correct'] ?? 0;
        $totalWrong = $result['total_wrong'] ?? 0;
        $totalSkipped = $result['total_skipped'] ?? 0;
        $totalMarks = $result['total_marks'] ?? 0;
        $roundsCompleted = $result['rounds_completed'] ?? 0;

        // Get total possible marks
        $this->db->query('SELECT SUM(total_marks) as max_marks FROM round_results WHERE team_id = ?', [$teamId]);
        $maxResult = $this->db->single();
        $maxMarks = $maxResult['max_marks'] ?? 0;
        $percentage = $maxMarks > 0 ? ($totalMarks / $maxMarks) * 100 : 0;

        $this->db->query('SELECT id FROM overall_results WHERE team_id = ?', [$teamId]);
        $existingResult = $this->db->single();

        $overallData = [
            'team_id' => $teamId,
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalWrong,
            'total_skipped' => $totalSkipped,
            'total_marks' => $totalMarks,
            'rounds_completed' => $roundsCompleted,
            'percentage' => $percentage
        ];

        if ($existingResult) {
            $this->db->update('overall_results', $overallData, 'id = ?', [$existingResult['id']]);
        } else {
            $this->db->insert('overall_results', $overallData);
        }

        // Update rankings
        $this->updateRankings();
    }

    /**
     * Update rankings
     */
    public function updateRankings()
    {
        // Get all results ordered by marks
        $this->db->query(
            'SELECT id FROM overall_results ORDER BY total_marks DESC, updated_at ASC'
        );
        $results = $this->db->resultSet();

        $rank = 1;
        foreach ($results as $result) {
            $this->db->update('overall_results', ['rank' => $rank], 'id = ?', [$result['id']]);
            $rank++;
        }
    }

    /**
     * Get round result
     */
    public function getRoundResult($teamId, $roundId)
    {
        $this->db->query(
            'SELECT * FROM round_results WHERE team_id = ? AND round_id = ?',
            [$teamId, $roundId]
        );
        return $this->db->single();
    }

    /**
     * Get overall result
     */
    public function getOverallResult($teamId)
    {
        $this->db->query(
            'SELECT * FROM overall_results WHERE team_id = ?',
            [$teamId]
        );
        return $this->db->single();
    }

    /**
     * Get team rank
     */
    public function getTeamRank($teamId)
    {
        $this->db->query(
            'SELECT rank FROM overall_results WHERE team_id = ?',
            [$teamId]
        );
        $result = $this->db->single();
        return $result['rank'] ?? 'N/A';
    }

    /**
     * Get all results for export
     */
    public function getAllResults($roundId = null)
    {
        if ($roundId) {
            $this->db->query(
                'SELECT rr.*,
                        rr.started_at as start_time,
                        TIMESTAMPDIFF(SECOND, rr.started_at, rr.completed_at) as time_taken_seconds,
                        t.team_name, t.school_name, t.leader_name, r.name as round_name
                FROM round_results rr
                JOIN teams t ON rr.team_id = t.id
                JOIN rounds r ON rr.round_id = r.id
                WHERE rr.round_id = ? AND rr.status = ?
                ORDER BY rr.total_marks DESC, time_taken_seconds ASC',
                [$roundId, 'submitted']
            );
        } else {
            $this->db->query(
                'SELECT or_data.*, t.team_name, t.school_name, t.leader_name
                FROM overall_results or_data
                JOIN teams t ON or_data.team_id = t.id
                ORDER BY or_data.rank ASC'
            );
        }
        return $this->db->resultSet();
    }
}

/**
 * Leaderboard Model
 * Version: 1.0
 */

class LeaderboardModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard($limit = null)
    {
        if ($limit !== null) {
            $this->db->query(
                'SELECT t.id as team_id, t.team_name, t.school_name, t.leader_name, COALESCE(or_data.rank, 0) as rank, COALESCE(or_data.total_marks, 0) as total_marks, COALESCE(or_data.percentage, 0) as percentage, COALESCE(or_data.total_correct, 0) as total_correct, COALESCE(or_data.total_wrong, 0) as total_wrong, COALESCE(or_data.total_skipped, 0) as total_skipped
                FROM teams t
                LEFT JOIN overall_results or_data ON t.id = or_data.team_id
                ORDER BY COALESCE(or_data.rank, 9999) ASC, COALESCE(or_data.total_marks, 0) DESC, t.team_name ASC
                LIMIT ?',
                [$limit]
            );
        } else {
            $this->db->query(
                'SELECT t.id as team_id, t.team_name, t.school_name, t.leader_name, COALESCE(or_data.rank, 0) as rank, COALESCE(or_data.total_marks, 0) as total_marks, COALESCE(or_data.percentage, 0) as percentage, COALESCE(or_data.total_correct, 0) as total_correct, COALESCE(or_data.total_wrong, 0) as total_wrong, COALESCE(or_data.total_skipped, 0) as total_skipped
                FROM teams t
                LEFT JOIN overall_results or_data ON t.id = or_data.team_id
                ORDER BY COALESCE(or_data.rank, 9999) ASC, COALESCE(or_data.total_marks, 0) DESC, t.team_name ASC'
            );
        }
        return $this->db->resultSet();
    }

    /**
     * Get top teams
     */
    public function getTopTeams($limit = null)
    {
        if ($limit !== null) {
            $this->db->query(
                'SELECT t.id as team_id, t.team_name, t.school_name, COALESCE(or_data.rank, 0) as rank, COALESCE(or_data.total_marks, 0) as total_marks, COALESCE(or_data.percentage, 0) as percentage, COALESCE(or_data.total_correct, 0) as total_correct, COALESCE(or_data.total_wrong, 0) as total_wrong
                FROM teams t
                LEFT JOIN overall_results or_data ON t.id = or_data.team_id
                ORDER BY COALESCE(or_data.total_marks, 0) DESC, t.team_name ASC
                LIMIT ?',
                [$limit]
            );
        } else {
            $this->db->query(
                'SELECT t.id as team_id, t.team_name, t.school_name, COALESCE(or_data.rank, 0) as rank, COALESCE(or_data.total_marks, 0) as total_marks, COALESCE(or_data.percentage, 0) as percentage, COALESCE(or_data.total_correct, 0) as total_correct, COALESCE(or_data.total_wrong, 0) as total_wrong
                FROM teams t
                LEFT JOIN overall_results or_data ON t.id = or_data.team_id
                ORDER BY COALESCE(or_data.total_marks, 0) DESC, t.team_name ASC'
            );
        }
        return $this->db->resultSet();
    }

    /**
     * Get team rank
     */
    public function getTeamRank($teamId)
    {
        $this->db->query(
            'SELECT rank FROM overall_results WHERE team_id = ?',
            [$teamId]
        );
        $result = $this->db->single();
        return $result['rank'] ?? null;
    }

    /**
     * Get round leaderboard
     */
    public function getRoundLeaderboard($roundId, $limit = 50)
    {
        $this->db->query(
            'SELECT rr.*, t.team_name, t.school_name, t.leader_name 
            FROM round_results rr 
            JOIN teams t ON rr.team_id = t.id 
            WHERE rr.round_id = ? 
            ORDER BY rr.total_marks DESC, rr.completed_at ASC 
            LIMIT ?',
            [$roundId, $limit]
        );
        return $this->db->resultSet();
    }

    /**
     * Get school standings
     */
    public function getSchoolStandings()
    {
        $this->db->query(
            'SELECT 
                t.school_name,
                COUNT(DISTINCT t.id) as teams_count,
                SUM(or_data.total_marks) as total_school_marks,
                AVG(or_data.percentage) as avg_percentage
            FROM overall_results or_data 
            JOIN teams t ON or_data.team_id = t.id 
            GROUP BY t.school_name 
            ORDER BY total_school_marks DESC'
        );
        return $this->db->resultSet();
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        // Total teams
        $this->db->query('SELECT COUNT(*) as count FROM teams WHERE status = ?', ['active']);
        $totalTeams = $this->db->single()['count'];

        // Total rounds
        $this->db->query('SELECT COUNT(*) as count FROM rounds');
        $totalRounds = $this->db->single()['count'];

        // Total questions
        $this->db->query('SELECT COUNT(*) as count FROM questions');
        $totalQuestions = $this->db->single()['count'];

        // Participated teams
        $this->db->query('SELECT COUNT(DISTINCT team_id) as count FROM round_results WHERE status = ?', ['submitted']);
        $participatedTeams = $this->db->single()['count'];

        return [
            'total_teams' => $totalTeams,
            'total_rounds' => $totalRounds,
            'total_questions' => $totalQuestions,
            'participated_teams' => $participatedTeams
        ];
    }
}
