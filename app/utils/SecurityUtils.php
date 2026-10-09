<?php

require_once __DIR__ . '/../services/ClamAVScannerService.php';

class SecurityUtils {
    private static $redisInstance = null;

    private static function getRedis() {
        if (self::$redisInstance === null && class_exists('Redis')) {
            try {
                $redis = new Redis();
                $host = getenv('REDIS_HOST') ?: '127.0.0.1';
                $port = (int)(getenv('REDIS_PORT') ?: 6379);
                $connected = @$redis->connect($host, $port, 1.5);
                if ($connected) {
                    if (getenv('REDIS_PASSWORD')) {
                        $redis->auth(getenv('REDIS_PASSWORD'));
                    }
                    self::$redisInstance = $redis;
                }
            } catch (Exception $e) {
                self::$redisInstance = false;
            }
        }
        return self::$redisInstance ?: null;
    }
    
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
        // Allow pausing rate limiting in dev/testing mode
        $settings_file = __DIR__ . '/../config/settings.json';
        if (file_exists($settings_file)) {
            $settings = json_decode(file_get_contents($settings_file), true);
            if (!empty($settings['disable_rate_limiting'])) {
                return true;
            }
        }

        if (getenv('DISABLE_RATE_LIMITING') === 'true') {
            return true;
        }

        $redis = self::getRedis();

        if ($redis) {
            try {
                $key = 'rate_limit:' . md5($identifier);
                $current = $redis->incr($key);
                if ($current === 1) {
                    $redis->expire($key, $timeWindow);
                }
                if ($current > $maxAttempts) {
                    return false;
                }
                return true;
            } catch (Exception $e) {
                // Fall through to DB fallback if Redis error
            }
        }

        // Database Fallback Layer
        return self::checkRateLimitDatabaseFallback($identifier, $maxAttempts, $timeWindow);
    }

    private static function checkRateLimitDatabaseFallback($identifier, $maxAttempts, $timeWindow) {
        global $conn;
        $key = md5($identifier);
        $now = time();
        $cutoff = $now - $timeWindow;

        if ($conn) {
            try {
                // Clean up expired hits
                $cleanStmt = $conn->prepare("DELETE FROM rate_limits WHERE key_hash = ? AND timestamp < ?");
                if ($cleanStmt) {
                    $cleanStmt->bind_param("si", $key, $cutoff);
                    $cleanStmt->execute();
                }

                // Count active hits
                $countStmt = $conn->prepare("SELECT COUNT(*) as hit_count FROM rate_limits WHERE key_hash = ?");
                if ($countStmt) {
                    $countStmt->bind_param("s", $key);
                    $countStmt->execute();
                    $res = $countStmt->get_result()->fetch_assoc();
                    
                    if ($res && $res['hit_count'] >= $maxAttempts) {
                        return false;
                    }
                }

                // Record hit
                $insertStmt = $conn->prepare("INSERT INTO rate_limits (key_hash, timestamp) VALUES (?, ?)");
                if ($insertStmt) {
                    $insertStmt->bind_param("si", $key, $now);
                    $insertStmt->execute();
                }
                return true;
            } catch (Throwable $e) {
                // Auto-create rate_limits table if missing
                @$conn->query("CREATE TABLE IF NOT EXISTS rate_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    key_hash VARCHAR(64) NOT NULL,
                    timestamp INT NOT NULL,
                    INDEX idx_key_timestamp (key_hash, timestamp)
                ) ENGINE=InnoDB");

                // Retry or fallback to file-based store
                try {
                    $insertStmt = $conn->prepare("INSERT INTO rate_limits (key_hash, timestamp) VALUES (?, ?)");
                    if ($insertStmt) {
                        $insertStmt->bind_param("si", $key, $now);
                        $insertStmt->execute();
                        return true;
                    }
                } catch (Throwable $e2) {
                    // Fallthrough to file fallback if DB error persists
                }
            }
        }

        // Final File Fallback for offline/CLI tests without DB
        return self::checkRateLimitFileFallback($identifier, $maxAttempts, $timeWindow);
    }


    private static function checkRateLimitFileFallback($identifier, $maxAttempts, $timeWindow) {
        $file = __DIR__ . '/../logs/rate_limits.json';
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $store = [];
        if (file_exists($file)) {
            $store = json_decode(file_get_contents($file), true) ?: [];
        }

        $key = 'rate_limit_' . $identifier;
        $now = time();
        if (!isset($store[$key]) || !is_array($store[$key])) {
            $store[$key] = [];
        }

        $store[$key] = array_values(array_filter($store[$key], function($ts) use ($now, $timeWindow) {
            return ($now - (int)$ts) < $timeWindow;
        }));

        if (count($store[$key]) >= $maxAttempts) {
            file_put_contents($file, json_encode($store, JSON_PRETTY_PRINT), LOCK_EX);
            return false;
        }

        $store[$key][] = $now;
        file_put_contents($file, json_encode($store, JSON_PRETTY_PRINT), LOCK_EX);
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
        
        require_once __DIR__ . '/StoragePathResolver.php';
        $logDir = StoragePathResolver::getLogRoot();
        $logFile = $logDir . '/security.log';
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
            @chmod($logDir, 0750);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        @chmod($logFile, 0640);

        // Write to audit_logs database table if available
        global $conn;
        if ($conn) {
            $userId = $_SESSION['user_id'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $desc = json_encode($details);

            $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, event_type, event_description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issss", $userId, $event, $desc, $ip, $ua);
                $stmt->execute();
            }
        }
    }
    
    public static function validateFileUpload($file, $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png']) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return $errors;
        }
        
        $max_size_mb = 5;
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

        // Run ClamAV Malware Scan
        if (empty($errors)) {
            $isClean = ClamAVScannerService::scanFile($file['tmp_name']);
            if (!$isClean) {
                $errors[] = 'Security scan failed: Malicious content detected in file.';
            }
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
