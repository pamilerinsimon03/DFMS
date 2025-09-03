<?php
require_once __DIR__ . '/../config/config.php';

class Notification
{
    public static function create($user_id, $title, $message)
    {
        global $conn;
        $sql = "INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $user_id, $title, $message);
        return $stmt->execute();
    }

    public static function getForUser($user_id, $limit = 10)
    {
        global $conn;
        $sql = "SELECT id, title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public static function countUnread($user_id)
    {
        global $conn;
        $sql = "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    }

    public static function markAllRead($user_id)
    {
        global $conn;
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }
}


