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

        // 15 minutes expiry per NDPR 2019 recommendations
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $hashed_token, $expires_at);
        return $stmt->execute();
    }

    public static function getByToken($raw_token) {
        global $conn;
        $hashed_token = hash('sha256', $raw_token);
        
        $sql = "SELECT * FROM password_resets WHERE (token = ? OR token = ?) AND expires_at > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_token, $raw_token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function delete($raw_token) {
        global $conn;
        $hashed_token = hash('sha256', $raw_token);
        $sql = "DELETE FROM password_resets WHERE token = ? OR token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hashed_token, $raw_token);
        return $stmt->execute();
    }
}