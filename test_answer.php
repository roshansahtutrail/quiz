<?php
require_once 'includes/config.php';
require_once 'Models/Models.php';
require_once 'Models/QuestionModel.php';
require_once 'Models/AnswerModel.php';

// Set up session for team
$_SESSION['team_id'] = 1;

try {
    echo "Starting test...\n";
    
    // Create answer model
    $answer_model = new AnswerModel();
    echo "AnswerModel created\n";
    
    // Test saveAnswer
    $result = $answer_model->saveAnswer(1, 1, 1, 'A');
    
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
