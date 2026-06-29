<?php
/**
 * Login Diagnostics & Verification
 * Debug script to check database connectivity and credentials
 */

require_once 'includes/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Login Diagnostics</title>
    <style>
        body { font-family: Segoe UI, Arial; background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 35%, #2563eb 70%, #3b82f6 100%); color: #333; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { color: #1e3a8a; border-bottom: 3px solid #2563eb; padding-bottom: 10px; }
        h2 { color: #2563eb; margin-top: 25px; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { color: #2563eb; background: #f0f9ff; padding: 10px; border-left: 4px solid #2563eb; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #1e3a8a; color: white; }
        tr:hover { background: #f0f9ff; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin: 5px 5px 5px 0; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #7f1d1d; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        button { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px 10px 0; }
        button:hover { background: #1e3a8a; }
    </style>
</head>
<body>
    <div class='container'>";

// Test 1: Database Connection
echo "<h2>🔍 Test 1: Database Connection</h2>";
try {
    $db = Database::getInstance();
    echo "<p class='success'>✅ Database Connected Successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Connection Failed: " . $e->getMessage() . "</p>";
    die();
}

// Test 2: Admin User
echo "<h2>🔍 Test 2: Admin User</h2>";
$db->query('SELECT id, username, email, role, status FROM admins WHERE username = ?', ['admin']);
$admin = $db->single();

if ($admin) {
    echo "<p class='success'>✅ Admin user exists</p>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Username</td><td><code>" . htmlspecialchars($admin['username']) . "</code></td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
    echo "<tr><td>Role</td><td><span class='badge badge-info'>" . htmlspecialchars($admin['role']) . "</span></td></tr>";
    echo "<tr><td>Status</td><td><span class='badge badge-success'>" . htmlspecialchars($admin['status']) . "</span></td></tr>";
    echo "</table>";
} else {
    echo "<p class='error'>❌ Admin user not found</p>";
}

// Test 3: Team Users
echo "<h2>🔍 Test 3: Team Users</h2>";
$db->query('SELECT COUNT(*) as count FROM teams WHERE status = ?', ['active']);
$result = $db->single();
$teamCount = $result['count'] ?? 0;

if ($teamCount > 0) {
    echo "<p class='success'>✅ Found " . $teamCount . " active teams</p>";
    
    $db->query('SELECT id, username, team_name, school_name, status FROM teams ORDER BY id LIMIT 10');
    $teams = $db->resultSet();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Team Name</th><th>School</th><th>Status</th></tr>";
    foreach ($teams as $team) {
        echo "<tr>";
        echo "<td>" . $team['id'] . "</td>";
        echo "<td><code>" . htmlspecialchars($team['username']) . "</code></td>";
        echo "<td>" . htmlspecialchars($team['team_name']) . "</td>";
        echo "<td>" . htmlspecialchars($team['school_name']) . "</td>";
        echo "<td><span class='badge badge-success'>" . htmlspecialchars($team['status']) . "</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($teamCount > 10) {
        echo "<p class='info'>Showing 10 of " . $teamCount . " teams</p>";
    }
} else {
    echo "<p class='error'>❌ No active teams found</p>";
}

// Test 4: Password Verification
echo "<h2>🔍 Test 4: Password Verification</h2>";
$testPassword = 'password';
$testHash = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/1Pq';

if (password_verify($testPassword, $testHash)) {
    echo "<p class='success'>✅ Password verification working</p>";
    echo "<p class='info'>Test Password '<code>" . $testPassword . "</code>' correctly verifies against stored hash</p>";
} else {
    echo "<p class='error'>❌ Password verification failed</p>";
}

// Test 5: Database Configuration
echo "<h2>🔍 Test 5: Database Configuration</h2>";
echo "<table>";
echo "<tr><th>Config</th><th>Value</th></tr>";
echo "<tr><td>Host</td><td><code>" . DB_HOST . "</code></td></tr>";
echo "<tr><td>Database</td><td><code>" . DB_NAME . "</code></td></tr>";
echo "<tr><td>User</td><td><code>" . DB_USER . "</code></td></tr>";
echo "<tr><td>Charset</td><td><code>" . DB_CHARSET . "</code></td></tr>";
echo "</table>";

// Test 6: Required Files
echo "<h2>🔍 Test 6: Required Files</h2>";
$files = [
    'includes/Database.php',
    'includes/Auth.php',
    'includes/Security.php',
    'includes/Logger.php',
    'quiz/login.php',
    'admin/login.php'
];

foreach ($files as $file) {
    $path = ROOT_PATH . '/' . $file;
    if (file_exists($path)) {
        echo "<p class='success'>✅ " . $file . "</p>";
    } else {
        echo "<p class='error'>❌ " . $file . " NOT FOUND</p>";
    }
}

echo "<h2>✅ Diagnostics Complete</h2>";
echo "<p class='info'>If all tests passed, your system is ready to login!</p>";
echo "<p style='margin-top: 20px;'>";
echo "<button onclick='window.location=\"fix_login_credentials.php\"'>🔧 Fix/Update Team Credentials</button>";
echo "<button onclick='window.location=\"quiz/login.php\"'>👥 Go to Team Login</button>";
echo "<button onclick='window.location=\"admin/login.php\"'>🔐 Go to Admin Login</button>";
echo "</p>";

echo "    </div>
</body>
</html>";
?>
