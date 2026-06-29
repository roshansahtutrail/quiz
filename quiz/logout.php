<?php
require_once '../includes/config.php';

$auth = new Auth();
$auth->logout();

Helper::redirect(APP_URL . '/quiz/login.php');
?>
