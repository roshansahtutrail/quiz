<?php
/**
 * Reset Model
 * Handles resetting quiz data, points, rankings, and results
 * Version: 1.0
 */

class ResetModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Reset all quiz data
     * Clears: team_answers, round_results, overall_results
     */
    public function resetAllQuizData()
    {
        try {
            // Begin transaction for data integrity
            $this->db->query('START TRANSACTION');

            // Delete all team answers
            $this->db->query('DELETE FROM team_answers');

            // Delete all round results
            $this->db->query('DELETE FROM round_results');

            // Reset overall results (clear points but keep team records)
            $this->db->query('UPDATE overall_results SET 
                total_marks = 0,
                total_correct = 0,
                total_wrong = 0,
                total_skipped = 0,
                rounds_completed = 0,
                percentage = 0,
                rank = NULL,
                updated_at = NOW()
            ');

            // Commit transaction
            $this->db->query('COMMIT');

            return [
                'success' => true,
                'message' => 'All quiz data has been reset successfully'
            ];
        } catch (Exception $e) {
            // Rollback on error
            try {
                $this->db->query('ROLLBACK');
            } catch (Exception $rollbackError) {
                // Rollback failed
            }

            return [
                'success' => false,
                'message' => 'Error resetting quiz data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reset specific round results
     * Clears answers and results for a specific round only
     */
    public function resetRoundResults($round_id)
    {
        try {
            $this->db->query('START TRANSACTION');

            // Delete team answers for this round
            $this->db->query('DELETE FROM team_answers WHERE round_id = ?', [$round_id]);

            // Delete round results for this round
            $this->db->query('DELETE FROM round_results WHERE round_id = ?', [$round_id]);

            // Recalculate overall results after deletion
            $this->recalculateOverallResults();

            $this->db->query('COMMIT');

            return [
                'success' => true,
                'message' => 'Round results have been reset successfully'
            ];
        } catch (Exception $e) {
            try {
                $this->db->query('ROLLBACK');
            } catch (Exception $rollbackError) {
                // Rollback failed
            }

            return [
                'success' => false,
                'message' => 'Error resetting round results: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Reset specific team results
     * Clears all answers and scores for a specific team
     */
    public function resetTeamResults($team_id)
    {
        try {
            $this->db->query('START TRANSACTION');

            // Delete team answers for this team
            $this->db->query('DELETE FROM team_answers WHERE team_id = ?', [$team_id]);

            // Delete round results for this team
            $this->db->query('DELETE FROM round_results WHERE team_id = ?', [$team_id]);

            // Reset overall results for this team
            $this->db->query('UPDATE overall_results SET 
                total_marks = 0,
                total_correct = 0,
                total_wrong = 0,
                total_skipped = 0,
                rounds_completed = 0,
                percentage = 0,
                rank = NULL,
                updated_at = NOW()
            WHERE team_id = ?', [$team_id]);

            // Recalculate overall results for all teams after deletion
            $this->recalculateOverallResults();

            $this->db->query('COMMIT');

            return [
                'success' => true,
                'message' => 'Team results have been reset successfully'
            ];
        } catch (Exception $e) {
            try {
                $this->db->query('ROLLBACK');
            } catch (Exception $rollbackError) {
                // Rollback failed
            }

            return [
                'success' => false,
                'message' => 'Error resetting team results: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Recalculate overall results based on current round_results
     * Updates overall_results table with cumulative data
     */
    private function recalculateOverallResults()
    {
        $this->db->query('
            UPDATE overall_results SET
            total_marks = COALESCE((
                SELECT SUM(total_marks) FROM round_results 
                WHERE round_results.team_id = overall_results.team_id
            ), 0),
            total_correct = COALESCE((
                SELECT SUM(correct_answers) FROM round_results 
                WHERE round_results.team_id = overall_results.team_id
            ), 0),
            total_wrong = COALESCE((
                SELECT SUM(wrong_answers) FROM round_results 
                WHERE round_results.team_id = overall_results.team_id
            ), 0),
            total_skipped = COALESCE((
                SELECT SUM(skipped_answers) FROM round_results 
                WHERE round_results.team_id = overall_results.team_id
            ), 0),
            rounds_completed = (
                SELECT COUNT(*) FROM round_results 
                WHERE round_results.team_id = overall_results.team_id 
                AND round_results.status = "completed"
            ),
            percentage = CASE 
                WHEN COALESCE((
                    SELECT SUM(total_marks) FROM round_results 
                    WHERE round_results.team_id = overall_results.team_id
                ), 0) = 0 THEN 0
                ELSE ROUND((
                    COALESCE((
                        SELECT SUM(total_marks) FROM round_results 
                        WHERE round_results.team_id = overall_results.team_id
                    ), 0) * 100 / (
                        SELECT SUM(total_marks) FROM (
                            SELECT SUM(r.total_marks) as total_marks FROM round_results r
                        ) as subq
                    )
                ), 2)
            END,
            rank = NULL,
            updated_at = NOW()
        ');

        // Recalculate ranks
        $this->db->query('
            SET @rank = 0;
            UPDATE overall_results SET rank = (@rank := @rank + 1)
            ORDER BY total_marks DESC, total_correct DESC
        ');
    }

    /**
     * Get reset statistics
     * Returns information about current data before reset
     */
    public function getResetStatistics()
    {
        try {
            // Count team answers
            $this->db->query('SELECT COUNT(*) as count FROM team_answers');
            $team_answers = $this->db->single()['count'] ?? 0;

            // Count round results
            $this->db->query('SELECT COUNT(*) as count FROM round_results');
            $round_results = $this->db->single()['count'] ?? 0;

            // Count teams with scores
            $this->db->query('SELECT COUNT(*) as count FROM overall_results WHERE total_marks > 0');
            $teams_with_scores = $this->db->single()['count'] ?? 0;

            return [
                'team_answers' => $team_answers,
                'round_results' => $round_results,
                'teams_with_scores' => $teams_with_scores,
                'total_teams' => 0
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
}
