<?php
require_once __DIR__ . '/SecurityUtils.php';

class DataRetention {
    
    public static function cleanupExpiredData() {
        global $conn;
        
        $sql = "DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $conn->query($sql);
        
        $sql = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $conn->query($sql);
        
        $logFile = __DIR__ . '/../logs/security.log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $cutoff = time() - (365 * 24 * 60 * 60); // 1 year ago
            
            $filteredLines = array_filter($lines, function($line) use ($cutoff) {
                $data = json_decode($line, true);
                if ($data && isset($data['timestamp'])) {
                    $timestamp = strtotime($data['timestamp']);
                    return $timestamp > $cutoff;
                }
                return true;
            });
            
            file_put_contents($logFile, implode('', $filteredLines));
        }
        
        SecurityUtils::logSecurityEvent('data_retention_cleanup', [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'cleanup_expired_data'
        ]);
    }
    
    public static function getRetentionSummary($userId) {
        global $conn;
        
        $summary = [];
        
        $user = User::getById($userId);
        if ($user) {
            $summary['user_data'] = [
                'created_at' => $user['created_at'],
                'retention_until' => date('Y-m-d', strtotime($user['created_at'] . ' +5 years'))
            ];
        }
        
        $sql = "SELECT COUNT(*) as count, MIN(created_at) as oldest, MAX(created_at) as newest 
                FROM documents WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $summary['documents'] = [
                'count' => $result['count'],
                'oldest' => $result['oldest'],
                'newest' => $result['newest'],
                'retention_until' => $result['newest'] ? date('Y-m-d', strtotime($result['newest'] . ' +2 years')) : null
            ];
        }
        
        return $summary;
    }
    
    public static function scheduleDataDeletion($userId, $reason = 'graduation') {
        global $conn;
        
        $sql = "INSERT INTO data_deletion_schedule (user_id, reason, scheduled_date, status) 
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 YEAR), 'scheduled')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $reason);
        
        if ($stmt->execute()) {
            SecurityUtils::logSecurityEvent('data_deletion_scheduled', [
                'user_id' => $userId,
                'reason' => $reason,
                'scheduled_date' => date('Y-m-d', strtotime('+2 years'))
            ]);
            return true;
        }
        
        return false;
    }
}
