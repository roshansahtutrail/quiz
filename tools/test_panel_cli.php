<?php
// Simulate a team session and include panel.php to inspect output
session_start();
// Adjust these IDs if your sample data uses different IDs
$_SESSION['team_id'] = $argv[1] ?? 1;
$_SESSION['team_username'] = 'team_alpha';
$_SESSION['team_name'] = 'Team Alpha';
// Optionally set q
if (isset($argv[2])) {
    $_GET['q'] = $argv[2];
}

// Capture output
ob_start();
include __DIR__ . '/../quiz/panel.php';
$html = ob_get_clean();

// Print a short summary
if (strpos($html, 'Waiting for Next Round') !== false) {
    echo "PANEL: Waiting screen detected\n";
} else if (strpos($html, 'Question') !== false) {
    echo "PANEL: Question screen detected\n";
} else {
    echo "PANEL: Unknown output (size=" . strlen($html) . ")\n";
}

// Optional: save full HTML for inspection
file_put_contents(__DIR__ . '/panel_output.html', $html);
echo "Full output saved to tools/panel_output.html\n";
