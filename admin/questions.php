<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/QuestionModel.php';

$auth = new Auth();
if (!$auth->canManageContent()) {
    Helper::redirect(APP_URL . '/admin/login.php');
}

// Handle GET request to fetch question data for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'edit') {
    header('Content-Type: application/json');
    
    try {
        $question_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if (!$question_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
            exit;
        }
        
        $db = Database::getInstance();
        $db->query('SELECT * FROM questions WHERE id = ?', [$question_id]);
        $question = $db->single();
        
        if (!$question) {
            echo json_encode(['success' => false, 'message' => 'Question not found']);
            exit;
        }
        
        // Get options
        $db->query('SELECT * FROM question_options WHERE question_id = ? ORDER BY option_letter ASC', [$question_id]);
        $options = $db->resultSet();
        $question['options'] = $options;
        
        echo json_encode(['success' => true, 'data' => $question]);
        exit;
    } catch (Exception $e) {
        error_log('Question Edit Fetch Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle update question request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question_id']) && (isset($_POST['question_text']) || isset($_FILES['question_image']))) {
    header('Content-Type: application/json');
    
    try {
        $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
        $question_type = isset($_POST['question_type']) ? $_POST['question_type'] : 'text';
        $question_text = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
        $question_image = '';
        $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 10;
        $time_limit = isset($_POST['time_limit']) ? (int)$_POST['time_limit'] : 30;
        $correct_answer = isset($_POST['correct_answer']) ? strtoupper(trim($_POST['correct_answer'])) : '';

        if (!$question_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
            exit;
        }

        if ($question_type === 'text' && empty($question_text)) {
            echo json_encode(['success' => false, 'message' => 'Question text is required for text questions']);
            exit;
        }

        if ($question_type === 'image' && empty($existing_question['question_image']) && (!isset($_FILES['question_image']) || $_FILES['question_image']['size'] === 0)) {
            echo json_encode(['success' => false, 'message' => 'Question image is required for image questions']);
            exit;
        }

        if (empty($correct_answer)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        if ($marks < 1 || $marks > 100) {
            echo json_encode(['success' => false, 'message' => 'Marks must be between 1 and 100']);
            exit;
        }

        if ($time_limit < 10 || $time_limit > 600) {
            echo json_encode(['success' => false, 'message' => 'Time limit must be between 10 and 600 seconds']);
            exit;
        }

        // Get existing question to check for old image
        $db = Database::getInstance();
        $db->query('SELECT * FROM questions WHERE id = ?', [$question_id]);
        $existing_question = $db->single();

        // Handle question image upload if present
        if (isset($_FILES['question_image']) && $_FILES['question_image']['size'] > 0) {
            $file = $_FILES['question_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (in_array($file['type'], $allowed_types)) {
                $upload_dir = ROOT_PATH . '/uploads/questions';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old image if exists
                if ($existing_question && !empty($existing_question['question_image'])) {
                    $old_image_path = ROOT_PATH . '/' . $existing_question['question_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'question_' . $question_id . '_' . time() . '.' . $file_ext;
                $filepath = $upload_dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $question_image = 'uploads/questions/' . $filename;
                } else {
                    // Keep existing image if upload fails
                    if ($existing_question && !empty($existing_question['question_image'])) {
                        $question_image = $existing_question['question_image'];
                    }
                }
            } else {
                // Keep existing image
                if ($existing_question && !empty($existing_question['question_image'])) {
                    $question_image = $existing_question['question_image'];
                }
            }
        } else {
            // Keep existing image
            if ($existing_question && !empty($existing_question['question_image'])) {
                $question_image = $existing_question['question_image'];
            }
        }

        // Update question
        $question_data = [
            'question_text' => $question_text,
            'question_image' => $question_image,
            'marks' => $marks,
            'time_limit' => $time_limit,
            'correct_answer' => $correct_answer,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('questions', $question_data, 'id = ?', [$question_id]);

        // Update options
        $letters = ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'];
        foreach ($letters as $letter => $key) {
            $option_text = isset($_POST["option_{$key}_text"]) ? trim($_POST["option_{$key}_text"]) : '';
            $is_correct = ($letter === $correct_answer) ? 1 : 0;
            $option_image = '';
            $option_type = 'text';

            // Get existing option
            $db->query('SELECT * FROM question_options WHERE question_id = ? AND option_letter = ?', [$question_id, $letter]);
            $existing_option = $db->single();

            // Handle image upload if present
            if (isset($_FILES["option_{$key}_image"]) && $_FILES["option_{$key}_image"]['size'] > 0) {
                $file = $_FILES["option_{$key}_image"];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (in_array($file['type'], $allowed_types)) {
                    $upload_dir = ROOT_PATH . '/uploads/options';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if ($existing_option && !empty($existing_option['option_image'])) {
                        $old_image_path = ROOT_PATH . '/' . $existing_option['option_image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    
                    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'option_' . $question_id . '_' . $letter . '_' . time() . '.' . $file_ext;
                    $filepath = $upload_dir . '/' . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $option_image = 'uploads/options/' . $filename;
                        $option_type = empty($option_text) ? 'image' : 'text';
                    }
                } else {
                    // Keep existing image if no new image uploaded
                    if ($existing_option && !empty($existing_option['option_image'])) {
                        $option_image = $existing_option['option_image'];
                        $option_type = $existing_option['option_type'];
                    }
                }
            } else {
                // Keep existing image
                if ($existing_option && !empty($existing_option['option_image'])) {
                    $option_image = $existing_option['option_image'];
                    $option_type = $existing_option['option_type'];
                }
            }

            // Skip if both text and image are empty
            if (empty($option_text) && empty($option_image)) {
                continue;
            }

            $option_data = [
                'option_text' => $option_text,
                'option_image' => $option_image,
                'option_type' => $option_type
            ];
            
            if ($existing_option) {
                $db->update('question_options', $option_data, 'id = ?', [$existing_option['id']]);
            } else {
                $option_data['question_id'] = $question_id;
                $option_data['option_letter'] = $letter;
                $option_data['created_at'] = date('Y-m-d H:i:s');
                $db->insert('question_options', $option_data);
            }
        }

        $activity_log = new ActivityLog();
        $activity_log->log('update', 'questions', 'question', $question_id, null, $question_data);

        echo json_encode(['success' => true, 'message' => 'Question updated successfully', 'question_id' => $question_id]);
        exit;
    } catch (Exception $e) {
        error_log('Question Update Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle Delete Question Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question') {
    header('Content-Type: application/json');
    
    try {
        $question_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
        
        if (!$question_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
            exit;
        }
        
        $db = Database::getInstance();
        $db->query('DELETE FROM question_options WHERE question_id = ?', [$question_id]);
        $db->query('DELETE FROM questions WHERE id = ?', [$question_id]);
        
        $activity_log = new ActivityLog();
        $activity_log->log('delete', 'questions', 'question', $question_id);
        
        echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
        exit;
    } catch (Exception $e) {
        error_log('Question Delete Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle JSON POST for delete question
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = json_decode(file_get_contents('php://input'), true);
    
    if ($json_data && isset($json_data['action']) && $json_data['action'] === 'delete_question') {
        header('Content-Type: application/json');
        
        try {
            $question_id = isset($json_data['question_id']) ? (int)$json_data['question_id'] : 0;
            
            if (!$question_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
                exit;
            }
            
            $db = Database::getInstance();
            $db->query('DELETE FROM question_options WHERE question_id = ?', [$question_id]);
            $db->query('DELETE FROM questions WHERE id = ?', [$question_id]);
            
            $activity_log = new ActivityLog();
            $activity_log->log('delete', 'questions', 'question', $question_id);
            
            echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
            exit;
        } catch (Exception $e) {
            error_log('Question Delete Error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Handle Form Submission for Adding Questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['question_text']) || isset($_FILES['question_image']))) {
    header('Content-Type: application/json');
    
    try {
        $round_id = isset($_POST['round_id']) ? (int)$_POST['round_id'] : 0;
        $question_type = isset($_POST['question_type']) ? $_POST['question_type'] : 'text';
        $question_text = isset($_POST['question_text']) ? trim($_POST['question_text']) : '';
        $question_image = '';
        $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 10;
        $time_limit = isset($_POST['time_limit']) ? (int)$_POST['time_limit'] : 30;
        $correct_answer = isset($_POST['correct_answer']) ? strtoupper(trim($_POST['correct_answer'])) : '';

        // Validation
        if (empty($round_id) || empty($correct_answer)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Validate based on question type
        if ($question_type === 'text' && empty($question_text)) {
            echo json_encode(['success' => false, 'message' => 'Question text is required for text questions']);
            exit;
        }

        if ($question_type === 'image' && (!isset($_FILES['question_image']) || $_FILES['question_image']['size'] === 0)) {
            echo json_encode(['success' => false, 'message' => 'Question image is required for image questions']);
            exit;
        }

        // Handle question image upload if present
        if ($question_type === 'image' && isset($_FILES['question_image']) && $_FILES['question_image']['size'] > 0) {
            $file = $_FILES['question_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed']);
                exit;
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                echo json_encode(['success' => false, 'message' => 'Image size must be less than 5MB']);
                exit;
            }

            $upload_dir = ROOT_PATH . '/uploads/questions';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'question_' . time() . '_' . uniqid() . '.' . $file_ext;
            $filepath = $upload_dir . '/' . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                exit;
            }
            
            $question_image = 'uploads/questions/' . $filename;
        }

        if ($marks < 1 || $marks > 100) {
            echo json_encode(['success' => false, 'message' => 'Marks must be between 1 and 100']);
            exit;
        }

        if ($time_limit < 10 || $time_limit > 600) {
            echo json_encode(['success' => false, 'message' => 'Time limit must be between 10 and 600 seconds']);
            exit;
        }

        // Get next sequence number
        $question_model = new QuestionModel();
        $roundQuestions = $question_model->getByRound($round_id, 1000);
        $nextSequence = count($roundQuestions) + 1;

        // Insert question
        $db = Database::getInstance();
        $question_data = [
            'round_id' => $round_id,
            'question_text' => $question_text,
            'question_image' => $question_image,
            'marks' => $marks,
            'time_limit' => $time_limit,
            'sequence' => $nextSequence,
            'correct_answer' => $correct_answer,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $question_id = $db->insert('questions', $question_data);

        // Insert options
        $letters = ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'];
        foreach ($letters as $letter => $key) {
            $option_text = isset($_POST["option_{$key}_text"]) ? trim($_POST["option_{$key}_text"]) : '';
            $is_correct = ($letter === $correct_answer) ? 1 : 0;
            $option_image = '';
            $option_type = 'text';

            // Handle image upload if present
            if (isset($_FILES["option_{$key}_image"]) && $_FILES["option_{$key}_image"]['size'] > 0) {
                $file = $_FILES["option_{$key}_image"];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (in_array($file['type'], $allowed_types)) {
                    // Create uploads directory if not exists
                    $upload_dir = ROOT_PATH . '/uploads/options';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'option_' . $question_id . '_' . $letter . '_' . time() . '.' . $file_ext;
                    $filepath = $upload_dir . '/' . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $option_image = 'uploads/options/' . $filename;
                        $option_type = empty($option_text) ? 'image' : 'text'; // 'text' for mixed content
                    }
                }
            }

            // Skip if both text and image are empty
            if (empty($option_text) && empty($option_image)) {
                continue;
            }

            $option_data = [
                'question_id' => $question_id,
                'option_letter' => $letter,
                'option_text' => $option_text,
                'option_image' => $option_image,
                'option_type' => $option_type,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $db->insert('question_options', $option_data);
        }

        // Log activity
        $activity_log = new ActivityLog();
        $activity_log->log('create', 'questions', 'question', $question_id, null, $question_data);

        echo json_encode(['success' => true, 'message' => 'Question saved successfully', 'question_id' => $question_id]);
        exit;
    } catch (Exception $e) {
        error_log('Question Form Error: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

$round_model = new RoundModel();
$question_model = new QuestionModel();
$rounds = $round_model->getAll();
$selectedRound = $_GET['round'] ?? ($rounds[0]['id'] ?? 0);
$selectedRoundName = '';

foreach ($rounds as $round) {
    if ((int)$round['id'] === (int)$selectedRound) {
        $selectedRoundName = $round['name'];
        break;
    }
}

$questions = [];
if ($selectedRound) {
    $questions = $question_model->getByRound($selectedRound, 100);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions Management -<?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="pabson-logo.svg">
    <style>
        .question-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #2563eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .question-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .question-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e3a8a;
            margin: 0;
        }

        .option-preview {
            background: #f0f4f8;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 5px 0;
            border-left: 3px solid #2563eb;
            font-size: 0.9rem;
        }

        .option-preview.correct {
            border-left-color: #10b981;
            background: #ecfdf5;
        }

        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-radius: 12px;
        }

        .modal-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .option-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .option-card.selected {
            border-color: #10b981;
            background: #f0fdf4;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .option-letter {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .option-card.selected .option-letter {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .correct-answer-radio {
            accent-color: #10b981;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(37, 99, 235, 0.3);
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
           <h2><i class="fas fa-quiz"></i>PABSON QUIZ APP</h2>
        </div>
        <ul class="sidebar-menu list-unstyled">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <?php if ($auth->canManageContent()): ?>
                <li><a href="teams.php"><i class="fas fa-users"></i> Teams</a></li>
                <li><a href="rounds.php"><i class="fas fa-circle-notch"></i> Rounds</a></li>
                <li><a href="questions.php" class="active"><i class="fas fa-question"></i> Questions</a></li>
            <?php endif; ?>
            <li><a href="results.php"><i class="fas fa-trophy"></i> Results</a></li>
            <li><a href="leaderboard.php"><i class="fas fa-podium"></i> Leaderboard</a></li>
            <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
            <?php if ($auth->getCurrentAdmin()['role'] === 'super_admin'): ?>
                <li><a href="admins.php"><i class="fas fa-shield-alt"></i> Admin Management</a></li>
            <?php endif; ?>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="backup.php"><i class="fas fa-database"></i> Backup</a></li>
            <li><hr style="opacity: 0.2; margin: 10px 0;"></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Edit Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Navbar -->
        <div class="navbar-top">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <h3><i class="fas fa-question-circle" style="margin-right: 10px;"></i>Questions Management</h3>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" id="btnPrintQuestions">
                        <i class="fas fa-print"></i> Print Questions
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </div>
            </div>
        </div>

        <!-- Round Filter -->
        <div class="card-modern mb-4">
            <div class="card-modern-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-filter"></i> Select Round</label>
                        <select name="round" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($rounds as $round): ?>
                                <option value="<?php echo $round['id']; ?>" <?php echo $selectedRound == $round['id'] ? 'selected' : ''; ?>>
                                    <?php echo Security::escapeOutput($round['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <span class="badge bg-info" style="padding: 10px 15px; font-size: 1rem;">
                            <i class="fas fa-info-circle"></i> Total Questions: <?php echo count($questions); ?>
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- Questions List -->
        <div class="container-fluid p-0">
            <?php if (empty($questions)): ?>
                <div class="alert alert-info text-center" style="padding: 40px;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h5>No Questions Added Yet</h5>
                    <p>Click the "Add Question" button above to get started.</p>
                </div>
            <?php else: ?>
                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div>
                                <h5 class="question-title" data-full-question="<?php echo htmlspecialchars($question['question_text'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <span style="background: #2563eb; color: white; padding: 5px 12px; border-radius: 6px; margin-right: 10px;">Q<?php echo $question['sequence']; ?></span>
                                    <?php echo strlen($question['question_text']) > 80 ? substr(Security::escapeOutput($question['question_text']), 0, 80) . '...' : Security::escapeOutput($question['question_text']); ?>
                                </h5>
                            </div>
                            <div class="action-buttons">
                                <button class="btn-edit" onclick="editQuestion(<?php echo $question['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" onclick="deleteQuestion(<?php echo $question['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="badge" style="background: #e0e7ff; color: #2563eb; padding: 6px 12px;">
                                <i class="fas fa-asterisk"></i> <?php echo $question['marks']; ?> points
                            </span>
                            <span class="badge" style="background: #fef3c7; color: #b45309; padding: 6px 12px;">
                                <i class="fas fa-clock"></i> <?php echo $question['time_limit']; ?>s
                            </span>
                        </div>

                        <?php if (!empty($question['options'])): ?>
                            <div style="margin-top: 15px; border-top: 1px solid #e5e7eb; padding-top: 15px;">
                                <p style="margin: 0 0 10px 0; font-weight: 600; color: #666; font-size: 0.9rem;">
                                    <i class="fas fa-list"></i> Options:
                                </p>
                                <?php foreach ($question['options'] as $option): ?>
                                    <div class="option-preview <?php echo isset($option['is_correct']) && $option['is_correct'] ? 'correct' : ''; ?>">
                                        <strong><?php echo $option['option_letter']; ?>.</strong> 
                                        <?php echo Security::escapeOutput($option['option_text']); ?>
                                        <?php if (isset($option['is_correct']) && $option['is_correct']): ?>
                                            <i class="fas fa-check-circle" style="color: #10b981; margin-left: 10px;"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Question</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 85vh; overflow-y: auto;">
                    <form id="questionForm" enctype="multipart/form-data">
                        <input type="hidden" name="round_id" id="round_id" value="<?php echo $selectedRound; ?>">

                        <!-- Question Details Section -->
                        <div class="mb-4">
                            <h6 style="color: #1e3a8a; font-weight: 700; margin-bottom: 15px;">
                                <i class="fas fa-question-circle"></i> Question Details
                            </h6>

                            <div class="mb-3">
                                <label class="form-label">Question Type *</label>
                                <div class="btn-group w-100" role="group" style="margin-bottom: 15px;">
                                    <input type="radio" class="btn-check" name="question_type" id="type_text" value="text" checked>
                                    <label class="btn btn-outline-primary" for="type_text" style="flex: 1;">
                                        <i class="fas fa-font"></i> Text Question
                                    </label>

                                    <input type="radio" class="btn-check" name="question_type" id="type_image" value="image">
                                    <label class="btn btn-outline-primary" for="type_image" style="flex: 1;">
                                        <i class="fas fa-image"></i> Image Question
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3" id="question_text_div">
                                <label class="form-label">Question Text *</label>
                                <textarea class="form-control" id="question_text" name="question_text" rows="3" placeholder="Enter the question text..." required></textarea>
                            </div>

                            <div class="mb-3" id="question_image_div" style="display: none;">
                                <label class="form-label">Question Image *</label>
                                <input type="hidden" id="existing_question_image" name="existing_question_image" value="">
                                <input type="file" class="form-control" id="question_image" name="question_image" accept="image/*">
                                <img id="question_image_preview" style="max-width: 300px; margin-top: 10px; display: none; border-radius: 8px; border: 2px solid #e5e7eb;">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Marks (Points) *</label>
                                    <input type="number" class="form-control" id="marks" name="marks" value="10" min="1" max="100" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Time Limit (Seconds) *</label>
                                    <input type="number" class="form-control" id="time_limit" name="time_limit" value="30" min="10" max="600" required>
                                </div>
                            </div>
                        </div>

                        <!-- Options Section -->
                        <div class="mb-4">
                            <h6 style="color: #1e3a8a; font-weight: 700; margin-bottom: 15px;">
                                <i class="fas fa-list"></i> Options (Select the Correct Answer)
                            </h6>

                            <div class="options-grid">
                                <?php $letters = ['A', 'B', 'C', 'D']; ?>
                                <?php foreach ($letters as $letter): ?>
                                    <div class="option-card" id="option-<?php echo $letter; ?>">
                                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                            <div class="option-letter"><?php echo $letter; ?></div>
                                            <label style="margin-left: 10px; display: flex; align-items: center; cursor: pointer;">
                                                <input type="radio" name="correct_answer" value="<?php echo $letter; ?>" class="correct-answer-radio">
                                                <span style="margin-left: 5px; font-size: 0.9rem; color: #666;">Correct</span>
                                            </label>
                                        </div>

                                        <textarea class="form-control form-control-sm mb-2" 
                                                  placeholder="Option <?php echo $letter; ?> text..." 
                                                  name="option_<?php echo strtolower($letter); ?>_text" 
                                                  rows="2" style="font-size: 0.9rem;"></textarea>
                                        
                                        <div class="mb-2">
                                            <label class="form-label" style="font-size: 0.85rem; margin-bottom: 5px;">
                                                <i class="fas fa-image"></i> Option Image (Optional)
                                            </label>
                                            <input type="hidden" name="option_<?php echo strtolower($letter); ?>_existing_image" id="option_<?php echo strtolower($letter); ?>_existing_image" value="">
                                            <input type="file" class="form-control form-control-sm" 
                                                   name="option_<?php echo strtolower($letter); ?>_image" 
                                                   id="option_<?php echo strtolower($letter); ?>_image" 
                                                   accept="image/*" style="font-size: 0.85rem;">
                                            <img id="option_<?php echo strtolower($letter); ?>_preview" style="max-width: 200px; margin-top: 10px; display: none; border-radius: 8px; border: 2px solid #e5e7eb;">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding-top: 20px;">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save"></i> Save Question
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        // Form submission
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validation
            const questionType = document.querySelector('input[name="question_type"]:checked')?.value || 'text';
            const questionText = document.getElementById('question_text').value.trim();
            const correctAnswer = document.querySelector('input[name="correct_answer"]:checked');
            const marks = document.getElementById('marks').value;
            const timeLimit = document.getElementById('time_limit').value;
            const existingQuestionImage = document.getElementById('existing_question_image')?.value.trim();

            if (questionType === 'text' && !questionText) {
                Swal.fire('Error', 'Please enter question text', 'error');
                return;
            }

            if (questionType === 'image') {
                const imageField = document.getElementById('question_image');
                const hasNewImage = imageField && imageField.files && imageField.files.length > 0;
                if (!hasNewImage && !existingQuestionImage) {
                    Swal.fire('Error', 'Please upload or keep the existing question image', 'error');
                    return;
                }
            }

            if (!correctAnswer) {
                Swal.fire('Error', 'Please select the correct answer', 'error');
                return;
            }

            // Check all options have text OR image
            const letters = ['a', 'b', 'c', 'd'];
            let hasEmptyOption = false;
            letters.forEach(letter => {
                const textField = document.querySelector(`textarea[name="option_${letter}_text"]`);
                const imageField = document.querySelector(`input[name="option_${letter}_image"]`);
                const existingImageField = document.querySelector(`input[name="option_${letter}_existing_image"]`);
                const hasText = textField && textField.value.trim();
                const hasNewImage = imageField && imageField.files && imageField.files.length > 0;
                const hasExistingImage = existingImageField && existingImageField.value.trim();

                if (!hasText && !hasNewImage && !hasExistingImage) {
                    hasEmptyOption = true;
                }
            });

            if (hasEmptyOption) {
                Swal.fire('Error', 'Please fill all 4 options with text or image', 'error');
                return;
            }

            // Submit form via AJAX
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: document.getElementById('hidden_question_id') ? 'Question Updated!' : 'Question Saved!',
                        text: document.getElementById('hidden_question_id') ? 'The question has been updated successfully.' : 'The question has been added successfully.',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to save question', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while saving the question', 'error');
            });
        });
        
        // Reset modal when hidden
        document.getElementById('addQuestionModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('questionForm').reset();
            const modalTitle = document.querySelector('#addQuestionModal .modal-title');
            modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Add New Question';
            const submitBtn = document.querySelector('#questionForm button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Question';
            const questionIdField = document.getElementById('hidden_question_id');
            if (questionIdField) {
                questionIdField.remove();
            }
            document.getElementById('question_image_preview').style.display = 'none';
            document.getElementById('question_image_preview').src = '';
            document.getElementById('existing_question_image').value = '';
            ['a','b','c','d'].forEach(letter => {
                const preview = document.getElementById(`option_${letter}_preview`);
                const existingImage = document.getElementById(`option_${letter}_existing_image`);
                if (preview) {
                    preview.style.display = 'none';
                    preview.src = '';
                }
                if (existingImage) {
                    existingImage.value = '';
                }
            });
        });

        function updateQuestionTypeDisplay(type) {
            const questionTextDiv = document.getElementById('question_text_div');
            const questionImageDiv = document.getElementById('question_image_div');
            const questionTextField = document.getElementById('question_text');
            const questionImageField = document.getElementById('question_image');

            if (type === 'text') {
                questionTextDiv.style.display = 'block';
                questionImageDiv.style.display = 'none';
                questionTextField.required = true;
                questionImageField.required = false;
            } else {
                questionTextDiv.style.display = 'none';
                questionImageDiv.style.display = 'block';
                questionTextField.required = false;
                questionImageField.required = true;
            }
        }

        function editQuestion(questionId) {
            // Fetch question data
            fetch('?action=edit&id=' + questionId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const question = data.data;
                        
                                    // Reset form and populate with existing data
                        document.getElementById('questionForm').reset();
                        const questionType = question.question_type || (question.question_image ? 'image' : 'text');
                        const typeRadio = document.querySelector(`input[name="question_type"][value="${questionType}"]`);
                        if (typeRadio) {
                            typeRadio.checked = true;
                        }
                        updateQuestionTypeDisplay(questionType);
                        document.getElementById('question_text').value = question.question_text;
                        document.getElementById('marks').value = question.marks;
                        document.getElementById('time_limit').value = question.time_limit;
                        document.getElementById('round_id').value = question.round_id;
                        document.getElementById('existing_question_image').value = question.question_image || '';
                        const questionPreview = document.getElementById('question_image_preview');
                        if (question.question_image) {
                            questionPreview.src = '<?php echo APP_URL; ?>/' + question.question_image;
                            questionPreview.style.display = 'block';
                        } else {
                            questionPreview.style.display = 'none';
                            questionPreview.src = '';
                        }
                        
                        // Populate options
                        const letters = ['A', 'B', 'C', 'D'];
                        document.querySelectorAll('.option-card').forEach(card => card.classList.remove('selected'));
                        letters.forEach(letter => {
                            const option = question.options.find(o => o.option_letter === letter);
                            const key = letter.toLowerCase();
                            const textField = document.querySelector(`textarea[name="option_${key}_text"]`);
                            const radioField = document.querySelector(`input[name="correct_answer"][value="${letter}"]`);
                            const existingOptionInput = document.getElementById(`option_${key}_existing_image`);
                            const preview = document.getElementById(`option_${key}_preview`);

                            if (option) {
                                textField.value = option.option_text || '';
                                if (option.option_image) {
                                    existingOptionInput.value = option.option_image;
                                    preview.src = '<?php echo APP_URL; ?>/' + option.option_image;
                                    preview.style.display = 'block';
                                } else {
                                    existingOptionInput.value = '';
                                    preview.style.display = 'none';
                                    preview.src = '';
                                }
                                if (option.option_letter === question.correct_answer) {
                                    radioField.checked = true;
                                    document.getElementById(`option-${letter}`).classList.add('selected');
                                }
                            } else {
                                textField.value = '';
                                existingOptionInput.value = '';
                                preview.style.display = 'none';
                                preview.src = '';
                            }
                        });
                        
                        // Add hidden field to track that we're editing
                        let questionIdField = document.getElementById('hidden_question_id');
                        if (!questionIdField) {
                            questionIdField = document.createElement('input');
                            questionIdField.type = 'hidden';
                            questionIdField.id = 'hidden_question_id';
                            questionIdField.name = 'question_id';
                            document.getElementById('questionForm').appendChild(questionIdField);
                        }
                        questionIdField.value = questionId;
                        
                        // Update modal title
                        const modalTitle = document.querySelector('#addQuestionModal .modal-title');
                        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Question';
                        
                        // Change submit button text
                        const submitBtn = document.querySelector('#questionForm button[type="submit"]');
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Question';
                        
                        // Show modal
                        const editModal = new bootstrap.Modal(document.getElementById('addQuestionModal'));
                        editModal.show();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to load question', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to load question data', 'error');
                });
        }

        function deleteQuestion(questionId) {
            Swal.fire({
                title: 'Delete Question?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_question',
                            question_id: questionId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', 'Question has been deleted.', 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Failed to delete question', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'An error occurred while deleting the question', 'error');
                    });
                }
            });
        }

        // Handle option card selection for correct answer
        document.querySelectorAll('input[name="correct_answer"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.option-card').forEach(card => {
                    card.classList.remove('selected');
                });
                if (this.checked) {
                    this.closest('.option-card').classList.add('selected');
                }
            });
        });

        // Handle question type toggle (Text vs Image)
        document.querySelectorAll('input[name="question_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                updateQuestionTypeDisplay(this.value);
            });
        });

        // Handle question image preview
        document.getElementById('question_image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('question_image_preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                preview.src = '';
            }
        });

        ['a','b','c','d'].forEach(letter => {
            const optionInput = document.getElementById(`option_${letter}_image`);
            const preview = document.getElementById(`option_${letter}_preview`);
            if (optionInput) {
                optionInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            preview.src = event.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        preview.style.display = 'none';
                        preview.src = '';
                    }
                });
            }
        });

        document.getElementById('btnPrintQuestions')?.addEventListener('click', function() {
            const questionCards = document.querySelectorAll('.question-card');
            if (!questionCards.length) {
                Swal.fire('Info', 'There are no questions to print.', 'info');
                return;
            }

            const selectedRoundName = <?php echo json_encode($selectedRoundName ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

            let printHtml = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Questions</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
    h2 { margin-bottom: 5px; }
    p { margin-top: 0; color: #555; }
    .question-block { margin-bottom: 25px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
    .question-header { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .question-number { background: #2563eb; color: white; padding: 5px 12px; border-radius: 6px; font-weight: 700; }
    .question-text { font-size: 1rem; margin: 0; }
    .question-meta { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; color: #555; }
    .question-meta span { background: #f3f4f6; padding: 5px 10px; border-radius: 6px; }
    .options-list { margin-top: 15px; }
    .option-item { margin-bottom: 8px; }
    .option-item strong { margin-right: 6px; }
    .option-item img { max-width: 100%; margin-top: 6px; display: block; border: 1px solid #ddd; border-radius: 6px; }
</style>
</head>
<body>
    <h1><?php echo APP_NAME; ?></h1>
    <h3>Question List</h3>
    <p>Round: ${selectedRoundName}</p>
    <p>Printed on ${new Date().toLocaleString()}</p>`;

            questionCards.forEach(card => {
                const questionTitle = card.querySelector('.question-title');
                const marksBadge = card.querySelector('.badge:nth-of-type(1)');
                const timeBadge = card.querySelector('.badge:nth-of-type(2)');
                const options = card.querySelectorAll('.option-preview');

                let titleText = '';
                if (questionTitle) {
                    titleText = questionTitle.dataset.fullQuestion || questionTitle.textContent.trim();
                    titleText = titleText.replace(/^Q\d+\s*/, '');
                }

                printHtml += `\n    <div class="question-block">`;
                printHtml += `\n        <div class="question-header">`;
                if (questionTitle) {
                    const numberMatch = questionTitle.textContent.match(/Q(\d+)/);
                    const numberText = numberMatch ? numberMatch[1] : '';
                    printHtml += `\n            <div class="question-number">Q${numberText}</div>`;
                    printHtml += `\n            <div><p class="question-text">${titleText}</p></div>`;
                }
                printHtml += `\n        </div>`;

                printHtml += `\n        <div class="question-meta">`;
                if (marksBadge) {
                    printHtml += `\n            <span>${marksBadge.textContent.trim()}</span>`;
                }
                if (timeBadge) {
                    printHtml += `\n            <span>${timeBadge.textContent.trim()}</span>`;
                }
                printHtml += `\n        </div>`;

                if (options.length) {
                    printHtml += `\n        <div class="options-list">`;
                    options.forEach(option => {
                        const clone = option.cloneNode(true);
                        clone.querySelectorAll('i').forEach(icon => icon.remove());
                        const optionHtml = clone.innerHTML.replace(/<\/?span[^>]*>/g, '');
                        printHtml += `\n            <div class="option-item">${optionHtml.trim()}</div>`;
                    });
                    printHtml += `\n        </div>`;
                }

                printHtml += `\n    </div>`;
            });

            printHtml += `\n</body>\n</html>`;

            const printWindow = window.open('', '', 'height=900,width=1000');
            if (!printWindow) {
                alert('Unable to open print preview. Please allow pop-ups for this site.');
                return;
            }

            printWindow.document.write(printHtml);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        });
    </script>
<?php include_once '../includes/footer.php'; ?>
</body>
</html>
