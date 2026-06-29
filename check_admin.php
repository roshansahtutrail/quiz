<?php
require_once 'includes/config.php';

$db = Database::getInstance();

echo "<h2>Admin User Diagnostic</h2>";

// Check if admin exists
echo "<h3>1. Checking Admin User in Database:</h3>";
$db->query('SELECT * FROM admins WHERE username = ?', ['admin']);
$admin = $db->single();

if ($admin) {
    echo "<p style='color: green;'><strong>✅ Admin user found!</strong></p>";
    echo "<pre>";
    echo "ID: " . $admin['id'] . "\n";
    echo "Username: " . $admin['username'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "First Name: " . $admin['first_name'] . "\n";
    echo "Last Name: " . $admin['last_name'] . "\n";
    echo "Role: " . $admin['role'] . "\n";
    echo "Status: " . $admin['status'] . "\n";
    echo "Password Hash: " . $admin['password'] . "\n";
    echo "</pre>";
} else {
    echo "<p style='color: red;'><strong>❌ Admin user NOT found!</strong></p>";
}

// Test password verification
echo "<h3>2. Testing Password Verification:</h3>";
if ($admin) {
    $testPassword = 'password';
    $isValid = password_verify($testPassword, $admin['password']);
    
    echo "<p>Testing password: '<strong>password</strong>'</p>";
    echo "<p>Result: <strong>" . ($isValid ? "✅ VALID" : "❌ INVALID") . "</strong></p>";
    
    if (!$isValid) {
        echo "<p style='color: red;'>⚠️ Password verification failed! The stored hash may be incorrect.</p>";
    }
}

// Check all admins
echo "<h3>3. All Admins in Database:</h3>";
$db->query('SELECT id, username, email, status FROM admins');
$allAdmins = $db->resultSet();

if ($allAdmins) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Status</th></tr>";
    foreach ($allAdmins as $a) {
        echo "<tr>";
        echo "<td>" . $a['id'] . "</td>";
        echo "<td>" . $a['username'] . "</td>";
        echo "<td>" . $a['email'] . "</td>";
        echo "<td>" . $a['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No admins found in database.</p>";
}

// Check database connection
echo "<h3>4. Database Connection Info:</h3>";
echo "<p>Database: " . DB_NAME . "</p>";
echo "<p>Host: " . DB_HOST . "</p>";
echo "<p>User: " . DB_USER . "</p>";

?>
