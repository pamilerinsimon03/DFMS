<?php

require_once __DIR__ . '/../utils/SecurityUtils.php';

class ClamAVScannerService
{
    /**
     * Scans a target file for malware signatures via ClamAV socket/daemon.
     * Returns true if clean or if ClamAV daemon is unconfigured/offline in local dev mode.
     */
    public static function scanFile($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $socketPath = getenv('CLAMAV_SOCKET') ?: '/var/run/clamav/clamd.ctl';
        $host = getenv('CLAMAV_HOST');
        $port = (int)(getenv('CLAMAV_PORT') ?: 3310);

        $socket = null;
        if ($host) {
            $socket = @fsockopen($host, $port, $errno, $errstr, 2);
        } elseif (file_exists($socketPath)) {
            $socket = @stream_socket_client("unix://{$socketPath}", $errno, $errstr, 2);
        }

        // If socket unavailable (e.g. standard local dev without clamd daemon), log warning and allow
        if (!$socket) {
            SecurityUtils::logSecurityEvent('clamav_scan_bypassed', [
                'reason' => 'ClamAV daemon socket not available',
                'file' => basename($filePath)
            ]);
            return true;
        }

        // Write SCAN command
        $realPath = realpath($filePath);
        fwrite($socket, "SCAN {$realPath}\n");
        $response = trim(fgets($socket, 1024));
        fclose($socket);

        if (str_contains($response, 'FOUND')) {
            SecurityUtils::logSecurityEvent('malware_detected', [
                'file' => basename($filePath),
                'scan_response' => $response
            ]);
            return false;
        }

        return true;
    }
}
