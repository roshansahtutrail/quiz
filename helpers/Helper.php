<?php
/**
 * Helper Functions
 * Version: 1.0
 */

class Helper
{
    /**
     * Format date
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s')
    {
        if (empty($date)) return '';
        return date($format, strtotime($date));
    }

    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = 'USD')
    {
        return number_format($amount, 2) . ' ' . $currency;
    }

    /**
     * Format file size
     */
    public static function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Time elapsed
     */
    public static function timeElapsed($datetime)
    {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;

        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = round($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = round($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } else {
            $days = round($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }
    }

    /**
     * Array to CSV
     */
    public static function arrayToCSV($array, $filename)
    {
        $output = fopen('php://output', 'w');
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        foreach ($array as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
    }

    /**
     * JSON response
     */
    public static function jsonResponse($status, $message, $data = [], $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        return json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Redirect
     */
    public static function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Generate slug
     */
    public static function generateSlug($string)
    {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }

    /**
     * Random string
     */
    public static function randomString($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    /**
     * Get percentage
     */
    public static function getPercentage($obtained, $total)
    {
        if ($total == 0) return 0;
        return round(($obtained / $total) * 100, 2);
    }

    /**
     * Check if mobile
     */
    public static function isMobile()
    {
        return preg_match('/Mobile|Android|iPhone|iPad|iPod/', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    /**
     * Array search recursive
     */
    public static function arraySearchRecursive($needle, $haystack)
    {
        foreach ($haystack as $key => $value) {
            if ($value === $needle) {
                return $key;
            }
            if (is_array($value) && $this->arraySearchRecursive($needle, $value) !== false) {
                return $key;
            }
        }
        return false;
    }

    /**
     * Generate PDF
     */
    public static function generatePDF($content, $filename)
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $content;
        exit;
    }

    /**
     * Validate input
     */
    public static function validateInput($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
            } elseif (strpos($rule, 'email') !== false && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Invalid email address';
                }
            } elseif (strpos($rule, 'numeric') !== false && !empty($value)) {
                if (!is_numeric($value)) {
                    $errors[$field] = ucfirst($field) . ' must be numeric';
                }
            } elseif (strpos($rule, 'min:') !== false && !empty($value)) {
                preg_match('/min:(\d+)/', $rule, $matches);
                $min = $matches[1] ?? 0;
                if (strlen($value) < $min) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
                }
            } elseif (strpos($rule, 'max:') !== false && !empty($value)) {
                preg_match('/max:(\d+)/', $rule, $matches);
                $max = $matches[1] ?? 255;
                if (strlen($value) > $max) {
                    $errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
                }
            }
        }

        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
}
