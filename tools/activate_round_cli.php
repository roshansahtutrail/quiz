<?php
require_once __DIR__ . '/../includes/config.php';
require_once MODELS_PATH . '/Models.php';

$roundId = $argv[1] ?? 1;
$rm = new RoundModel();
if ($rm->activateRound($roundId)) {
    echo "Activated round: $roundId\n";
} else {
    echo "Failed to activate round: $roundId\n";
}
