<?php

class QualityCheckService
{
    /**
     * Checks if a file is an image.
     */
    public static function isImage($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) return false;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            $imageMimes = ['image/jpeg', 'image/png', 'image/gif'];
            return in_array($mime, $imageMimes);
        } elseif (function_exists('getimagesize')) {
            return @getimagesize($filePath) !== false;
        }
        return false;
    }

    /**
     * GD-based variance-of-Laplacian blur estimator (memory-safe).
     */
    public static function getBlurriness($filePath, array &$debug_messages)
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imageconvolution')) {
            $debug_messages[] = '[Debug: GD extension with convolution not available.]';
            return -1;
        }

        // Load image
        $data = @file_get_contents($filePath);
        $img  = @imagecreatefromstring($data);
        if (!$img) {
            $debug_messages[] = '[Debug: GD failed to load image.]';
            return -2;
        }

        // Downsample if too big
        $maxDim = 500;
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $maxDim) {
            $scale = $maxDim / max($w, $h);
            $newW  = (int)($w * $scale);
            $newH  = (int)($h * $scale);
            $small = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($small, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($img);
            $img = $small;
            $w   = $newW;
            $h   = $newH;
        }

        // Laplacian kernel
        $kernel = [
            [-1, -1, -1],
            [-1,  8, -1],
            [-1, -1, -1],
        ];
        $divisor = 1;
        $offset  = 0;

        if (!imageconvolution($img, $kernel, $divisor, $offset)) {
            imagedestroy($img);
            $debug_messages[] = '[Debug: GD convolution failed.]';
            return -3;
        }

        // Sample a ~100x100 grid
        $responses = [];
        $step = max(1, floor(min($w, $h) / 100));
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($img, $x, $y);
                $r   = ($rgb >> 16) & 0xFF;
                $g   = ($rgb >> 8)  & 0xFF;
                $b   = $rgb & 0xFF;
                // gray
                $responses[] = ($r + $g + $b) / 3;
            }
        }
        imagedestroy($img);

        $count = count($responses);
        if ($count === 0) {
            $debug_messages[] = '[Debug: No samples for blur metric.]';
            return -4;
        }

        // compute std-dev
        $mean  = array_sum($responses) / $count;
        $sumSq = 0;
        foreach ($responses as $v) {
            $sumSq += ($v - $mean) ** 2;
        }
        $variance = $sumSq / $count;
        $stdDev   = sqrt($variance);

        $debug_messages[] = '[Debug: Laplacian-stdDev (blur score) ' . round($stdDev, 2) . ']';
        return $stdDev;
    }

    /**
     * Calculates OCR confidence using Tesseract.
     * - Detects PDFs by MIME and rasterizes page 1 to PNG before OCR.
     */
    public static function getOcrConfidence($filePath, array &$debug_messages)
    {
        if (!function_exists('shell_exec')) {
            $debug_messages[] = '[Debug: shell_exec is disabled.]';
            return -1;
        }

        // 1) Detect PDF by MIME
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $filePath) ?: '';
            finfo_close($finfo);
        }
        $rasterPath = $filePath;

        // 2) If it's a PDF, rasterize page 1
        if (stripos($mime, 'pdf') !== false) {
            if (!extension_loaded('imagick')) {
                $debug_messages[] = '[Debug: Cannot rasterize PDF—Imagick not loaded.]';
                return -1;
            }
            try {
                $im = new \Imagick();
                $im->setResolution(300, 300);
                $im->readImage($filePath . '[0]');
                $im->setImageFormat('png');
                $rasterPath = tempnam(sys_get_temp_dir(), 'ocr_pdf_') . '.png';
                $im->writeImage($rasterPath);
                $im->clear();
                $im->destroy();
                $debug_messages[] = "[Debug: PDF rasterized to $rasterPath]";
            } catch (\Exception $e) {
                $debug_messages[] = '[Debug: PDF rasterization failed: ' . $e->getMessage() . ']';
                return -1;
            }
        }

        // 3) Locate Tesseract
        $tess = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            ? trim(shell_exec('where tesseract'))
            : trim(shell_exec('which tesseract'));
        if (empty($tess)) {
            $debug_messages[] = '[Debug: Tesseract not found in PATH.]';
            if ($rasterPath !== $filePath) {
                @unlink($rasterPath);
            }
            return -1;
        }

        // 4) Build & log command
        $tmpBase = tempnam(sys_get_temp_dir(), 'ocr_');
        unlink($tmpBase);
        $cmd = sprintf(
            '%s %s %s tsv -l eng --psm 3 2>&1',
            escapeshellarg($tess),
            escapeshellarg($rasterPath),
            escapeshellarg($tmpBase)
        );
        $debug_messages[] = "[Debug: Running OCR command: $cmd]";

        // 5) Execute and parse TSV
        $output = shell_exec($cmd);
        $tsvFile = $tmpBase . '.tsv';
        if ($rasterPath !== $filePath) {
            @unlink($rasterPath);
        }
        if (!file_exists($tsvFile) || filesize($tsvFile) === 0) {
            @unlink($tsvFile);
            $debug_messages[] = '[Debug: Tesseract produced no output.]';
            return 0;
        }
        $tsv = file_get_contents($tsvFile);
        @unlink($tsvFile);

        $lines = preg_split('/\r\n|\r|\n/', $tsv);
        $confs = [];
        foreach ($lines as $line) {
            $cols = str_getcsv($line, "\t");
            if (isset($cols[10]) && is_numeric($cols[10]) && $cols[10] > 0) {
                $confs[] = (int)$cols[10];
            }
        }
        if (empty($confs)) {
            $debug_messages[] = '[Debug: No confidence data extracted.]';
            return 0;
        }

        $avg = array_sum($confs) / count($confs);
        $debug_messages[] = '[Debug: OCR Confidence ' . round($avg) . '%]';
        return $avg;
    }

    /**
     * Logs debug messages to a file
     */
    public static function logDebugMessages($user_id, $messages)
    {
        $logPath = __DIR__ . '/../logs/upload_debug.log';
        if (!file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] User $user_id: " . implode(' ', $messages) . PHP_EOL;
        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }
}