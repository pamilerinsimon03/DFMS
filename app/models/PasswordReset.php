<?php
require_once __DIR__ . '/../config/config.php';

class PasswordReset {
    public static function create($user_id, $token) {
        global $conn;
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $token, $expires_at);
        return $stmt->execute();
    }

    public static function getByToken($token) {
        global $conn;
        $sql = "SELECT * FROM password_resets WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function delete($token) {
        global $conn;
        $sql = "DELETE FROM password_resets WHERE token = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }
}