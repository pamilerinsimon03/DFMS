<?php
/**
 * Security Maintenance Script
 * Run this script daily via cron job for security maintenance
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';
require_once __DIR__ . '/../utils/DataRetention.php';

echo "Starting security maintenance...\n";

// 1. Clean up expired data
echo "Cleaning up expired data...\n";
DataRetention::cleanupExpiredData();

// 2. Check for suspicious activity
echo "Checking for suspicious activity...\n";
$logFile = __DIR__ . '/../logs/security.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -100); // Last 100 lines
    
    $failedLogins = 0;
    $rateLimitHits = 0;
    
    foreach ($recentLines as $line) {
        $data = json_decode($line, true);
        if ($data) {
            if ($data['event'] === 'failed_login') {
                $failedLogins++;
            } elseif ($data['event'] === 'rate_limit_exceeded') {
                $rateLimitHits++;
            }
        }
    }
    
    // Alert if too many failed logins
    if ($failedLogins > 50) {
        SecurityUtils::logSecurityEvent('security_alert', [
            'type' => 'high_failed_logins',
            'count' => $failedLogins,
            'message' => 'High number of failed login attempts detected'
        ]);
        echo "ALERT: High number of failed logins detected: $failedLogins\n";
    }
    
    // Alert if too many rate limit hits
    if ($rateLimitHits > 20) {
        SecurityUtils::logSecurityEvent('security_alert', [
            'type' => 'high_rate_limit_hits',
            'count' => $rateLimitHits,
            'message' => 'High number of rate limit hits detected'
        ]);
        echo "ALERT: High number of rate limit hits detected: $rateLimitHits\n";
    }
}

// 3. Check file system security
echo "Checking file system security...\n";
$uploadDir = __DIR__ . '/../secure_uploads/';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            if (is_file($filePath)) {
                // Check for suspicious file types
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($extension, ['php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi'])) {
                    SecurityUtils::logSecurityEvent('security_alert', [
                        'type' => 'suspicious_file_upload',
                        'file' => $file,
                        'message' => 'Suspicious file type detected in uploads'
                    ]);
                    echo "ALERT: Suspicious file detected: $file\n";
                }
            }
        }
    }
}

// 4. Check database security
echo "Checking database security...\n";
global $conn;

// Check for users with weak passwords (this is a simplified check)
$sql = "SELECT id, matric_number FROM users WHERE password = '' OR password IS NULL";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    SecurityUtils::logSecurityEvent('security_alert', [
        'type' => 'weak_passwords',
        'count' => $result->num_rows,
        'message' => 'Users with weak passwords detected'
    ]);
    echo "ALERT: Users with weak passwords detected: " . $result->num_rows . "\n";
}

// 5. Generate security report
echo "Generating security report...\n";
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'maintenance_completed' => true,
    'failed_logins' => $failedLogins ?? 0,
    'rate_limit_hits' => $rateLimitHits ?? 0,
    'suspicious_files' => 0, // This would be counted in the file check above
    'weak_passwords' => $result->num_rows ?? 0
];

$reportFile = __DIR__ . '/../logs/security_maintenance_' . date('Y-m-d') . '.json';
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

echo "Security maintenance completed successfully.\n";
echo "Report saved to: $reportFile\n";
?>
