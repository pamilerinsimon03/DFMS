<?php
/**
 * DFMS Secure Storage Path Resolver
 * 
 * Enforces non-webroot storage isolation, content-addressed HMAC naming,
 * 2-hex prefix sharding, magic-byte extension determination, and path-traversal mitigation.
 */

class StoragePathResolver
{
    private static $allowedMimeMap = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png'
    ];

    /**
     * Get the configured STORAGE_ROOT path
     */
    public static function getStorageRoot(): string
    {
        if (defined('STORAGE_ROOT')) {
            return STORAGE_ROOT;
        }
        $env = getenv('STORAGE_ROOT') ?: ($_ENV['STORAGE_ROOT'] ?? null);
        if ($env) {
            return rtrim(str_replace('\\', '/', $env), '/');
        }
        $repoRoot = realpath(__DIR__ . '/../..');
        $default = dirname($repoRoot ?: __DIR__) . '/dfms_private/secure_uploads';
        return rtrim(str_replace('\\', '/', $default), '/');
    }

    /**
     * Get the configured LOG_ROOT path
     */
    public static function getLogRoot(): string
    {
        if (defined('LOG_ROOT')) {
            return LOG_ROOT;
        }
        $env = getenv('LOG_ROOT') ?: ($_ENV['LOG_ROOT'] ?? null);
        if ($env) {
            return rtrim(str_replace('\\', '/', $env), '/');
        }
        $repoRoot = realpath(__DIR__ . '/../..');
        $default = dirname($repoRoot ?: __DIR__) . '/dfms_private/logs';
        return rtrim(str_replace('\\', '/', $default), '/');
    }

    /**
     * Get the server-side salt for HMAC naming
     */
    public static function getStorageSalt(): string
    {
        if (defined('STORAGE_SALT')) {
            return STORAGE_SALT;
        }
        return getenv('STORAGE_SALT') ?: ($_ENV['STORAGE_SALT'] ?? 'dfms_secure_storage_salt_key_ui_2026');
    }

    /**
     * Determine file extension strictly from magic bytes (finfo), never from client name or header.
     * Returns null if unrecognized or not in allowed list.
     */
    public static function determineExtension(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        if (!$mime || !isset(self::$allowedMimeMap[$mime])) {
            return null;
        }

        return self::$allowedMimeMap[$mime];
    }

    /**
     * Compute SHA-256 HMAC of file content with server-side salt
     */
    public static function computeHmac(string $content): string
    {
        return hash_hmac('sha256', $content, self::getStorageSalt());
    }

    /**
     * Compute plain SHA-256 of file content (used for documents.file_hash)
     */
    public static function computeFileHash(string $filePath): string
    {
        return hash_file('sha256', $filePath);
    }

    /**
     * Resolve the storage destination path for an upload:
     * Format: {session}/{department}/{level}/{first two hex chars of hash}/{hash}.{ext}
     * Directories mode: 0750
     */
    public static function resolveStoragePath(string $session, string $deptCode, $level, string $hmacHash, string $ext): array
    {
        $storageRoot = self::getStorageRoot();

        $sessionFolder = preg_replace('/[^a-zA-Z0-9_-]/', '_', str_replace('/', '_', $session ?: 'default'));
        $deptFolder    = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtoupper($deptCode ?: 'GENERAL'));
        $cleanLevel    = preg_replace('/[^0-9]/', '', (string)$level);
        $levelFolder   = !empty($cleanLevel) ? $cleanLevel . 'L' : 'GENERAL';
        $prefix        = substr($hmacHash, 0, 2);

        $cleanExt = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($ext));
        $filename = "{$hmacHash}.{$cleanExt}";

        $relativeDir = "{$sessionFolder}/{$deptFolder}/{$levelFolder}/{$prefix}";
        $relativePath = "{$relativeDir}/{$filename}";

        $fullDir = "{$storageRoot}/{$relativeDir}";
        $fullPath = "{$storageRoot}/{$relativePath}";

        // Traversal block: verify full path is inside storage root
        self::assertContainedInRoot($fullPath, $storageRoot);

        if (!is_dir($fullDir)) {
            @mkdir($fullDir, 0750, true);
            @chmod($fullDir, 0750);
        }

        return [
            'relative'   => $relativePath,
            'absolute'   => $fullPath,
            'dir'        => $fullDir,
            'filename'   => $filename,
            'hmac_hash'  => $hmacHash,
            'extension'  => $cleanExt
        ];
    }

    /**
     * Compatibility helper for resolving upload directory
     */
    public static function resolveUploadDirectory(string $session, string $deptCode, $level): array
    {
        $storageRoot = self::getStorageRoot();
        $sessionFolder = preg_replace('/[^a-zA-Z0-9_-]/', '_', str_replace('/', '_', $session ?: 'default'));
        $deptFolder    = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtoupper($deptCode ?: 'GENERAL'));
        $cleanLevel    = preg_replace('/[^0-9]/', '', (string)$level);
        $levelFolder   = !empty($cleanLevel) ? $cleanLevel . 'L' : 'GENERAL';

        $relativeDir = "{$sessionFolder}/{$deptFolder}/{$levelFolder}/";
        $fullDir = "{$storageRoot}/{$relativeDir}";

        if (!is_dir($fullDir)) {
            @mkdir($fullDir, 0750, true);
            @chmod($fullDir, 0750);
        }

        return [
            'relative' => $relativeDir,
            'absolute' => $fullDir
        ];
    }

    /**
     * Resolve an absolute filesystem path from a relative documents.file_path,
     * ensuring it strictly resides within STORAGE_ROOT to block traversal attacks.
     */
    public static function resolveAbsolutePath(string $relativePath): string
    {
        $storageRoot = self::getStorageRoot();
        
        // Strip leading slash or dot components
        $normalizedRelative = ltrim(str_replace(['\\', '//'], '/', $relativePath), '/');
        
        // Check for directory traversal sequences
        if (str_contains($normalizedRelative, '../') || str_contains($normalizedRelative, '..\\')) {
            throw new InvalidArgumentException("Path traversal sequence detected in file path.");
        }

        // Check if file exists directly under storageRoot
        $fullPath = $storageRoot . '/' . $normalizedRelative;
        if (file_exists($fullPath)) {
            self::assertContainedInRoot($fullPath, $storageRoot);
            return $fullPath;
        }

        // If path begins with secure_uploads/ and storageRoot ends with secure_uploads
        if (str_starts_with($normalizedRelative, 'secure_uploads/')) {
            $stripped = substr($normalizedRelative, strlen('secure_uploads/'));
            $strippedPath = $storageRoot . '/' . $stripped;
            if (file_exists($strippedPath)) {
                self::assertContainedInRoot($strippedPath, $storageRoot);
                return $strippedPath;
            }
        }

        // Fallback for legacy paths relative to repo root during migration
        $repoRoot = realpath(__DIR__ . '/../..');
        if ($repoRoot) {
            $repoPath = $repoRoot . '/' . $normalizedRelative;
            if (file_exists($repoPath)) {
                return $repoPath;
            }
        }

        self::assertContainedInRoot($fullPath, $storageRoot);
        return $fullPath;
    }

    /**
     * Resolve staging directory path (mode 0700 outside web root)
     */
    public static function resolveStagingPath(string $filename = ''): string
    {
        $storageRoot = self::getStorageRoot();
        $stagingDir = $storageRoot . '/_staging';
        
        if (!is_dir($stagingDir)) {
            @mkdir($stagingDir, 0700, true);
            @chmod($stagingDir, 0700);
        }

        if ($filename !== '') {
            $safeName = basename($filename);
            return $stagingDir . '/' . $safeName;
        }

        return $stagingDir;
    }

    /**
     * Enforce mode 0640 on stored files
     */
    public static function secureFilePermissions(string $filePath): void
    {
        if (file_exists($filePath)) {
            @chmod($filePath, 0640);
        }
    }

    /**
     * Assert that a target path is strictly contained within the allowed root directory
     */
    private static function assertContainedInRoot(string $targetPath, string $root): void
    {
        $normRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normTarget = str_replace('\\', '/', $targetPath);

        // Normalize potential relative tokens
        $parts = [];
        foreach (explode('/', $normTarget) as $part) {
            if ($part === '' || $part === '.') continue;
            if ($part === '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }
        $canonicalTarget = (str_starts_with($normTarget, '/') ? '/' : '') . implode('/', $parts);

        if (!str_starts_with($canonicalTarget, $normRoot . '/') && $canonicalTarget !== $normRoot) {
            throw new InvalidArgumentException("Access denied: Resolved path escapes STORAGE_ROOT.");
        }
    }
}
