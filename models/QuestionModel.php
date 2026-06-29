<?php
/**
 * Question Model
 * Version: 1.0
 */

class QuestionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all questions for a round
     */
    public function getByRound($roundId, $limit = 50, $offset = 0)
    {
        $this->db->query(
            'SELECT * FROM questions WHERE round_id = ? ORDER BY sequence ASC LIMIT ? OFFSET ?',
            [$roundId, $limit, $offset]
        );
        $questions = $this->db->resultSet();

        // Fetch options for each question
        foreach ($questions as &$question) {
            $this->db->query(
                'SELECT * FROM question_options WHERE question_id = ? ORDER BY option_letter ASC',
                [$question['id']]
            );
            $question['options'] = $this->db->resultSet();
        }

        return $questions;
    }

    /**
     * Get question by ID with options
     */
    public function getById($id)
    {
        $this->db->query('SELECT * FROM questions WHERE id = ?', [$id]);
        $question = $this->db->single();

        if (!$question) return null;

        // Get options
        $this->db->query('SELECT * FROM question_options WHERE question_id = ? ORDER BY option_letter ASC', [$id]);
        $question['options'] = $this->db->resultSet();

        return $question;
    }

    /**
     * Get questions count for round
     */
    public function getCountByRound($roundId)
    {
        $this->db->query('SELECT COUNT(*) as count FROM questions WHERE round_id = ?', [$roundId]);
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Get total questions and total marks for a round
     */
    public function getRoundStats($roundId)
    {
        $this->db->query('SELECT COUNT(*) as total_questions, COALESCE(SUM(marks), 0) as total_marks FROM questions WHERE round_id = ?', [$roundId]);
        return $this->db->single();
    }

    /**
     * Create question
     */
    public function create($data, $options)
    {
        try {
            $this->db->beginTransaction();

            // Get next sequence
            $this->db->query('SELECT MAX(sequence) as max_seq FROM questions WHERE round_id = ?', [$data['round_id']]);
            $result = $this->db->single();
            $data['sequence'] = ($result['max_seq'] ?? 0) + 1;

            $questionId = $this->db->insert('questions', $data);

            // Insert options
            foreach ($options as $letter => $option) {
                if (!empty($option['text']) || !empty($option['image'])) {
                    $optionData = [
                        'question_id' => $questionId,
                        'option_letter' => $letter,
                        'option_text' => $option['text'] ?? '',
                        'option_image' => $option['image'] ?? '',
                        'option_type' => $option['type'] ?? 'text'
                    ];
                    $this->db->insert('question_options', $optionData);
                }
            }

            // Update round question count and marks
            $this->updateRoundStats($data['round_id']);

            $this->db->commit();
            return $questionId;
        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('Error creating question: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update question
     */
    public function update($id, $data, $options)
    {
        try {
            $this->db->beginTransaction();

            // Don't update sequence
            unset($data['sequence']);

            $this->db->update('questions', $data, 'id = ?', [$id]);

            // Delete old options
            $this->db->delete('question_options', 'question_id = ?', [$id]);

            // Insert new options
            foreach ($options as $letter => $option) {
                if (!empty($option['text']) || !empty($option['image'])) {
                    $optionData = [
                        'question_id' => $id,
                        'option_letter' => $letter,
                        'option_text' => $option['text'] ?? '',
                        'option_image' => $option['image'] ?? '',
                        'option_type' => $option['type'] ?? 'text'
                    ];
                    $this->db->insert('question_options', $optionData);
                }
            }

            // Update round stats
            $question = $this->getById($id);
            $this->updateRoundStats($question['round_id']);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('Error updating question: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete question
     */
    public function delete($id)
    {
        try {
            $this->db->beginTransaction();

            $question = $this->getById($id);

            // Delete options
            $this->db->delete('question_options', 'question_id = ?', [$id]);

            // Delete question
            $this->db->delete('questions', 'id = ?', [$id]);

            // Delete answers
            $this->db->delete('team_answers', 'question_id = ?', [$id]);

            // Update round stats
            $this->updateRoundStats($question['round_id']);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Update round statistics
     */
    private function updateRoundStats($roundId)
    {
        // Get total questions and marks
        $this->db->query('SELECT COUNT(*) as count, SUM(marks) as total_marks FROM questions WHERE round_id = ?', [$roundId]);
        $result = $this->db->single();

        $this->db->update('rounds', [
            'total_questions' => $result['count'] ?? 0,
            'total_marks' => $result['total_marks'] ?? 0
        ], 'id = ?', [$roundId]);
    }

    /**
     * Get next question in sequence
     */
    public function getNextQuestion($roundId, $currentSequence)
    {
        $this->db->query(
            'SELECT * FROM questions WHERE round_id = ? AND sequence > ? ORDER BY sequence ASC LIMIT 1',
            [$roundId, $currentSequence]
        );
        return $this->db->single();
    }

    /**
     * Get first question of round
     */
    public function getFirstQuestion($roundId)
    {
        $this->db->query(
            'SELECT * FROM questions WHERE round_id = ? ORDER BY sequence ASC LIMIT 1',
            [$roundId]
        );
        return $this->db->single();
    }

    /**
     * Duplicate question
     */
    public function duplicate($id)
    {
        $question = $this->getById($id);
        if (!$question) return false;

        $options = $question['options'];
        unset($question['id']);
        unset($question['options']);
        unset($question['created_at']);
        unset($question['updated_at']);

        // Get next sequence
        $this->db->query('SELECT MAX(sequence) as max_seq FROM questions WHERE round_id = ?', [$question['round_id']]);
        $result = $this->db->single();
        $question['sequence'] = ($result['max_seq'] ?? 0) + 1;

        $questionId = $this->db->insert('questions', $question);

        foreach ($options as $option) {
            $optionData = [
                'question_id' => $questionId,
                'option_letter' => $option['option_letter'],
                'option_text' => $option['option_text'],
                'option_image' => $option['option_image'],
                'option_type' => $option['option_type']
            ];
            $this->db->insert('question_options', $optionData);
        }

        return $questionId;
    }
}

/**
 * Answer Model
 * Version: 1.0
 */

class AnswerModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Save team answer
     */
    public function saveAnswer($teamId, $questionId, $roundId, $selectedAnswer, $skip = 0)
    {
        // Get question details
        $this->db->query('SELECT * FROM questions WHERE id = ?', [$questionId]);
        $question = $this->db->single();

        if (!$question) {
            return ['success' => false, 'message' => 'Question not found'];
        }

        // If the question was skipped by timeout, record no selected answer.
        if ($skip) {
            // mark skipped answers with NULL selected_answer so they can be distinguished
            $selectedAnswer = null;
            $isCorrect = 0;
            $marksObtained = 0;
        } else {
            $isCorrect = $selectedAnswer === $question['correct_answer'] ? 1 : 0;
            $marksObtained = $isCorrect ? $question['marks'] : 0;
        }

        // Check if answer already exists
        $this->db->query(
            'SELECT id FROM team_answers WHERE team_id = ? AND question_id = ? AND round_id = ?',
            [$teamId, $questionId, $roundId]
        );
        $existingAnswer = $this->db->single();

        $answerData = [
            'team_id' => $teamId,
            'question_id' => $questionId,
            'round_id' => $roundId,
            'selected_answer' => $selectedAnswer,
            'is_correct' => $isCorrect,
            'marks_obtained' => $marksObtained,
            'answered_at' => date('Y-m-d H:i:s')
        ];

        if ($existingAnswer) {
            // Update existing answer
            $this->db->update('team_answers', $answerData, 'id = ?', [$existingAnswer['id']]);
        } else {
            // Insert new answer
            $this->db->insert('team_answers', $answerData);
        }

        return [
            'success' => true,
            'is_correct' => $isCorrect,
            'selected_answer' => $selectedAnswer,
            'correct_answer' => $question['correct_answer'],
            'marks_obtained' => $marksObtained
        ];
    }

    /**
     * Get team answer for a question
     */
    public function getTeamAnswer($teamId, $questionId, $roundId)
    {
        $this->db->query(
            'SELECT * FROM team_answers WHERE team_id = ? AND question_id = ? AND round_id = ?',
            [$teamId, $questionId, $roundId]
        );
        return $this->db->single();
    }

    /**
     * Get all team answers for a round
     */
    public function getTeamRoundAnswers($teamId, $roundId)
    {
        $this->db->query(
            'SELECT ta.*, q.correct_answer, q.marks FROM team_answers ta 
             JOIN questions q ON ta.question_id = q.id 
             WHERE ta.team_id = ? AND ta.round_id = ? 
             ORDER BY q.sequence ASC',
            [$teamId, $roundId]
        );
        return $this->db->resultSet();
    }

    /**
     * Alias for getTeamRoundAnswers - convenience method
     */
    public function getTeamAnswers($teamId, $roundId)
    {
        return $this->getTeamRoundAnswers($teamId, $roundId);
    }

    /**
     * Get team round result
     */
    public function getTeamRoundResult($teamId, $roundId)
    {
        $this->db->query(
            'SELECT 
                SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_count,
                SUM(CASE WHEN is_correct = 0 AND selected_answer IS NOT NULL THEN 1 ELSE 0 END) as wrong_count,
                SUM(CASE WHEN selected_answer IS NULL THEN 1 ELSE 0 END) as skipped_count,
                SUM(marks_obtained) as total_marks,
                SUM(CASE WHEN selected_answer IS NOT NULL THEN 1 ELSE 0 END) as total_answered
            FROM team_answers 
            WHERE team_id = ? AND round_id = ?',
            [$teamId, $roundId]
        );

        return $this->db->single();
    }

    /**
     * Calculate and save round results
     */
    public function calculateRoundResults($teamId, $roundId)
    {
        try {
            $this->db->beginTransaction();

            // Get all questions for the round
            $this->db->query('SELECT * FROM questions WHERE round_id = ? ORDER BY sequence', [$roundId]);
            $questions = $this->db->resultSet();

            // Get all answers for this team in this round
            $this->db->query(
                'SELECT * FROM team_answers WHERE team_id = ? AND round_id = ?',
                [$teamId, $roundId]
            );
            $answers = $this->db->resultSet();
            $answerMap = [];
            foreach ($answers as $answer) {
                $answerMap[$answer['question_id']] = $answer;
            }

            // Calculate statistics
            $total_marks = 0;
            $correct_answers = 0;
            $wrong_answers = 0;
            $skipped_answers = 0;

            foreach ($questions as $question) {
                $total_marks += $question['marks'];

                if (isset($answerMap[$question['id']])) {
                    $answer = $answerMap[$question['id']];
                    // If saved answer has NULL selected_answer, treat as skipped
                    if (is_null($answer['selected_answer'])) {
                        $skipped_answers++;
                    } elseif ($answer['is_correct']) {
                        $correct_answers++;
                    } else {
                        $wrong_answers++;
                    }
                } else {
                    $skipped_answers++;
                }
            }

            // Get total marks obtained
            $this->db->query(
                'SELECT SUM(marks_obtained) as total_obtained FROM team_answers WHERE team_id = ? AND round_id = ?',
                [$teamId, $roundId]
            );
            $result = $this->db->single();
            $marks_obtained = $result['total_obtained'] ?? 0;

            // Calculate percentage
            $percentage = $total_marks > 0 ? ($marks_obtained / $total_marks) * 100 : 0;

            // Save or update round results
            $this->db->query(
                'INSERT INTO round_results (team_id, round_id, total_marks, total_questions, correct_answers, wrong_answers, skipped_answers, percentage, status, completed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE
                 total_marks = ?, total_questions = ?, correct_answers = ?, wrong_answers = ?, skipped_answers = ?, percentage = ?, status = ?, completed_at = NOW()',
                [
                    $teamId, $roundId, $marks_obtained, count($questions), $correct_answers, $wrong_answers, $skipped_answers, $percentage, 'submitted',
                    $marks_obtained, count($questions), $correct_answers, $wrong_answers, $skipped_answers, $percentage, 'submitted'
                ]
            );

            // Update overall results for the team
            $this->updateOverallResults($teamId);

            $this->db->commit();

            return [
                'success' => true,
                'total_marks' => $marks_obtained,
                'total_questions' => count($questions),
                'correct_answers' => $correct_answers,
                'wrong_answers' => $wrong_answers,
                'skipped_answers' => $skipped_answers,
                'percentage' => $percentage
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            Logger::error('Error calculating round results: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error calculating results'];
        }
    }

    /**
     * Update overall results for a team based on all completed rounds
     */
    private function updateOverallResults($teamId)
    {
        try {
            // Calculate cumulative statistics from all completed rounds
            $this->db->query(
                'SELECT 
                    SUM(total_marks) as total_marks,
                    SUM(correct_answers) as total_correct,
                    SUM(wrong_answers) as total_wrong,
                    SUM(skipped_answers) as total_skipped,
                    COUNT(*) as rounds_completed
                FROM round_results 
                WHERE team_id = ? AND status = ?',
                [$teamId, 'submitted']
            );
            $result = $this->db->single();

            $total_marks = $result['total_marks'] ?? 0;
            $total_correct = $result['total_correct'] ?? 0;
            $total_wrong = $result['total_wrong'] ?? 0;
            $total_skipped = $result['total_skipped'] ?? 0;
            $rounds_completed = $result['rounds_completed'] ?? 0;

            // Get total possible marks from all rounds
            $this->db->query(
                'SELECT SUM(total_marks) as max_marks FROM (
                    SELECT DISTINCT round_id, (SELECT SUM(marks) FROM questions WHERE round_id = round_results.round_id) as total_marks
                    FROM round_results
                    WHERE team_id = ? AND status = ?
                ) as round_totals',
                [$teamId, 'submitted']
            );
            $maxResult = $this->db->single();
            $max_marks = $maxResult['max_marks'] ?? 1;
            $percentage = $max_marks > 0 ? ($total_marks / $max_marks) * 100 : 0;

            // Check if overall result exists
            $this->db->query('SELECT id FROM overall_results WHERE team_id = ?', [$teamId]);
            $existing = $this->db->single();

            $overallData = [
                'team_id' => $teamId,
                'total_marks' => $total_marks,
                'total_correct' => $total_correct,
                'total_wrong' => $total_wrong,
                'total_skipped' => $total_skipped,
                'rounds_completed' => $rounds_completed,
                'percentage' => $percentage
            ];

            if ($existing) {
                $this->db->update('overall_results', $overallData, 'team_id = ?', [$teamId]);
            } else {
                $this->db->insert('overall_results', $overallData);
            }

            // Recalculate rankings for all teams
            $this->recalculateAllRankings();

        } catch (Exception $e) {
            Logger::error('Error updating overall results: ' . $e->getMessage());
        }
    }

    /**
     * Recalculate rankings for all teams
     */
    private function recalculateAllRankings()
    {
        try {
            // Get all teams sorted by total_marks (descending), then by id (ascending)
            $this->db->query(
                'SELECT id FROM overall_results ORDER BY total_marks DESC, id ASC'
            );
            $results = $this->db->resultSet();

            // Assign ranks
            $rank = 1;
            foreach ($results as $result) {
                $this->db->update('overall_results', ['rank' => $rank], 'id = ?', [$result['id']]);
                $rank++;
            }
        } catch (Exception $e) {
            Logger::error('Error recalculating rankings: ' . $e->getMessage());
        }
    }

    /**
     * Get round results for display
     */
    public function getRoundResults($teamId, $roundId)
    {
        $this->db->query(
            'SELECT * FROM round_results WHERE team_id = ? AND round_id = ?',
            [$teamId, $roundId]
        );
        return $this->db->single();
    }
}
