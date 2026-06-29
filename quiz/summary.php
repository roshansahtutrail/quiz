<?php
/**
 * Quiz Summary Page - Display round results
 * PABSON Inter School Quiz Competition
 * Version: 1.0
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/QuestionModel.php';

header('Content-Type: text/html; charset=utf-8');

$auth = new Auth();
if (!$auth->isTeamLoggedIn()) {
    Helper::redirect(APP_URL . '/quiz/login.php');
}

$team = $auth->getCurrentTeam();
$round_id = $_GET['round_id'] ?? 0;

if (!$round_id) {
    Helper::redirect(APP_URL . '/quiz/panel.php');
}

$round_model = new RoundModel();
$question_model = new QuestionModel();
$answer_model = new AnswerModel();

// Get round info
$round = $round_model->getById($round_id);
if (!$round) {
    Helper::redirect(APP_URL . '/quiz/panel.php');
}

// Get all questions
$questions = $question_model->getByRound($round_id, 100);

// Get all team answers
$teamAnswers = $answer_model->getTeamAnswers($team['id'], $round_id);
$answerMap = [];
foreach ($teamAnswers as $answer) {
    $answerMap[$answer['question_id']] = $answer;
}

// Get round results
$results = $answer_model->getRoundResults($team['id'], $round_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Round Summary</title>
    <link rel="icon" href="pabson-logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --secondary-blue: #2563eb;
            --accent-blue: #3b82f6;
            --success-green: #10b981;
            --danger-red: #ef4444;
            --warning-orange: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 35%, #2563eb 70%, #3b82f6 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 30px;
            border-bottom: 3px solid var(--primary-blue);
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .app-brand {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .summary-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .summary-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            text-align: center;
        }

        .summary-header h1 {
            color: var(--primary-blue);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .round-title {
            font-size: 1.3rem;
            color: #666;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(37,99,235,0.1) 0%, rgba(59,130,246,0.1) 100%);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--secondary-blue);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-blue);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
        }

        .score-display {
            background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 20px 0;
            box-shadow: 0 5px 20px rgba(16,185,129,0.3);
        }

        .score-value {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Courier New', monospace;
        }

        .score-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .total-points {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            font-size: 0.95rem;
            border-top: 2px solid rgba(255,255,255,0.3);
        }

        .total-points-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border-left: 5px solid var(--secondary-blue);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .total-points-label {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }

        .total-points-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--secondary-blue);
            font-family: 'Courier New', monospace;
        }

        .questions-section {
            margin-top: 30px;
        }

        .question-summary {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid var(--secondary-blue);
        }

        .question-summary.correct {
            border-left-color: var(--success-green);
        }

        .question-summary.incorrect {
            border-left-color: var(--danger-red);
        }

        .question-summary.skipped {
            border-left-color: #999;
        }

        .q-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .q-number {
            font-weight: 700;
            color: var(--secondary-blue);
            font-size: 1.1rem;
        }

        .q-score-badge {
            background: #f0f9ff;
            color: var(--secondary-blue);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .q-score-badge.incorrect {
            background: rgba(239,68,68,0.1);
            color: var(--danger-red);
        }

        .q-score-badge.skipped {
            background: #f5f5f5;
            color: #999;
        }

        .q-text {
            font-size: 1.2rem;
            color: #333;
            font-weight: 600;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .q-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .answers-display {
            display: grid;
            gap: 10px;
            margin-top: 15px;
        }

        .answer-item {
            padding: 12px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .answer-item.correct {
            background: rgba(16,185,129,0.08);
            border-color: var(--success-green);
        }

        .answer-item.incorrect {
            background: rgba(239,68,68,0.08);
            border-color: var(--danger-red);
        }

        .answer-item.skipped {
            background: #f5f5f5;
            border-color: #ddd;
        }

        .answer-label {
            font-weight: 700;
            min-width: 60px;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #666;
        }

        .answer-content {
            flex: 1;
        }

        .answer-letter {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 8px;
            font-size: 0.9rem;
        }

        .answer-item.correct .answer-letter {
            background: var(--success-green);
        }

        .answer-item.incorrect .answer-letter {
            background: var(--danger-red);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-action {
            padding: 12px 35px;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: white;
        }

        .btn-home {
            background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--accent-blue) 100%);
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(37,99,235,0.4);
            color: white;
            text-decoration: none;
        }

        .btn-print {
            background: #6366f1;
        }

        .btn-print:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(99,102,241,0.4);
            color: white;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .q-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }

            .score-value {
                font-size: 2.5rem;
            }
        }

        @media print {
            body {
                background: white;
            }
            .navbar, .action-buttons {
                display: none;
            }
            .summary-container {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="app-brand">
                <i class="fas fa-graduation-cap"></i> <?php echo APP_NAME; ?>
            </div>
            <div>
                <span><?php echo Security::escapeOutput($team['team_name']); ?> - Round Summary</span>
            </div>
        </div>
    </nav>

    <!-- Summary Content -->
    <div class="summary-container">
        <!-- Header -->
        <div class="summary-header">
            <h1><i class="fas fa-trophy"></i> Round Completed!</h1>
            <div class="round-title">
                <strong><?php echo Security::escapeOutput($round['name']); ?></strong>
            </div>

            <?php if ($results): ?>
                <div class="score-display">
                    <div class="score-value">
                        <?php echo number_format($results['total_marks']); ?> / <?php echo ($results['total_questions'] * 10); // Assuming 10 points per question by default ?>
                    </div>
                    <div class="score-label">
                        <?php echo number_format($results['percentage'], 2); ?>% • <?php echo $results['correct_answers']; ?> Correct
                    </div>
                    <div class="total-points">
                        <strong>Total Points Received:</strong> <span style="font-weight: 700; font-size: 1.2rem;"><?php echo $results['total_marks']; ?></span>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--success-green);"><?php echo $results['correct_answers']; ?></div>
                        <div class="stat-label">Correct</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: var(--danger-red);"><?php echo $results['wrong_answers']; ?></div>
                        <div class="stat-label">Wrong</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" style="color: #999;"><?php echo $results['skipped_answers']; ?></div>
                        <div class="stat-label">Skipped</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $results['total_questions']; ?></div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Questions Review -->
        <div class="questions-section">
            <h3 style="color: white; margin-bottom: 20px;">
                <i class="fas fa-list-check"></i> Question Review
            </h3>

            <?php foreach ($questions as $index => $question): ?>
                <?php
                $teamAnswer = $answerMap[$question['id']] ?? null;
                $isCorrect = $teamAnswer && $teamAnswer['is_correct'];
                $isSkipped = !$teamAnswer;
                $statusClass = $isSkipped ? 'skipped' : ($isCorrect ? 'correct' : 'incorrect');
                ?>
                <div class="question-summary <?php echo $statusClass; ?>">
                    <div class="q-header">
                        <div class="q-number">Question <?php echo ($index + 1); ?></div>
                        <div class="q-score-badge <?php echo $statusClass; ?>">
                            <?php if ($isSkipped): ?>
                                <i class="fas fa-minus-circle"></i> Not Answered
                            <?php elseif ($isCorrect): ?>
                                <i class="fas fa-check-circle"></i> Correct (+<?php echo $teamAnswer['marks_obtained']; ?>)
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> Wrong (0/<?php echo $question['marks']; ?>)
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($question['question_text'])): ?>
                        <div class="q-text">
                            <?php echo nl2br(Security::escapeOutput($question['question_text'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($question['question_image'])): ?>
                        <img src="<?php echo UPLOADS_URL; ?>/questions/<?php echo Security::escapeOutput($question['question_image']); ?>" 
                             class="q-image" alt="Question Image">
                    <?php endif; ?>

                    <!-- Answers Display -->
                    <div class="answers-display">
                        <?php foreach ($question['options'] as $option): ?>
                            <?php
                            $isTeamSelected = isset($answerMap[$question['id']]) && $answerMap[$question['id']]['selected_answer'] == $option['option_letter'];
                            $teamAnswer = $answerMap[$question['id']] ?? null;
                            $isCorrectAnswer = $option['is_correct'] ?? 0;
                            
                            // Determine display class
                            $displayClass = '';
                            if ($isTeamSelected && $isCorrectAnswer) {
                                $displayClass = 'correct';
                            } elseif ($isTeamSelected && !$isCorrectAnswer) {
                                $displayClass = 'incorrect';
                            } elseif (!$isTeamSelected && $isCorrectAnswer) {
                                $displayClass = 'correct';
                            } elseif ($isSkipped) {
                                $displayClass = 'skipped';
                            } else {
                                continue; // Don't show unselected wrong answers
                            }
                            ?>
                            <div class="answer-item <?php echo $displayClass; ?>">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="answer-letter"><?php echo $option['option_letter']; ?></span>
                                    <div class="answer-label">
                                        <?php if ($isTeamSelected): ?>
                                            YOUR ANSWER
                                        <?php elseif ($isCorrectAnswer): ?>
                                            CORRECT
                                        <?php else: ?>
                                            NOT SELECTED
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="answer-content">
                                    <?php if (!empty($option['option_text'])): ?>
                                        <div><?php echo Security::escapeOutput($option['option_text']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($option['option_image'])): ?>
                                        <img src="<?php echo UPLOADS_URL; ?>/options/<?php echo Security::escapeOutput($option['option_image']); ?>" 
                                             style="max-width: 150px; margin-top: 8px; border-radius: 5px;" alt="Option">
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="panel.php" class="btn-action btn-home">
                <i class="fas fa-home"></i> Go Home
            </a>
            <button class="btn-action btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print Results
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
