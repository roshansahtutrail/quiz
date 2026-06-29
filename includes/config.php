<?php
/**
 * Application Configuration
 * Version: 1.0
 */

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quiz_app');
define('DB_CHARSET', 'utf8mb4');

// ============================================================================
// APPLICATION SETTINGS
// ============================================================================
define('APP_NAME', 'PABSON Inter School Quiz Competition');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/quizapp');
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('QUIZ_PATH', ROOT_PATH . '/quiz');
define('AJAX_PATH', ROOT_PATH . '/ajax');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODELS_PATH', ROOT_PATH . '/models');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('SERVICES_PATH', ROOT_PATH . '/services');
define('HELPERS_PATH', ROOT_PATH . '/helpers');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('UPLOADS_URL', APP_URL . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('BACKUPS_PATH', ROOT_PATH . '/backups');

// ============================================================================
// SECURITY SETTINGS
// ============================================================================
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 10);

// ============================================================================
// FILE UPLOAD SETTINGS
// ============================================================================
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif']);

// ============================================================================
// ERROR REPORTING (Development - Change to 0 for production)
// ============================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set('UTC');

// ============================================================================
// START SESSION
// ============================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================================
// AUTO-LOAD REQUIRED FILES
// ============================================================================
require_once INCLUDES_PATH . '/Logger.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Security.php';
require_once INCLUDES_PATH . '/ActivityLog.php';
require_once INCLUDES_PATH . '/Auth.php';
require_once HELPERS_PATH . '/Helper.php';
require_once INCLUDES_PATH . '/ExportHelper.php';

// ============================================================================
// SET ERROR HANDLER
// ============================================================================
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    Logger::error("Error [$errno]: $errstr in $errfile on line $errline");
    if (php_sapi_name() !== 'cli') {
        echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
        exit;
    }
});

// ============================================================================
// SET EXCEPTION HANDLER
// ============================================================================
set_exception_handler(function ($exception) {
    Logger::error("Exception: " . $exception->getMessage());
    if (php_sapi_name() !== 'cli') {
        echo json_encode(['status' => 'error', 'message' => 'An exception occurred']);
        exit;
    }
});
