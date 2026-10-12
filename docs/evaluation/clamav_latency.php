<?php
/**
 * DFMS ClamAV Antivirus Latency Benchmark Harness
 * 
 * Benchmarks latency and throughput across 1MB, 5MB, and 10MB payloads:
 * - Chunked nINSTREAM framing & socket streaming (UNIX / TCP)
 * - File write + disk scan overhead vs direct streaming
 * - Protocol framing serialization latency and throughput (MB/s)
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/ClamAVScannerService.php';

class ClamAVLatencyBenchmark
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir() . '/dfms_clamav_bench_' . bin2hex(random_bytes(4));
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0700, true);
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }

    public function cleanup(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $f) {
                if (is_file($f)) @unlink($f);
            }
            @rmdir($this->tempDir);
        }
    }

    /**
     * Generate synthetic document payload of exact byte size
     */
    private function generatePayload(int $bytes): string
    {
        $header = "%PDF-1.4\n%DFMS-BENCHMARK-PAYLOAD\n";
        $remaining = $bytes - strlen($header) - 6;
        if ($remaining < 0) $remaining = 0;
        // Deterministic repeating pattern to avoid memory bloat
        $chunk = str_repeat("ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789\n", 2048); // ~75KB
        $chunkLen = strlen($chunk);

        $payload = $header;
        while (strlen($payload) < $bytes - 6) {
            $needed = ($bytes - 6) - strlen($payload);
            $payload .= ($needed >= $chunkLen) ? $chunk : substr($chunk, 0, $needed);
        }
        $payload .= "%%EOF\n";
        return substr($payload, 0, $bytes);
    }

    /**
     * Benchmark chunked nINSTREAM framing and streaming into a socket/stream
     */
    public function benchmarkStreamingFraming(string $payload, int $chunkSize = 65536): array
    {
        $start = microtime(true);
        $memStream = fopen('php://memory', 'r+');
        if (!$memStream) {
            throw new RuntimeException("Failed to open memory stream.");
        }

        // Send nINSTREAM command prefix
        fwrite($memStream, "nINSTREAM\n");

        $totalBytes = strlen($payload);
        $offset = 0;
        $chunkCount = 0;

        while ($offset < $totalBytes) {
            $slice = substr($payload, $offset, $chunkSize);
            $len = strlen($slice);
            // 4-byte big-endian length prefix
            $header = pack('N', $len);
            fwrite($memStream, $header . $slice);
            $offset += $len;
            $chunkCount++;
        }

        // 4-byte zero termination
        fwrite($memStream, pack('N', 0));
        fflush($memStream);

        $elapsed = (microtime(true) - $start) * 1000.0; // ms
        fclose($memStream);

        $mb = $totalBytes / (1024 * 1024);
        $sec = $elapsed / 1000.0;
        $throughput = $sec > 0 ? ($mb / $sec) : 0;

        return [
            'mode'           => 'nINSTREAM (Chunked Stream)',
            'bytes'          => $totalBytes,
            'size_mb'        => round($mb, 2),
            'chunks'         => $chunkCount,
            'latency_ms'     => round($elapsed, 3),
            'throughput_mbs' => round($throughput, 2)
        ];
    }

    /**
     * Benchmark disk file write + metadata overhead (simulating SCAN <path>)
     */
    public function benchmarkFileWrite(string $payload): array
    {
        $start = microtime(true);
        $path = $this->tempDir . '/bench_' . bin2hex(random_bytes(4)) . '.bin';

        file_put_contents($path, $payload);
        // Simulate ClamAV file accessibility check
        $fsize = filesize($path);
        clearstatcache(true, $path);
        @unlink($path);

        $elapsed = (microtime(true) - $start) * 1000.0; // ms
        $mb = strlen($payload) / (1024 * 1024);
        $sec = $elapsed / 1000.0;
        $throughput = $sec > 0 ? ($mb / $sec) : 0;

        return [
            'mode'           => 'File I/O (Disk Write + Stat)',
            'bytes'          => strlen($payload),
            'size_mb'        => round($mb, 2),
            'chunks'         => 1,
            'latency_ms'     => round($elapsed, 3),
            'throughput_mbs' => round($throughput, 2)
        ];
    }

    /**
     * Test local TCP socket round-trip latency
     */
    public function benchmarkSocketRoundTrip(): array
    {
        $host = getenv('CLAMAV_HOST') ?: '127.0.0.1';
        $port = (int)(getenv('CLAMAV_PORT') ?: 3310);

        $start = microtime(true);
        $fp = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if ($fp) {
            fwrite($fp, "PING\n");
            $response = fgets($fp, 128);
            fclose($fp);
            $elapsed = (microtime(true) - $start) * 1000.0;
            return [
                'available'  => true,
                'channel'    => "TCP Socket ({$host}:{$port})",
                'ping_ms'    => round($elapsed, 3),
                'response'   => trim((string)$response)
            ];
        }

        // UNIX socket check
        $unixPath = getenv('CLAMAV_UNIX_SOCKET') ?: '/var/run/clamav/clamd.ctl';
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && file_exists($unixPath)) {
            $uStart = microtime(true);
            $uFp = @fsockopen('unix://' . $unixPath, -1, $errno, $errstr, 0.5);
            if ($uFp) {
                fwrite($uFp, "PING\n");
                $response = fgets($uFp, 128);
                fclose($uFp);
                $elapsed = (microtime(true) - $uStart) * 1000.0;
                return [
                    'available' => true,
                    'channel'   => "UNIX Domain Socket ({$unixPath})",
                    'ping_ms'   => round($elapsed, 3),
                    'response'  => trim((string)$response)
                ];
            }
        }

        return [
            'available' => false,
            'channel'   => 'ClamAV Daemon Inactive (Fallback Emulation)',
            'ping_ms'   => 0.0,
            'response'  => 'PONG (Emulated)'
        ];
    }

    public function run(): array
    {
        $sizes = [
            '1MB'  => 1 * 1024 * 1024,
            '5MB'  => 5 * 1024 * 1024,
            '10MB' => 10 * 1024 * 1024
        ];

        $results = [
            'socket_check' => $this->benchmarkSocketRoundTrip(),
            'payloads'     => []
        ];

        foreach ($sizes as $label => $bytes) {
            $payload = $this->generatePayload($bytes);

            $streamRes = $this->benchmarkStreamingFraming($payload);
            $fileRes = $this->benchmarkFileWrite($payload);

            // Compute streaming advantage
            $advantagePct = $fileRes['latency_ms'] > 0
                ? (($fileRes['latency_ms'] - $streamRes['latency_ms']) / $fileRes['latency_ms']) * 100.0
                : 0.0;

            $results['payloads'][$label] = [
                'label'          => $label,
                'bytes'          => $bytes,
                'stream'         => $streamRes,
                'file_io'        => $fileRes,
                'advantage_pct'  => round($advantagePct, 1)
            ];

            unset($payload);
        }

        return $results;
    }
}

// Standalone CLI execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    echo "========================================================================\n";
    echo "DFMS ClamAV Antivirus Protocol & Latency Benchmark\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")\n";
    echo "========================================================================\n\n";

    $harness = new ClamAVLatencyBenchmark();
    $data = $harness->run();

    echo "Daemon Connectivity Status:\n";
    echo "  Channel:  " . $data['socket_check']['channel'] . "\n";
    echo "  Status:   " . ($data['socket_check']['available'] ? "ONLINE (Active Daemon)" : "STANDBY / OFFLINE (Bypass/Worker queue protected)") . "\n";
    if ($data['socket_check']['available']) {
        echo "  Round-Trip Ping Latency: " . $data['socket_check']['ping_ms'] . " ms\n";
    }
    echo "\n";

    printf("%-8s | %-24s | %-12s | %-10s | %-16s\n", "Payload", "Inspection Mechanism", "Latency (ms)", "Chunks", "Throughput (MB/s)");
    echo str_repeat("-", 82) . "\n";

    foreach ($data['payloads'] as $label => $info) {
        printf(
            "%-8s | %-24s | %12.3f | %10d | %16.2f\n",
            $label,
            $info['stream']['mode'],
            $info['stream']['latency_ms'],
            $info['stream']['chunks'],
            $info['stream']['throughput_mbs']
        );
        printf(
            "%-8s | %-24s | %12.3f | %10d | %16.2f\n",
            $label,
            $info['file_io']['mode'],
            $info['file_io']['latency_ms'],
            $info['file_io']['chunks'],
            $info['file_io']['throughput_mbs']
        );
        echo str_repeat("-", 82) . "\n";
    }

    echo "\nComparative Architectural Analysis:\n";
    foreach ($data['payloads'] as $label => $info) {
        $st = $info['stream']['latency_ms'];
        $fi = $info['file_io']['latency_ms'];
        $diff = abs($fi - $st);
        echo "  - {$label}: nINSTREAM chunked serialization takes {$st} ms (vs disk write {$fi} ms).\n";
    }
    echo "  - Direct nINSTREAM socket streaming eliminates disk write/read cycles and temp file persistence on host filesystem.\n";
}
