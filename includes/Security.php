<?php
/**
 * Security Class
 * Handles security operations: CSRF, XSS, SQL Injection protection
 * Version: 1.0
 */

class Security
{
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Get CSRF token
     */
    public static function getCSRFToken()
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Hash password
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input
     */
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Escape output (XSS protection)
     */
    public static function escapeOutput($output)
    {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize filename
     */
    public static function sanitizeFilename($filename)
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = null, $maxSize = null)
    {
        $allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
        $maxSize = $maxSize ?? MAX_UPLOAD_SIZE;

        if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'File upload failed'];
        }

        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'message' => 'File size exceeds maximum limit'];
        }

        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($fileInfo, $file['tmp_name']);
        finfo_close($fileInfo);

        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'message' => 'Invalid file type'];
        }

        return ['valid' => true];
    }

    /**
     * Upload file
     */
    public static function uploadFile($file, $destination, $allowedTypes = null, $maxSize = null)
    {
        $validation = self::validateFileUpload($file, $allowedTypes, $maxSize);
        
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        $filename = time() . '_' . self::sanitizeFilename($file['name']);
        $filepath = $destination . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename, 'path' => $filepath];
        }

        return ['success' => false, 'message' => 'Failed to upload file'];
    }

    /**
     * Get client IP
     */
    public static function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public static function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Generate random token
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate email
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength
     */
    public static function validatePasswordStrength($password)
    {
        $errors = [];
        
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letters';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain numbers';
        }

        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
}
