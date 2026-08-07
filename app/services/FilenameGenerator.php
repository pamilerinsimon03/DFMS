<?php
require_once __DIR__ . '/../models/User.php';

class FilenameGenerator {
    public static function generate($userId, $documentName, $semester, $originalFileExtension) {
        $user = User::getById($userId);

        if (!$user) {
            return false;
        }

        $matricNumber = !empty($user['matric_number']) ? $user['matric_number'] : 'USER_' . $userId;
        $rawLevel = !empty($user['level']) ? $user['level'] : '100';
        $level = preg_replace('/[^0-9]/', '', $rawLevel) ?: '100';
        $semesterCode = (str_contains($semester ?? '', '1st')) ? '1st' : '2nd';
        $programTypeCode = (!empty($user['program_type']) && stripos($user['program_type'], 'Postgraduate') !== false) ? 'PG' : 'Undergrad';
        $modeCode = (!empty($user['mode']) && stripos($user['mode'], 'Direct') !== false) ? 'DE' : 'Regular';
        $department_id = !empty($user['department_id']) ? $user['department_id'] : 1;
        
        $safeDocumentName = preg_replace('/[^a-zA-Z0-9-]/', '_', $documentName);

        $filename = sprintf('%s_%s_%s_%s_%s_%s_%s.%s',
            $matricNumber,
            $level,
            $semesterCode,
            $programTypeCode,
            $modeCode,
            $department_id,
            $safeDocumentName,
            $originalFileExtension
        );

        return $filename;
    }
}