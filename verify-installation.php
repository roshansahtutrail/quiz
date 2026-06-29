<?php
/**
 * Quiz App - Installation Verification Script
 * This script verifies all requirements are met
 */

// Color codes for console output
$colors = [
    'success' => "\033[92m",  // Green
    'error' => "\033[91m",    // Red
    'warning' => "\033[93m",  // Yellow
    'info' => "\033[94m",     // Blue
    'reset' => "\033[0m"      // Reset
];

function check($title, $condition) {
    global $colors;
    $status = $condition ? '✓ PASS' : '✗ FAIL';
    $color = $condition ? $colors['success'] : $colors['error'];
    echo $color . $status . $colors['reset'] . " - $title\n";
    return $condition;
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   QUIZ MANAGEMENT SYSTEM - Installation Verification      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;

// Check PHP Version
echo "📦 PHP Configuration\n";
$allPassed &= check("PHP Version 8.1+", version_compare(PHP_VERSION, '8.1.0', '>='));
$allPassed &= check("PDO Extension", extension_loaded('pdo'));
$allPassed &= check("PDO MySQL", extension_loaded('pdo_mysql'));
$allPassed &= check("JSON Extension", extension_loaded('json'));
$allPassed &= check("Sessions", extension_loaded('session'));

// Check File Permissions
echo "\n📁 Directory Structure\n";
$directories = [
    'includes' => 'Core includes directory',
    'models' => 'Models directory',
    'admin' => 'Admin panel directory',
    'quiz' => 'Quiz panel directory',
    'ajax' => 'AJAX handlers directory',
    'assets' => 'Assets directory',
    'uploads' => 'Uploads directory',
    'logs' => 'Logs directory',
    'backups' => 'Backups directory',
    'database' => 'Database directory'
];

foreach ($directories as $dir => $desc) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    check($desc . " exists", $exists);
    if ($exists) {
        $writable = is_writable($path);
        if ($dir === 'uploads' || $dir === 'logs' || $dir === 'backups') {
            check("  └─ Writable", $writable);
            $allPassed &= $writable;
        }
    }
    $allPassed &= $exists;
}

// Check Key Files
echo "\n📄 Configuration Files\n";
$files = [
    'includes/config.php' => 'Config file',
    'includes/Database.php' => 'Database class',
    'includes/Auth.php' => 'Auth class',
    'includes/Security.php' => 'Security class',
    'models/Models.php' => 'Models',
    'admin/dashboard.php' => 'Admin dashboard',
    'quiz/panel.php' => 'Quiz panel',
    'ajax/teams.php' => 'Teams AJAX',
    'database/quiz_app.sql' => 'Database SQL file',
    'README.md' => 'README documentation'
];

foreach ($files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    check($desc, $exists);
    $allPassed &= $exists;
}

// Check Database Connection
echo "\n🗄️  Database Configuration\n";
if (file_exists(__DIR__ . '/includes/config.php')) {
    require_once __DIR__ . '/includes/config.php';
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_CHARSET => 'utf8mb4'
        ]);
        check("Database connection", true);
        
        // Check tables
        $tables = [
            'admins', 'teams', 'rounds', 'questions', 'question_options',
            'team_answers', 'round_results', 'overall_results', 'settings',
            'activity_logs', 'backups'
        ];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            check("  └─ Table '$table'", $exists);
            $allPassed &= $exists;
        }
        
    } catch (Exception $e) {
        check("Database connection", false);
        echo "  Error: " . $e->getMessage() . "\n";
        $allPassed = false;
    }
} else {
    check("Config file exists", false);
    $allPassed = false;
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";

if ($allPassed) {
    echo $colors['success'];
    echo "║  ✓ ALL CHECKS PASSED - SYSTEM IS READY!                   ║\n";
    echo $colors['reset'];
} else {
    echo $colors['error'];
    echo "║  ✗ SOME CHECKS FAILED - REVIEW ERRORS ABOVE              ║\n";
    echo $colors['reset'];
}

echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Quick Links
echo "📱 Quick Links:\n";
echo "  • Home: http://localhost/quizapp\n";
echo "  • Admin Login: http://localhost/quizapp/admin/login.php\n";
echo "  • Team Login: http://localhost/quizapp/quiz/login.php\n";
echo "  • phpMyAdmin: http://localhost/phpmyadmin\n\n";

// Next Steps
echo "📋 Next Steps:\n";
if ($allPassed) {
    echo $colors['success'] . "  ✓ System is ready to use!\n" . $colors['reset'];
    echo "  1. Visit: http://localhost/quizapp\n";
    echo "  2. Login with admin / password\n";
    echo "  3. Create rounds and questions\n";
    echo "  4. Add teams\n";
    echo "  5. Activate round and start quiz\n";
} else {
    echo $colors['error'] . "  ✗ Fix issues above before proceeding\n" . $colors['reset'];
    echo "  • Check database connection\n";
    echo "  • Verify all directories exist\n";
    echo "  • Ensure PHP extensions are loaded\n";
    echo "  • Import database if not already done\n";
}

echo "\n";
?>
