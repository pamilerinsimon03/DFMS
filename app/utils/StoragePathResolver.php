<?php

class StoragePathResolver
{
    /**
     * Resolves a partitioned, hierarchical directory structure for file uploads
     * e.g., secure_uploads/2024_2025/CS/100L/
     */
    public static function resolveUploadDirectory($session, $deptCode, $level)
    {
        $sessionFolder = preg_replace('/[^a-zA-Z0-9_-]/', '_', str_replace('/', '_', $session ?: 'default'));
        $deptFolder    = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtoupper($deptCode ?: 'GENERAL'));
        
        $cleanLevel = preg_replace('/[^0-9]/', '', (string)$level);
        $levelFolder = !empty($cleanLevel) ? $cleanLevel . 'L' : 'GENERAL';

        $baseDir = __DIR__ . '/../../secure_uploads/';
        $nestedSubpath = "{$sessionFolder}/{$deptFolder}/{$levelFolder}/";
        $fullPath = $baseDir . $nestedSubpath;

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0750, true);
        }

        return [
            'relative' => 'secure_uploads/' . $nestedSubpath,
            'absolute' => $fullPath
        ];
    }
}
