<?php
/**
 * DFMS Controlled File Delivery Service
 * 
 * Production-grade kernel streaming and defense-in-depth file delivery:
 * 1. Multi-mode delivery via SENDFILE_MODE:
 *    - 'apache': X-Sendfile
 *    - 'nginx': X-Accel-Redirect
 *    - 'php': direct readfile (local development fallback)
 * 2. Defense-in-depth scoping checks: AccessScope::isDocumentInScope()
 * 3. Pipeline completion enforcement: Rejects unverified or non-clean files
 * 4. Anti-tampering SHA-256 integrity validation
 * 5. Strict security headers: X-Content-Type-Options: nosniff, strict Content-Security-Policy
 */

require_once __DIR__ . '/../utils/StoragePathResolver.php';
require_once __DIR__ . '/../utils/AccessScope.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/User.php';

class FileDeliveryService
{
    private static ?string $overrideMode = null;

    /**
     * Set explicit delivery mode for testing ('apache', 'nginx', 'php')
     */
    public static function setDeliveryMode(?string $mode): void
    {
        self::$overrideMode = $mode ? strtolower($mode) : null;
    }

    /**
     * Get active delivery mode from environment or override
     */
    public static function getDeliveryMode(): string
    {
        if (self::$overrideMode !== null) {
            return self::$overrideMode;
        }

        $env = getenv('SENDFILE_MODE') ?: ($_ENV['SENDFILE_MODE'] ?? null);
        if ($env) {
            return strtolower(trim($env));
        }

        return 'php';
    }

    /**
     * Compute delivery headers without sending them (for testability and validation)
     * 
     * @param array $doc Document record
     * @param string $fullPath Absolute path on disk
     * @param string $disposition 'inline' or 'attachment'
     * @param string|null $mode Optional mode override
     * @return array [header_name => value]
     */
    public static function computeDeliveryHeaders(array $doc, string $fullPath, string $disposition = 'inline', ?string $mode = null): array
    {
        $deliveryMode = $mode ? strtolower($mode) : self::getDeliveryMode();

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($fullPath) ?: 'application/octet-stream';

        $safeFilename = basename($doc['file_name'] ?? $doc['file_path']);
        // Sanitize filename for header
        $cleanFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $safeFilename);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type'           => $mimeType,
            'Cache-Control'          => 'private, no-transform, max-age=3600'
        ];

        $allowedInlineMimes = ['application/pdf', 'image/jpeg', 'image/png'];

        if ($disposition === 'inline' && in_array($mimeType, $allowedInlineMimes, true)) {
            $headers['Content-Disposition'] = 'inline; filename="' . $cleanFilename . '"';
            // Restrictive CSP: Allows display in iframe while blocking active scripts, external origins, and form submissions
            $headers['Content-Security-Policy'] = "default-src 'none'; frame-ancestors 'self'; object-src 'self' blob:; style-src 'unsafe-inline'; form-action 'none'";
        } else {
            $headers['Content-Disposition'] = 'attachment; filename="' . $cleanFilename . '"';
            $headers['Content-Description'] = 'File Transfer';
            $headers['Expires']             = '0';
            $headers['Pragma']              = 'public';
        }

        if ($deliveryMode === 'apache') {
            $headers['X-Sendfile'] = $fullPath;
        } elseif ($deliveryMode === 'nginx') {
            $storageRoot = StoragePathResolver::getStorageRoot();
            $internalPrefix = getenv('NGINX_INTERNAL_LOCATION') ?: '/internal_secure_uploads/';
            
            $normFull = str_replace('\\', '/', $fullPath);
            $normRoot = rtrim(str_replace('\\', '/', $storageRoot), '/');

            if (str_starts_with($normFull, $normRoot . '/')) {
                $relative = substr($normFull, strlen($normRoot) + 1);
            } else {
                $relative = ltrim(str_replace('\\', '/', $doc['file_path']), '/');
            }

            $headers['X-Accel-Redirect'] = rtrim($internalPrefix, '/') . '/' . ltrim($relative, '/');
        } else {
            $headers['Content-Length'] = (string)filesize($fullPath);
        }

        return $headers;
    }

    /**
     * Perform defense-in-depth authorization, pipeline verification, integrity validation,
     * and deliver the requested document to the client.
     * 
     * @param int|string $idOrPublicId Document internal ID or UUIDv4
     * @param array|null $actor Authenticated user session record
     * @param string $disposition 'inline' or 'attachment'
     * @return void Exits script upon completion
     */
    public static function deliver($idOrPublicId, ?array $actor = null, string $disposition = 'inline'): void
    {
        // 1. Authenticate Actor
        if ($actor === null) {
            $sessionUserId = $_SESSION['user_id'] ?? null;
            if (!$sessionUserId) {
                self::sendResponse(401, 'Authentication required');
            }
            $actor = User::getById((int)$sessionUserId);
            if (!$actor) {
                self::sendResponse(401, 'Invalid user session');
            }
        }

        // 2. Load Document
        $numericId = Document::resolveId($idOrPublicId);
        if (!$numericId) {
            self::sendResponse(404, 'Document not found');
        }

        $doc = Document::getById($numericId);
        if (!$doc) {
            self::sendResponse(404, 'Document not found');
        }

        // 3. Defense-in-depth Authorization Check (W4 Scope Check)
        if (!AccessScope::isDocumentInScope($actor, $doc)) {
            SecurityUtils::logSecurityEvent('unauthorized_file_delivery_attempt', [
                'user_id' => $actor['id'] ?? null,
                'doc_id'  => $doc['id'],
                'target'  => $doc['public_id'] ?? $doc['id'],
                'role'    => AccessScope::resolveRole($actor),
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            self::sendResponse(403, 'Forbidden: You do not have permission to access this document');
        }

        // 4. Verify Pipeline Status & Clean Scan Status
        // Refuse any file whose pipeline has not completed (still in staging or pending QC)
        if (str_contains($doc['file_path'], '_staging') || ($doc['status'] ?? '') === 'Pending Quality Check') {
            SecurityUtils::logSecurityEvent('unverified_file_access_attempt', [
                'user_id' => $actor['id'] ?? null,
                'doc_id'  => $doc['id'],
                'reason'  => 'Document still in staging pipeline'
            ]);
            self::sendResponse(404, 'Document is still being processed and verified');
        }

        // Refuse any file whose scan status is not clean
        $scanStatus = $doc['file_scan_status'] ?? 'pending';
        if ($scanStatus !== 'clean') {
            SecurityUtils::logSecurityEvent('unverified_file_scan_status_access', [
                'user_id'     => $actor['id'] ?? null,
                'doc_id'      => $doc['id'],
                'scan_status' => $scanStatus
            ]);
            self::sendResponse(404, 'Document has not passed security scanning');
        }

        // 5. Resolve Physical File & Validate Storage Containment
        $fullPath = '';
        try {
            $fullPath = StoragePathResolver::resolveAbsolutePath($doc['file_path']);
        } catch (Throwable $e) {
            SecurityUtils::logSecurityEvent('path_traversal_delivery_attempt', [
                'user_id'   => $actor['id'] ?? null,
                'doc_id'    => $doc['id'],
                'file_path' => $doc['file_path'],
                'error'     => $e->getMessage()
            ]);
            self::sendResponse(403, 'Invalid file path specification');
        }

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            self::sendResponse(404, 'File not found on storage system');
        }

        // 6. Anti-Tampering Integrity Validation
        $calculatedHash = hash_file('sha256', $fullPath);
        if (empty($doc['file_hash'])) {
            // Proactively backfill missing file hash
            global $conn;
            if ($conn) {
                $updateStmt = $conn->prepare("UPDATE documents SET file_hash = ? WHERE id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $calculatedHash, $doc['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            }
        } else {
            if (!hash_equals($doc['file_hash'], $calculatedHash)) {
                SecurityUtils::logSecurityEvent('file_integrity_check_failed', [
                    'user_id'         => $actor['id'] ?? null,
                    'doc_id'          => $doc['id'],
                    'expected_hash'   => $doc['file_hash'],
                    'calculated_hash' => $calculatedHash,
                    'ip'              => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);
                self::sendResponse(500, 'File integrity check failed: Content has been tampered with or corrupted');
            }
        }

        // 7. Compute & Dispatch Headers
        $mode = self::getDeliveryMode();
        $headers = self::computeDeliveryHeaders($doc, $fullPath, $disposition, $mode);

        SecurityUtils::logSecurityEvent('file_delivery', [
            'user_id'       => $actor['id'] ?? null,
            'doc_id'        => $doc['id'],
            'disposition'   => $disposition,
            'delivery_mode' => $mode,
            'filename'      => basename($doc['file_name'] ?? $doc['file_path']),
            'file_size'     => filesize($fullPath),
            'ip'            => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        // Clean any residual output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        foreach ($headers as $name => $val) {
            header("{$name}: {$val}");
        }

        // 8. Stream or Offload Content
        if ($mode === 'apache' || $mode === 'nginx') {
            // Kernel streaming offloaded to web server; PHP exits with zero file body
            exit;
        }

        // PHP readfile development/fallback mode
        readfile($fullPath);
        exit;
    }

    /**
     * Emit HTTP response code, message, and terminate execution
     * 
     * @return never
     */
    private static function sendResponse(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }
}
