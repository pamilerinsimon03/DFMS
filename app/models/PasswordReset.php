<?php
require_once __DIR__ . '/../config/config.php';

class PasswordReset {
    public static function create($user_id, $hashed_token) {
        global $conn;

        // Invalidate existing active tokens for user
        $cleanStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
        if ($cleanStmt) {
            $cleanStmt->bind_param("i", $user_id);
            $cleanStmt->execute();
        }

        // 15 minutes expiry using database clock to avoid PHP-MySQL timezone skew
        $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $hashed_token);
        return $stmt->execute();
    }

    public static function getByToken($raw_token) {
        global $conn;
        $hashed_token = hash('sha256', $raw_token);
        
        $sql = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $hashed_token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function delete($raw_token) {
        global $conn;
        $hashed_token = hash('sha256', $raw_token);
        $sql = "DELETE FROM password_resets WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $hashed_token);
        return $stmt->execute();
    }
}