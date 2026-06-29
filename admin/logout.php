<?php
require_once '../includes/config.php';

$auth = new Auth();
$auth->logout();

// Log activity
$log = new ActivityLog();
$log->log('logout', 'auth', 'admin');

// Clear cookies
setcookie('admin_username', '', time() - 3600, '/');

Helper::redirect(APP_URL . '/admin/login.php');
?>
