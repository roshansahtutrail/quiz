<?php
require_once 'includes/config.php';

$db = Database::getInstance();

echo "<h2>Admin Password Reset</h2>";

// Generate new password hash for "password"
$newPassword = 'password';
$hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);

echo "<p>Generating new hash for password: '<strong>password</strong>'</p>";
echo "<p>New Hash: <code>" . $hashedPassword . "</code></p>";

// Update admin password
$db->update('admins', ['password' => $hashedPassword], 'username = ?', ['admin']);

echo "<p style='color: green;'><strong>✅ Admin password updated!</strong></p>";

// Verify it was updated
$db->query('SELECT * FROM admins WHERE username = ?', ['admin']);
$admin = $db->single();

if ($admin) {
    $isValid = password_verify($newPassword, $admin['password']);
    echo "<p>Password verification test: <strong>" . ($isValid ? "✅ PASS" : "❌ FAIL") . "</strong></p>";
    
    if ($isValid) {
        echo "<p style='color: green;'><strong>🎉 Password is now working correctly!</strong></p>";
        echo "<p>You can now login with:</p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> admin</li>";
        echo "<li><strong>Password:</strong> password</li>";
        echo "</ul>";
    }
}

?>
