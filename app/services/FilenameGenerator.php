<?php
require_once __DIR__ . '/../models/User.php';

class FilenameGenerator {
    /**
     * Generates a standardized filename based on user data and document details.
     *
     * @param int $userId The ID of the user uploading the file.
     * @param string $documentName The name for the document (e.g., "Transcript").
     * @param string $semester The selected semester (e.g., "1st Semester").
     * @param string $originalFileExtension The file's original extension (e.g., "pdf").
     * @return string|false The generated filename or false on failure.
     */
    public static function generate($userId, $documentName, $semester, $originalFileExtension) {
        $user = User::getById($userId);

        if (!$user || empty($user['department_code'])) {
            // Cannot generate a name if user or department info is missing
            return false;
        }

        // --- Assemble the filename parts ---
        $matricNumber = $user['matric_number'];
        $level = preg_replace('/[^0-9]/', '', $user['level']); //  "500L" -> "500"
        $semesterCode = (str_contains($semester, '1st')) ? '1st' : '2nd';
        $programTypeCode = ($user['program_type'] === 'Undergraduate') ? 'Undergrad' : 'PG';
        $modeCode = ($user['mode'] === 'Regular') ? 'Regular' : 'DL';
        $departmentCode = $user['department_code'];
        
        // Sanitize the document name to be filesystem-safe
        $safeDocumentName = preg_replace('/[^a-zA-Z0-9-]/', '_', $documentName);

        // Format: E0500627_500_2nd_Undergrad_Regular_CS_Transcript.pdf
        $filename = sprintf('%s_%s_%s_%s_%s_%s_%s.%s',
            $matricNumber,
            $level,
            $semesterCode,
            $programTypeCode,
            $modeCode,
            $departmentCode,
            $safeDocumentName,
            $originalFileExtension
        );

        return $filename;
    }
}