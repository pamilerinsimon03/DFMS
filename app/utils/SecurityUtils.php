<?php

class SecurityUtils {
    
    private static $rateLimitStore = [];
    
    public static function validateInput($input, $type = 'string', $maxLength = 255) {
        if (empty($input)) {
            return null;
        }
        
        $input = trim($input);
        
        if (strlen($input) > $maxLength) {
            return false;
        }
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_VALIDATE_INT);
            case 'alphanumeric':
                return preg_match('/^[a-zA-Z0-9]+$/', $input) ? $input : false;
            case 'matric':
                return preg_match('/^[A-Z0-9\/\-_]+$/', $input) ? $input : false;
            case 'string':
            default:
                return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
    }
    
    public static function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
        $key = 'rate_limit_' . $identifier;
        $now = time();
        
        
        if (isset(self::$rateLimitStore[$key])) {
            self::$rateLimitStore[$key] = array_filter(
                self::$rateLimitStore[$key], 
                function($timestamp) use ($now, $timeWindow) {
                    return ($now - $timestamp) < $timeWindow;
                }
            );
        } else {
            self::$rateLimitStore[$key] = [];
        }
        
        
        if (count(self::$rateLimitStore[$key]) >= $maxAttempts) {
            return false;
        }
        
        
        self::$rateLimitStore[$key][] = $now;
        return true;
    }
    
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    public static function validateFileUpload($file, $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png']) {
        $errors = [];
        
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return $errors;
        }
        
        // Load administrator-configured max_file_size (in MB) from settings.json
        $max_size_mb = 5; // Default fallback
        $settings_file = __DIR__ . '/../config/settings.json';
        if (file_exists($settings_file)) {
            $settings = json_decode(file_get_contents($settings_file), true);
            if (isset($settings['max_file_size'])) {
                $max_size_mb = (int)$settings['max_file_size'];
            }
        }
        $max_size_bytes = $max_size_mb * 1024 * 1024;
        
        if ($file['size'] > $max_size_bytes) {
            $errors[] = 'File size exceeds ' . $max_size_mb . 'MB limit';
        }
        
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
        
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
            $errors[] = 'File MIME type does not match extension';
        }
        
        return $errors;
    }
    
    public static function generateSecureFilename($originalName, $userId) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = bin2hex(random_bytes(8));
        return "doc_{$userId}_{$timestamp}_{$random}.{$extension}";
    }
}
