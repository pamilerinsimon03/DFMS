<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

class SessionRollOverService
{
    /**
     * Rolls over the academic session, advancing student levels and archiving current records.
     */
    public static function executeRollOver($newSession, $performedByUserId)
    {
        global $conn;

        $conn->begin_transaction();

        try {
            // 1. Fetch all active students
            $sql = "SELECT id, level, session, department_id FROM users WHERE is_admin = 0";
            $res = $conn->query($sql);
            $students = $res->fetch_all(MYSQLI_ASSOC);

            $archivedCount = 0;
            $advancedCount = 0;

            $histStmt = $conn->prepare("INSERT INTO session_history (user_id, session, level, department_id) VALUES (?, ?, ?, ?)");
            $updateStmt = $conn->prepare("UPDATE users SET level = ?, session = ? WHERE id = ?");

            foreach ($students as $s) {
                // Record session snapshot in session_history
                $deptId = ($s['department_id'] !== null && $s['department_id'] !== '') ? (int)$s['department_id'] : 1;
                $currentSession = !empty($s['session']) ? $s['session'] : '2023/2024';
                $currentLevel = !empty($s['level']) ? $s['level'] : '100L';

                $histStmt->bind_param("issi", $s['id'], $currentSession, $currentLevel, $deptId);
                $histStmt->execute();
                $archivedCount++;

                // Determine next level
                $currentNum = (int)preg_replace('/[^0-9]/', '', $s['level']);
                $nextLevel = 'Graduated';

                if ($currentNum >= 100 && $currentNum < 500) {
                    $nextLevel = ($currentNum + 100) . 'L';
                } elseif ($s['level'] === 'Graduated') {
                    $nextLevel = 'Graduated';
                }

                $updateStmt->bind_param("ssi", $nextLevel, $newSession, $s['id']);
                $updateStmt->execute();
                $advancedCount++;
            }

            // Update settings.json if exists
            $settingsFile = __DIR__ . '/../config/settings.json';
            if (file_exists($settingsFile)) {
                $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
                $settings['current_session'] = $newSession;
                $settings['session_year'] = $newSession;
                file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
            }

            // Synchronize active session if available
            if (session_status() === PHP_SESSION_ACTIVE || isset($_SESSION)) {
                $_SESSION['academic_session'] = $newSession;
                $_SESSION['current_academic_session'] = $newSession;
            }

            $conn->commit();

            SecurityUtils::logSecurityEvent('academic_session_rollover', [
                'performed_by' => $performedByUserId,
                'new_session' => $newSession,
                'students_archived' => $archivedCount,
                'students_advanced' => $advancedCount
            ]);

            return [
                'success' => true,
                'message' => "Academic session successfully rolled over to {$newSession}. Advanced {$advancedCount} students.",
                'archived_count' => $archivedCount,
                'advanced_count' => $advancedCount
            ];

        } catch (Exception $e) {
            $conn->rollback();
            SecurityUtils::logSecurityEvent('session_rollover_failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "Session roll-over failed: " . $e->getMessage()
            ];
        }
    }
}
