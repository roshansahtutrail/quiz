<?php
/**
 * Admin Panel Test Script
 * Tests all admin pages for errors
 */

require_once 'includes/config.php';
require_once MODELS_PATH . '/Models.php';
require_once MODELS_PATH . '/ResultModel.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin Panel Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body><div class='container mt-5'>";
echo "<h1>Admin Panel Test Results</h1>";
echo "<table class='table table-striped'>";
echo "<tr><th>Module</th><th>Status</th><th>Details</th></tr>";

$tests = [];

// Test 1: Database Connection
try {
    $db = Database::getInstance();
    $db->query("SELECT 1");
    $tests[] = ['Database', '✅ OK', 'Connection successful'];
} catch (Exception $e) {
    $tests[] = ['Database', '❌ ERROR', $e->getMessage()];
}

// Test 2: Activity Logs Table
try {
    $db = Database::getInstance();
    $db->query("SELECT * FROM activity_logs LIMIT 1");
    $count = count($db->resultSet());
    $tests[] = ['Activity Logs', '✅ OK', "Can query activity logs table ($count records)"];
} catch (Exception $e) {
    $tests[] = ['Activity Logs', '❌ ERROR', $e->getMessage()];
}

// Test 3: Teams Model
try {
    $team_model = new TeamModel();
    $count = $team_model->getCount();
    $tests[] = ['Teams Model', '✅ OK', "Retrieved teams count: $count"];
} catch (Exception $e) {
    $tests[] = ['Teams Model', '❌ ERROR', $e->getMessage()];
}

// Test 4: Rounds Model
try {
    $round_model = new RoundModel();
    $rounds = $round_model->getAll();
    $count = count($rounds);
    $tests[] = ['Rounds Model', '✅ OK', "Retrieved rounds: $count"];
} catch (Exception $e) {
    $tests[] = ['Rounds Model', '❌ ERROR', $e->getMessage()];
}

// Test 5: Admin Model
try {
    $admin_model = new AdminModel();
    $count = $admin_model->getCount();
    $tests[] = ['Admin Model', '✅ OK', "Retrieved admins count: $count"];
} catch (Exception $e) {
    $tests[] = ['Admin Model', '❌ ERROR', $e->getMessage()];
}

// Test 6: Result Model
try {
    $result_model = new ResultModel();
    $tests[] = ['Result Model', '✅ OK', 'Result model loaded'];
} catch (Exception $e) {
    $tests[] = ['Result Model', '❌ ERROR', $e->getMessage()];
}

// Test 7: Leaderboard Model
try {
    $leaderboard = new LeaderboardModel();
    $tests[] = ['Leaderboard Model', '✅ OK', 'Leaderboard model loaded'];
} catch (Exception $e) {
    $tests[] = ['Leaderboard Model', '❌ ERROR', $e->getMessage()];
}

// Test 8: Activity Log Class
try {
    $activity_log = new ActivityLog();
    $tests[] = ['Activity Log Class', '✅ OK', 'ActivityLog class loaded'];
} catch (Exception $e) {
    $tests[] = ['Activity Log Class', '❌ ERROR', $e->getMessage()];
}

// Display results
foreach ($tests as $test) {
    echo "<tr>";
    echo "<td>" . $test[0] . "</td>";
    echo "<td>" . $test[1] . "</td>";
    echo "<td><small>" . $test[2] . "</small></td>";
    echo "</tr>";
}

echo "</table>";

// Test Pages
echo "<h2 class='mt-5'>Page Loading Test</h2>";
echo "<p>Visit these pages to test if they load correctly:</p>";
echo "<ul class='list-group'>";
echo "<li class='list-group-item'><a href='admin/dashboard.php' target='_blank'>Dashboard</a></li>";
echo "<li class='list-group-item'><a href='admin/teams.php' target='_blank'>Teams</a></li>";
echo "<li class='list-group-item'><a href='admin/rounds.php' target='_blank'>Rounds</a></li>";
echo "<li class='list-group-item'><a href='admin/questions.php' target='_blank'>Questions</a></li>";
echo "<li class='list-group-item'><a href='admin/results.php' target='_blank'>Results</a></li>";
echo "<li class='list-group-item'><a href='admin/leaderboard.php' target='_blank'>Leaderboard</a></li>";
echo "<li class='list-group-item'><a href='admin/analytics.php' target='_blank'>Analytics</a></li>";
echo "<li class='list-group-item'><a href='admin/activity-logs.php' target='_blank'>Activity Logs</a></li>";
echo "<li class='list-group-item'><a href='admin/admins.php' target='_blank'>Admin Management</a></li>";
echo "<li class='list-group-item'><a href='admin/settings.php' target='_blank'>Settings</a></li>";
echo "<li class='list-group-item'><a href='admin/backup.php' target='_blank'>Backup</a></li>";
echo "</ul>";

echo "</div></body></html>";
?>
