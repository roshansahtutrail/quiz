<?php
require_once '../includes/config.php';
require_once MODELS_PATH . '/Models.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$auth = new Auth();

// Authentication check
if (!$auth->canManageContent()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$round_model = new RoundModel();
$log = new ActivityLog();

switch ($action) {
    case 'create':
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'time_per_question' => $_POST['time_per_question'] ?? 30,
            'status' => 'inactive'
        ];

        if (empty($data['name'])) {
            echo json_encode(['success' => false, 'message' => 'Round name is required']);
            exit;
        }

        $roundId = $round_model->create($data);
        $log->log('create', 'rounds', 'round', $roundId, null, $data);

        echo json_encode(['success' => true, 'message' => 'Round created successfully', 'round_id' => $roundId]);
        break;

    case 'update':
        $roundId = $_POST['id'] ?? 0;
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'time_per_question' => $_POST['time_per_question'] ?? 30
        ];

        $round_model->update($roundId, $data);
        $log->log('update', 'rounds', 'round', $roundId, null, $data);

        echo json_encode(['success' => true, 'message' => 'Round updated successfully']);
        break;

    case 'delete':
        $roundId = $_POST['id'] ?? 0;
        $round_model->delete($roundId);
        $log->log('delete', 'rounds', 'round', $roundId);

        echo json_encode(['success' => true, 'message' => 'Round deleted successfully']);
        break;

    case 'activate':
        $roundId = $_POST['id'] ?? 0;
        $round_model->activateRound($roundId);
        $log->log('activate', 'rounds', 'round', $roundId);

        echo json_encode(['success' => true, 'message' => 'Round activated successfully']);
        break;

    case 'deactivate':
        $roundId = $_POST['id'] ?? 0;
        $round_model->deactivateRound($roundId);
        $log->log('deactivate', 'rounds', 'round', $roundId);

        echo json_encode(['success' => true, 'message' => 'Round deactivated successfully']);
        break;

    case 'lock':
        $roundId = $_POST['id'] ?? 0;
        $round_model->lockRound($roundId);
        $log->log('lock', 'rounds', 'round', $roundId);

        echo json_encode(['success' => true, 'message' => 'Round locked successfully']);
        break;

    case 'unlock':
        $roundId = $_POST['id'] ?? 0;
        $round_model->unlockRound($roundId);
        $log->log('unlock', 'rounds', 'round', $roundId);

        echo json_encode(['success' => true, 'message' => 'Round unlocked successfully']);
        break;

    case 'complete':
        $roundId = $_POST['id'] ?? 0;
        $round_model->completeRound($roundId);
        $log->log('complete', 'rounds', 'round', $roundId);

        echo json_encode(['success' => true, 'message' => 'Round completed successfully']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
