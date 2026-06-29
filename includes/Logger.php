<?php
/**
 * Logger Class
 * Handles application logging
 * Version: 1.0
 */

class Logger
{
    /**
     * Log error
     */
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log info
     */
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log warning
     */
    public static function warning($message, $context = [])
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log debug
     */
    public static function debug($message, $context = [])
    {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Main log method
     */
    private static function log($level, $message, $context = [])
    {
        $logDir = LOGS_PATH;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logFile = $logDir . '/' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        error_log($logMessage, 3, $logFile);
    }

    /**
     * Get logs
     */
    public static function getLogs($date = null)
    {
        $date = $date ?? date('Y-m-d');
        $logFile = LOGS_PATH . '/' . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }

        return file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Clear logs
     */
    public static function clearLogs($date = null)
    {
        $date = $date ?? date('Y-m-d');
        $logFile = LOGS_PATH . '/' . $date . '.log';
        
        if (file_exists($logFile)) {
            unlink($logFile);
            return true;
        }
        return false;
    }
}
