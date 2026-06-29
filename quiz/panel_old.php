<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/QuestionModel.php';
require_once MODELS_PATH . '/ResultModel.php';

$auth = new Auth();
if (!$auth->isTeamLoggedIn()) {
    Helper::redirect(APP_URL . '/quiz/login.php');
}

$team = $auth->getCurrentTeam();
$round_model = new RoundModel();
$question_model = new QuestionModel();
$answer_model = new AnswerModel();
$result_model = new ResultModel();

// Get active round
$active_round = $round_model->getActiveRound();

if (!$active_round) {
    // Waiting screen
    $showWaitingScreen = true;
} else {
    $showWaitingScreen = false;
    
    // Get current question
    $currentQuestion = $_GET['q'] ?? 1;
    $question = $question_model->getByRound($active_round['id']);
    
    if (!empty($question) && isset($question[$currentQuestion - 1])) {
        $questionData = $question[$currentQuestion - 1];
        
        // Get team's answer for this question
        $teamAnswer = $answer_model->getTeamAnswer($team['id'], $questionData['id'], $active_round['id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Panel - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 35%, #2563eb 70%, #3b82f6 100%);
            min-height: 100vh;
            color: #333;
        }
        .quiz-container {
            max-width: 900px;
            margin: 20px auto;
        }
        .quiz-header {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .quiz-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .quiz-header-left h4 {
            margin: 0;
            color: #333;
        }
        .quiz-header-left p {
            margin: 5px 0 0 0;
            color: #999;
            font-size: 0.9rem;
        }
        .quiz-timer {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            min-width: 120px;
        }
        .progress-section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .progress-bar {
            height: 25px;
            border-radius: 10px;
        }
        .progress-text {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        .question-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .question-number {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .question-text {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .question-image {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin-bottom: 20px;
            max-height: 400px;
        }
        .option-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        .option-card:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        .option-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .option-card.correct {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        .option-card.incorrect {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        .option-letter {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            line-height: 40px;
            font-weight: 700;
            margin-right: 15px;
        }
        .option-card.selected .option-letter,
        .option-card.correct .option-letter,
        .option-card.incorrect .option-letter {
            background: transparent;
            border: 2px solid;
        }
        .option-text {
            display: inline-block;
            vertical-align: middle;
            flex: 1;
        }
        .option-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        .quiz-actions {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            gap: 15px;
            justify-content: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-next {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-next:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-submit {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
        }
        .waiting-screen {
            background: white;
            border-radius: 12px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .waiting-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .team-info {
            color: #999;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="quiz-container">
        <?php if ($showWaitingScreen): ?>
            <!-- Waiting Screen -->
            <div class="waiting-screen">
                <div class="waiting-icon">
                    <i class="fas fa-hourglass-start"></i>
                </div>
                <h2>Waiting for Next Round</h2>
                <p style="color: #666; margin: 20px 0;">
                    The administrator has not activated a round yet.<br>
                    Please wait...
                </p>
                <p class="team-info">
                    Team: <strong><?php echo Security::escapeOutput($team['team_name']); ?></strong><br>
                    School: <strong><?php echo Security::escapeOutput($team['school_name']); ?></strong>
                </p>
                <div style="margin-top: 20px; color: #999; font-size: 0.85rem;">
                    Page will refresh automatically in <span id="countdown">10</span> seconds
                </div>
            </div>
        <?php else: ?>
            <!-- Quiz Header -->
            <div class="quiz-header">
                <div class="quiz-header-content">
                    <div class="quiz-header-left">
                        <h4><?php echo Security::escapeOutput($active_round['name']); ?></h4>
                        <p><?php echo Security::escapeOutput($team['team_name']); ?> - <?php echo Security::escapeOutput($team['school_name']); ?></p>
                    </div>
                    <div class="quiz-timer" id="timer">
                        <span id="timer-display">00:30</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="progress-section">
                <div style="background: #f0f0f0; border-radius: 10px; height: 25px; overflow: hidden;">
                    <div class="progress-bar" style="background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); width: <?php echo ($currentQuestion / count($question) * 100); ?>%;"></div>
                </div>
                <div class="progress-text">
                    <span>Question <?php echo $currentQuestion; ?> of <?php echo count($question); ?></span>
                    <span><?php echo round(($currentQuestion / count($question) * 100), 0); ?>% Complete</span>
                </div>
            </div>

            <!-- Question Card -->
            <div class="question-card">
                <div class="question-number">
                    <i class="fas fa-question-circle"></i> Question <?php echo $currentQuestion; ?>
                </div>
                
                <?php if ($questionData['question_text']): ?>
                    <div class="question-text">
                        <?php echo Security::escapeOutput($questionData['question_text']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($questionData['question_image']): ?>
                    <img src="<?php echo UPLOADS_URL; ?>/questions/<?php echo Security::escapeOutput($questionData['question_image']); ?>" class="question-image" alt="Question">
                <?php endif; ?>

                <!-- Options -->
                <div id="options-container">
                    <?php foreach ($questionData['options'] as $option): ?>
                        <div class="option-card" data-letter="<?php echo $option['option_letter']; ?>" onclick="selectOption('<?php echo $option['option_letter']; ?>')">
                            <span class="option-letter"><?php echo $option['option_letter']; ?></span>
                            <span class="option-text">
                                <?php if ($option['option_text']): ?>
                                    <?php echo Security::escapeOutput($option['option_text']); ?>
                                <?php elseif ($option['option_image']): ?>
                                    <img src="<?php echo UPLOADS_URL; ?>/options/<?php echo Security::escapeOutput($option['option_image']); ?>" class="option-image" alt="Option <?php echo $option['option_letter']; ?>">
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="quiz-actions">
                <?php if ($currentQuestion < count($question)): ?>
                    <button class="btn-next" onclick="nextQuestion()">
                        Next Question <i class="fas fa-arrow-right"></i>
                    </button>
                <?php else: ?>
                    <button class="btn-submit" onclick="submitRound()">
                        <i class="fas fa-check"></i> Submit Round
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const teamId = <?php echo $team['id']; ?>;
        const roundId = <?php echo $showWaitingScreen ? 0 : $active_round['id']; ?>;
        const totalQuestions = <?php echo $showWaitingScreen ? 0 : count($question); ?>;
        const currentQuestion = <?php echo $showWaitingScreen ? 0 : $currentQuestion; ?>;
        let timeLeft = <?php echo $showWaitingScreen ? 10 : $active_round['time_per_question']; ?>;

        function startTimer() {
            const timerInterval = setInterval(() => {
                timeLeft--;
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                document.getElementById('timer-display').textContent = 
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    if (currentQuestion < totalQuestions) {
                        nextQuestion();
                    } else {
                        submitRound();
                    }
                }
            }, 1000);
        }

        function selectOption(letter) {
            // Save answer via AJAX
            $.ajax({
                url: '../ajax/save_answer.php',
                type: 'POST',
                data: {
                    team_id: teamId,
                    question_id: <?php echo $showWaitingScreen ? 0 : $questionData['id']; ?>,
                    round_id: roundId,
                    selected_answer: letter
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Update UI
                        document.querySelectorAll('.option-card').forEach(card => {
                            card.classList.remove('selected', 'correct', 'incorrect');
                        });
                        const selectedCard = document.querySelector('[data-letter="' + letter + '"]');
                        selectedCard.classList.add('selected');
                    }
                }
            });
        }

        function nextQuestion() {
            window.location.href = '?q=' + (currentQuestion + 1);
        }

        function submitRound() {
            if (confirm('Are you sure you want to submit this round?')) {
                $.ajax({
                    url: '../ajax/submit_round.php',
                    type: 'POST',
                    data: {
                        team_id: teamId,
                        round_id: roundId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            window.location.href = 'summary.php?round_id=' + roundId;
                        }
                    }
                });
            }
        }

        // Start timer on page load
        if (<?php echo $showWaitingScreen ? 'true' : 'false'; ?>) {
            // Countdown
            setInterval(() => {
                document.getElementById('countdown').textContent--;
                if (parseInt(document.getElementById('countdown').textContent) <= 0) {
                    location.reload();
                }
            }, 1000);
        } else {
            startTimer();
        }
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
