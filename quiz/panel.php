<?php
/**
 * Quiz Panel - Enhanced Version
 * PABSON Inter School Quiz Competition
 * Version: 2.0
 * 
 * Features:
 * - Professional navbar with team info
 * - Admin-configured timers
 * - Image and text questions/options
 * - Restricted navigation (no going back)
 * - Auto-advance on timer end
 * - Points per question
 * - Detailed summary after submission
 */

require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/QuestionModel.php';
require_once MODELS_PATH . '/ResultModel.php';

header('Content-Type: text/html; charset=utf-8');

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

// Defaults
$submittedRoundId = 0;
$showWaitingScreen = false;
$totalQuestions = 0;
$currentQuestion = 0;

if (!$active_round) {
    // No active round
    $showWaitingScreen = true;
} else {
    // Check if this team already submitted this active round
    $db = Database::getInstance();
    $db->query('SELECT status FROM round_results WHERE team_id = ? AND round_id = ?', [$team['id'], $active_round['id']]);
    $rr = $db->single();
    if ($rr && isset($rr['status']) && $rr['status'] === 'submitted') {
        $showWaitingScreen = true;
        $submittedRoundId = (int)$active_round['id'];
    } else {
        // Not submitted yet — initialize round and questions
        // Set started_at if this is the first time accessing the round
        $db->query(
            'SELECT id, started_at FROM round_results WHERE team_id = ? AND round_id = ?',
            [$team['id'], $active_round['id']]
        );
        $roundResult = $db->single();

        if (!$roundResult) {
            // Create new round result with started_at
            $db->query(
                'INSERT INTO round_results (team_id, round_id, started_at, status) VALUES (?, ?, NOW(), ?)',
                [$team['id'], $active_round['id'], 'pending']
            );
        } elseif (!$roundResult['started_at']) {
            // Update started_at if it's null
            $db->query(
                'UPDATE round_results SET started_at = NOW() WHERE id = ?',
                [$roundResult['id']]
            );
        }

        // Get current question
        $currentQuestion = max(1, (int)($_GET['q'] ?? 1));
        $questions = $question_model->getByRound($active_round['id']);
        $totalQuestions = count($questions);
        
        // Prevent going back (check if already answered previous questions)
        if ($currentQuestion > 1) {
            // Verify user progression
            $previousQ = $questions[$currentQuestion - 2];
            $prevAnswer = $answer_model->getTeamAnswer($team['id'], $previousQ['id'], $active_round['id']);
            if (!$prevAnswer) {
                $currentQuestion = 1;
            }
        }
        
        if (!empty($questions) && isset($questions[$currentQuestion - 1])) {
            $questionData = $questions[$currentQuestion - 1];
            
            // Get team's answer for this question (if exists)
            $teamAnswer = $answer_model->getTeamAnswer($team['id'], $questionData['id'], $active_round['id']);
        }
    }
}

    // If team already submitted and someone tried to access a question directly, strip query and reload
    if (!empty($submittedRoundId) && isset($_GET['q'])) {
        header('Location: panel.php');
        exit;
    }

// Prevent access before login
if (!isset($_SESSION['team_id'])) {
    Helper::redirect(APP_URL . '/quiz/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Quiz Panel</title>
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

        /* Navbar Styles */
        .quiz-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 30px;
            border-bottom: 3px solid var(--primary-blue);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
        }

        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .app-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .app-brand i {
            font-size: 1.5rem;
        }

        .team-info-nav {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .team-name {
            text-align: right;
        }

        .team-name .label {
            font-size: 0.75rem;
            color: #999;
            text-transform: uppercase;
        }

        .team-name .name {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }

        .logout-btn {
            background: var(--danger-red);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Main Container */
        .quiz-main {
            max-width: 1000px;
            margin: 110px auto 30px; /* account for fixed navbar height */
            padding: 0 20px;
        }

        /* Title Section */
        .quiz-title {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .quiz-title h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .quiz-title .round-name {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Header Card */
        .quiz-header {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info {
            display: flex;
            gap: 40px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.75rem;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
        }

        .timer-display {
            background: linear-gradient(135deg, var(--danger-red) 0%, #dc2626 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px;
            text-align: center;
            min-width: 120px;
            box-shadow: 0 4px 15px rgba(239,68,68,0.3);
        }

        .timer-display .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            opacity: 0.9;
            display: block;
            margin-bottom: 5px;
        }

        .timer-display .time {
            font-size: 2rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }

        .timer-display.warning .time {
            animation: pulse-warning 0.6s infinite;
        }

        @keyframes pulse-warning {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Progress Bar */
        .progress-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #666;
        }

        .progress-bar-container {
            background: #f0f0f0;
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            background: linear-gradient(90deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        /* Question Card */
        .question-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .question-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            color: var(--secondary-blue);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .question-header i {
            font-size: 1.2rem;
        }

        .question-number {
            color: var(--secondary-blue);
            font-size: 1rem;
            font-weight: 700;
        }

        .question-text {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .question-image {
            max-width: 100%;
            max-height: 350px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .question-points {
            background: #f0f9ff;
            color: var(--secondary-blue);
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            border-left: 3px solid var(--secondary-blue);
        }

        /* Options Container - Dynamic based on content */
        .options-container {
            display: grid;
            gap: 12px;
        }

        /* 4-Sector Grid for Image Options */
        .options-container.image-grid {
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .option-button {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        /* Image-based option styling */
        .option-button.image-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px 15px;
            min-height: 200px;
        }

        .option-button.image-option .option-letter {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
            font-size: 0.9rem;
        }

        .option-button.image-option .option-content {
            width: 100%;
            text-align: center;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .option-button.image-option .option-image {
            max-width: 100%;
            max-height: 120px;
            width: auto;
            height: auto;
            border-radius: 8px;
        }

        .option-button.image-option .option-text {
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .option-button:hover:not(:disabled) {
            border-color: var(--secondary-blue);
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        .option-button:disabled {
            cursor: not-allowed;
            opacity: 0.7;
        }

        .option-button.answered {
            pointer-events: none;
        }

        .option-button.correct {
            border-color: var(--success-green);
            background: rgba(16,185,129,0.08);
            box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
        }

        .option-button.incorrect {
            border-color: var(--danger-red);
            background: rgba(239,68,68,0.08);
            box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
        }

        .option-button.selected:not(.answered) {
            border-color: var(--secondary-blue);
            background: rgba(37,99,235,0.08);
            box-shadow: 0 2px 8px rgba(37,99,235,0.2), 0 0 0 3px rgba(37,99,235,0.1);
        }

        .option-letter {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }

        .option-button.correct .option-letter {
            background: var(--success-green);
        }

        .option-button.incorrect .option-letter {
            background: var(--danger-red);
        }

        .option-button.selected:not(.answered) .option-letter {
            background: var(--secondary-blue);
        }

        .option-content {
            flex: 1;
        }

        .option-text {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
            word-break: break-word;
        }

        .option-image {
            max-width: 100%;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
        }

        .option-feedback {
            font-size: 0.85rem;
            margin-top: 8px;
            font-weight: 600;
        }

        .option-button.correct .option-feedback {
            color: var(--success-green);
        }

        .option-button.incorrect .option-feedback {
            color: var(--danger-red);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
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
        }

        .btn-next {
            background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--accent-blue) 100%);
            color: white;
        }

        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(37,99,235,0.4);
            color: white;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
            color: white;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(16,185,129,0.4);
            color: white;
        }

        .btn-action:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Waiting Screen */
        .waiting-container {
            text-align: center;
            color: white;
            margin-top: 100px;
        }

        .waiting-icon {
            font-size: 5rem;
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .waiting-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .waiting-message {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .team-details {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            backdrop-filter: blur(10px);
        }

        .countdown {
            font-size: 0.95rem;
            margin-top: 20px;
            opacity: 0.8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 15px;
            }

            .quiz-title h1 {
                font-size: 1.5rem;
            }

            .quiz-header {
                flex-direction: column;
                gap: 20px;
            }

            .header-info {
                flex-direction: column;
                gap: 15px;
                width: 100%;
            }

            .timer-display {
                width: 100%;
            }

            .question-card {
                padding: 20px;
            }

            .question-text {
                font-size: 1.2rem;
            }

            .option-button {
                gap: 12px;
                padding: 12px;
            }

            .option-button.image-option {
                min-height: 150px;
                padding: 15px 10px;
            }

            .option-button.image-option .option-image {
                max-height: 80px;
            }

            .option-letter {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .option-button.image-option .option-letter {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
                top: 5px;
                right: 5px;
            }

            .options-container.image-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="quiz-navbar">
        <div class="navbar-content">
            <div class="app-brand">
                <i class="fas fa-graduation-cap"></i>
                <span><?php echo APP_NAME; ?></span>
            </div>
            <div class="team-info-nav">
                <div class="team-name">
                    <div class="label">Team</div>
                    <div class="name"><?php echo Security::escapeOutput($team['team_name']); ?></div>
                </div>
                <form method="POST" action="logout.php" style="margin: 0;">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="quiz-main">
        <?php if ($showWaitingScreen): ?>
            <!-- Waiting Screen -->
            <div class="waiting-container">
                <div class="waiting-icon">
                    <i class="fas fa-hourglass-start"></i>
                </div>
                <div class="waiting-title">Waiting for Next Round</div>
                <div class="waiting-message">
                    <?php if (!empty($submittedRoundId)): ?>
                        You have completed this round.<br>
                        Please wait for the administrator to activate the next round...
                    <?php else: ?>
                        The quiz will start soon.<br>
                        Please wait for the administrator to activate the round...
                    <?php endif; ?>
                </div>

                <div class="team-details">
                    <div style="margin-bottom: 10px;">
                        <strong><?php echo Security::escapeOutput($team['team_name']); ?></strong>
                    </div>
                    <div class="countdown">
                        Page will refresh automatically in <span id="countdown">10</span> seconds
                    </div>
                    <?php if (!empty($submittedRoundId)): ?>
                        <div style="margin-top:15px;">
                            <a href="summary.php?round_id=<?php echo $submittedRoundId; ?>" class="btn-action btn-submit" style="display:inline-block;padding:10px 18px;">View Summary</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                let countdownValue = 10;
                setInterval(() => {
                    countdownValue--;
                    document.getElementById('countdown').textContent = countdownValue;
                    if (countdownValue <= 0) {
                        location.reload();
                    }
                }, 1000);

                // Check for round activation every 3 seconds
                const submittedRoundId = <?php echo $submittedRoundId; ?>;
                setInterval(() => {
                    $.ajax({
                        url: '../ajax/check_round_activation.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.success && response.round_active) {
                                // Ignore activation for round the team already submitted
                                if (submittedRoundId && response.round_id == submittedRoundId) return;
                                // Round activated! Show modal and refresh
                                showRoundActivatedModal(response.round_name);
                                setTimeout(() => {
                                    location.reload();
                                }, 2000);
                            }
                        },
                        error: function() {
                            // Silently fail, retry next time
                        }
                    });
                }, 3000);

                function showRoundActivatedModal(roundName) {
                    Swal.fire({
                        title: 'Round Activated!',
                        html: '<div style="font-size: 1.1rem;"><i class="fas fa-check-circle" style="color: #10b981; font-size: 2rem; margin-bottom: 10px;"></i><br><br>' +
                              'The round <strong>' + roundName + '</strong> has been activated.<br><br>' +
                              'Starting quiz now...</div>',
                        icon: 'success',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        timer: 2000
                    });
                }
            </script>

        <?php else: ?>
            <!-- Quiz Title -->
            <div class="quiz-title">
                <h1><?php echo APP_NAME; ?></h1>
                <div class="round-name">
                    <i class="fas fa-circle-notch"></i> Round: <?php echo Security::escapeOutput($active_round['name']); ?>
                </div>
            </div>

            <!-- Header -->
            <div class="quiz-header">
                <div class="header-info">
                    <div class="info-item">
                        <span class="info-label">Question</span>
                        <span class="info-value"><?php echo $currentQuestion; ?> / <?php echo $totalQuestions; ?></span>
                    </div>
                </div>
                <div class="timer-display <?php echo ($active_round['time_per_question'] <= 30) ? 'warning' : ''; ?>">
                    <span class="label">Time Left</span>
                    <span class="time" id="timer">00:00</span>
                </div>
            </div>

            <!-- Progress -->
            <div class="progress-card">
                <div class="progress-header">
                    <span>Progress</span>
                    <span id="progress-percent">0%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progress-bar" style="width: 0%;"></div>
                </div>
            </div>

            <!-- Question -->
            <div class="question-card">
                <div class="question-header">
                    <i class="fas fa-question-circle"></i>
                    <span class="question-number">Question <?php echo $currentQuestion; ?></span>
                </div>

                <?php if (isset($questionData)): ?>
                    <?php if (!empty($questionData['question_text'])): ?>
                        <div class="question-text">
                            <?php echo nl2br(Security::escapeOutput($questionData['question_text'])); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($questionData['question_image'])): ?>
                        <img src="<?php echo APP_URL; ?>/<?php echo Security::escapeOutput($questionData['question_image']); ?>" class="question-image" alt="Question Image" style="max-width: 100%; height: auto; border-radius: 8px; margin: 20px 0;">
                    <?php endif; ?>

                    <?php if (!empty($questionData['marks'])): ?>
                        <div class="question-points">
                            <i class="fas fa-star"></i> <?php echo $questionData['marks']; ?> point<?php echo $questionData['marks'] != 1 ? 's' : ''; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Options -->
                    <div class="options-container" id="options-container">
                        <?php 
                        if (isset($questionData) && isset($questionData['options']) && is_array($questionData['options'])):
                            // Check if any option has an image
                            $hasImages = false;
                            foreach ($questionData['options'] as $option) {
                                if (!empty($option['option_image'])) {
                                    $hasImages = true;
                                    break;
                                }
                            }
                            
                            // Add image-grid class if options have images
                            if ($hasImages) {
                                echo '<script>setTimeout(() => {
                                    let container = document.getElementById("options-container");
                                    if (container) container.classList.add("image-grid");
                                }, 0);</script>';
                            }
                            
                            foreach ($questionData['options'] as $option): 
                                $isImageOption = !empty($option['option_image']);
                        ?>
                            <button class="option-button<?php echo $isImageOption ? ' image-option' : ''; ?>" 
                                    data-letter="<?php echo $option['option_letter']; ?>" 
                                    onclick="selectOption('<?php echo $option['option_letter']; ?>', this)">
                                <span class="option-letter"><?php echo $option['option_letter']; ?></span>
                                <div class="option-content">
                                    <?php if (!empty($option['option_image'])): ?>
                                        <img src="<?php echo APP_URL; ?>/<?php echo Security::escapeOutput($option['option_image']); ?>" 
                                             class="option-image" 
                                             alt="Option <?php echo $option['option_letter']; ?>"
                                             style="max-width: 120px; max-height: 120px;">
                                    <?php endif; ?>
                                    <?php if (!empty($option['option_text'])): ?>
                                        <div class="option-text"><?php echo nl2br(Security::escapeOutput($option['option_text'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </button>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($currentQuestion < $totalQuestions): ?>
                    <button class="btn-action btn-next" id="btn-next" onclick="nextQuestion()" disabled>
                        <i class="fas fa-arrow-right"></i> Next Question
                    </button>
                <?php else: ?>
                    <button class="btn-action btn-submit" id="btn-submit" onclick="submitRound()" disabled>
                        <i class="fas fa-check"></i> Submit Round
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        const teamId = <?php echo $team['id']; ?>;
        const roundId = <?php echo $showWaitingScreen ? 0 : $active_round['id']; ?>;
        const totalQuestions = <?php echo $totalQuestions; ?>;
        const currentQuestion = <?php echo $currentQuestion; ?>;
        const timePerQuestion = <?php if ($showWaitingScreen) { echo 0; } else { echo isset($questionData['time_limit']) && $questionData['time_limit'] > 0 ? (int)$questionData['time_limit'] : (int)$active_round['time_per_question']; } ?>;
        const currentQuestionData = <?php echo isset($questionData) ? json_encode($questionData) : 'null'; ?>;
        let timeLeft = timePerQuestion;
        let answerSelected = <?php echo !empty($teamAnswer) ? 'true' : 'false'; ?>;

        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        }

        function updateProgress() {
            const percent = Math.round((currentQuestion / totalQuestions) * 100);
            document.getElementById('progress-percent').textContent = percent + '%';
            document.getElementById('progress-bar').style.width = percent + '%';
        }

        function startTimer() {
            document.getElementById('timer').textContent = formatTime(timeLeft);

            const timerInterval = setInterval(() => {
                timeLeft--;
                document.getElementById('timer').textContent = formatTime(timeLeft);

                // Add warning animation when time is low
                if (timeLeft <= 10) {
                    document.querySelector('.timer-display').classList.add('warning');
                }

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    autoAdvance();
                }
            }, 1000);
        }

        function disableOptionsAfterTimeout() {
            document.querySelectorAll('.option-button').forEach(btn => {
                btn.disabled = true;
                btn.classList.add('answered');
            });
        }

        function saveSkippedAnswer() {
            return $.ajax({
                url: '../ajax/save_answer.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    team_id: teamId,
                    question_id: currentQuestionData.id,
                    round_id: roundId,
                    skip: 1
                }
            });
        }

        function selectOption(letter, element) {
            if (answerSelected) return;

            // Remove previous selection
            document.querySelectorAll('.option-button').forEach(btn => {
                btn.classList.remove('selected');
            });

            // Mark as selected
            element.classList.add('selected');

            // Save answer via AJAX
            $.ajax({
                url: '../ajax/save_answer.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    team_id: teamId,
                    question_id: currentQuestionData.id,
                    round_id: roundId,
                    selected_answer: letter
                },
                success: function(response) {
                    if (response.success) {
                        answerSelected = true;
                        
                        if (response.is_correct) {
                            // Correct answer - show green highlight
                            element.classList.remove('selected');
                            element.classList.add('correct', 'answered');
                            element.innerHTML += '<span class="option-feedback"><i class="fas fa-check-circle"></i> Correct! +' + response.marks_obtained + ' points</span>';
                        } else {
                            // Wrong answer - show red highlight for selected, and green for correct
                            element.classList.remove('selected');
                            element.classList.add('incorrect', 'answered');
                            element.innerHTML += '<span class="option-feedback"><i class="fas fa-times-circle"></i> Incorrect</span>';
                            
                            // Show correct answer in green
                            document.querySelectorAll('.option-button').forEach(btn => {
                                if (btn.getAttribute('data-letter') === response.correct_answer) {
                                    btn.classList.add('correct', 'answered');
                                    btn.innerHTML += '<span class="option-feedback"><i class="fas fa-check-circle"></i> Correct Answer</span>';
                                }
                            });
                        }

                        // Disable all options
                        document.querySelectorAll('.option-button').forEach(btn => {
                            btn.disabled = true;
                        });

                        // Enable next/submit button
                        if (currentQuestion < totalQuestions) {
                            document.getElementById('btn-next').disabled = false;
                        } else {
                            document.getElementById('btn-submit').disabled = false;
                        }
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to save answer. Please try again.', 'error');
                }
            });
        }

        function autoAdvance() {
            const message = currentQuestion < totalQuestions ? 'Time is up! Question skipped automatically.' : 'Time is up! Submitting the round now.';
            Swal.fire({
                icon: 'info',
                title: 'Time is up',
                text: message,
                timer: 1200,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });

            disableOptionsAfterTimeout();
            answerSelected = true;

            if (currentQuestion < totalQuestions) {
                saveSkippedAnswer().always(() => {
                    nextQuestion(true);
                });
            } else {
                saveSkippedAnswer().always(() => {
                    submitRound(true);
                });
            }
        }

        function nextQuestion(forceSkip = false) {
            if (!answerSelected && !forceSkip) {
                Swal.fire('Please Select', 'You must select an option before proceeding.', 'warning');
                return;
            }
            window.location.href = '?q=' + (currentQuestion + 1);
        }

        function submitRound(forceSubmit = false) {
            if (!answerSelected && !forceSubmit) {
                Swal.fire('Please Select', 'You must select an option before submitting.', 'warning');
                return;
            }

            function sendSubmitRequest() {
                $.ajax({
                    url: '../ajax/submit_round.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        team_id: teamId,
                        round_id: roundId
                    },
                    success: function(data) {
                        if (data.success) {
                            window.location.href = 'summary.php?round_id=' + roundId;
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Failed to submit round. Please try again.', 'error');
                    }
                });
            }

            if (forceSubmit) {
                sendSubmitRequest();
                return;
            }

            Swal.fire({
                title: 'Submit Round?',
                html: 'You have completed all questions.<br>Are you sure you want to submit your answers?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#10b981'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../ajax/submit_round.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            team_id: teamId,
                            round_id: roundId
                        },
                        success: function(data) {
                            if (data.success) {
                                window.location.href = 'summary.php?round_id=' + roundId;
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to submit round. Please try again.', 'error');
                        }
                    });
                }
            });
        }

        // Initialize on load
        $(document).ready(function() {
            <?php if (!$showWaitingScreen): ?>
                updateProgress();
                startTimer();
                
                // Prevent going back
                window.onpopstate = function() {
                    history.forward();
                };
                
                // Mark answered state if answer exists
                <?php if (!empty($teamAnswer)): ?>
                    answerSelected = true;
                    const selectedBtn = document.querySelector('[data-letter="<?php echo $teamAnswer['selected_answer']; ?>"]');
                    if (selectedBtn) {
                        selectedBtn.classList.add('selected', 'answered');
                        selectedBtn.disabled = true;
                    }
                    // Enable next/submit button
                    if (currentQuestion < totalQuestions) {
                        document.getElementById('btn-next').disabled = false;
                    } else {
                        document.getElementById('btn-submit').disabled = false;
                    }
                <?php endif; ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>