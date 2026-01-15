<?php
require_once __DIR__ . '/../models/User.php';

class FilenameGenerator {
    public static function generate($userId, $documentName, $semester, $originalFileExtension) {
        $user = User::getById($userId);

        if (!$user || empty($user['department_id'])) {
            return false;
        }

        $matricNumber = $user['matric_number'];
        $level = preg_replace('/[^0-9]/', '', $user['level']);
        $semesterCode = (str_contains($semester, '1st')) ? '1st' : '2nd';
        $programTypeCode = ($user['program_type'] === 'Undergraduate') ? 'Undergrad' : 'PG';
        $modeCode = ($user['mode'] === 'Regular') ? 'Regular' : 'DL';
        $department_id = $user['department_id'];
        
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