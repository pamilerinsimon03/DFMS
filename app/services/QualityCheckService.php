<?php
/**
 * DFMS Document Quality Check & OCR Verification Service
 * 
 * Implements:
 * 1. Discrete Laplacian Variance Blur Detection (sigma_Lap^2 >= 100):
 *    - 8-bit grayscale normalization
 *    - Width normalized to exactly 800px (preserving aspect ratio)
 *    - Discrete Laplacian convolution kernel [[0,1,0],[1,-4,1],[0,1,0]]
 *    - Full variance computation over all pixels
 *    - PDF page 1 rasterization (PDFs never bypass blur detection)
 * 2. Tesseract OCR & Document Pattern Cross-Check:
 *    - Page 1 field extraction via regex patterns in app/config/document_patterns.php
 *    - Cross-checking extracted fields against student database records
 *    - Mismatches routed to manual review (ocr_match_status = 'mismatch') rather than auto-rejected
 *    - Unreadable text flagged for review (ocr_match_status = 'unreadable')
 */

require_once __DIR__ . '/../utils/SecurityUtils.php';
require_once __DIR__ . '/../models/User.php';

class QualityCheckService
{
    /**
     * Get configured blur variance threshold (default 100.0)
     */
    public static function getBlurThreshold(): float
    {
        $env = getenv('BLUR_VARIANCE_THRESHOLD');
        if ($env !== false && $env !== '') {
            return (float)$env;
        }

        $settingsFile = __DIR__ . '/../config/settings.json';
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true);
            if (!empty($settings['blur_variance_threshold'])) {
                return (float)$settings['blur_variance_threshold'];
            }
        }

        return 100.0;
    }

    /**
     * Convert/rasterize input file (image or PDF page 1) into a temporary image path for analysis
     * 
     * @param string $filePath Physical path to file
     * @return array ['image_path' => string, 'is_temp' => bool]
     */
    public static function rasterizeToImage(string $filePath): array
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        if ($mime !== 'application/pdf') {
            return ['image_path' => $filePath, 'is_temp' => false];
        }

        // PDF rasterization via Imagick or pdftoppm
        $tempOut = tempnam(sys_get_temp_dir(), 'pdf_page1_') . '.png';

        if (extension_loaded('imagick')) {
            try {
                $im = new Imagick();
                $im->setResolution(150, 150);
                $im->readImage($filePath . '[0]'); // First page only
                $im->setImageFormat('png');
                $im->writeImage($tempOut);
                $im->clear();
                $im->destroy();
                return ['image_path' => $tempOut, 'is_temp' => true];
            } catch (Throwable $e) {
                // Fallback to pdftoppm if available
            }
        }

        // Fallback: pdftoppm CLI
        $pdftoppm = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'pdftoppm.exe' : 'pdftoppm';
        $prefix = tempnam(sys_get_temp_dir(), 'pdftoppm_');
        @unlink($prefix);

        $cmd = sprintf('"%s" -png -f 1 -l 1 "%s" "%s" 2>&1', $pdftoppm, $filePath, $prefix);
        @exec($cmd, $out, $ret);

        $generated = $prefix . '-1.png';
        if (file_exists($generated)) {
            return ['image_path' => $generated, 'is_temp' => true];
        }

        // If no rasterizer available, return original for GD fallback handling
        return ['image_path' => $filePath, 'is_temp' => false];
    }

    /**
     * Calculate Laplacian Variance of an image or PDF
     * 
     * Steps:
     * 1. 8-bit grayscale
     * 2. Resize to width = 800px (preserve aspect ratio)
     * 3. Convolve with [[0, 1, 0], [1, -4, 1], [0, 1, 0]]
     * 4. Compute variance over all pixel values
     * 
     * @param string $filePath Physical path to image or PDF
     * @param array $debugMessages Optional array to append debug logs
     * @return float Calculated variance (or negative code on fatal load error)
     */
    public static function calculateLaplacianVariance(string $filePath, array &$debugMessages = []): float
    {
        $raster = self::rasterizeToImage($filePath);
        $imagePath = $raster['image_path'];
        $isTemp = $raster['is_temp'];

        try {
            if (extension_loaded('imagick')) {
                try {
                    $im = new Imagick($imagePath);
                    $im->setImageType(Imagick::IMGTYPE_GRAYSCALE);

                    // Resize to width = 800px preserving aspect ratio
                    $im->resizeImage(800, 0, Imagick::FILTER_LANCZOS, 1);

                    // Discrete Laplacian Kernel [[0, 1, 0], [1, -4, 1], [0, 1, 0]]
                    $kernelMatrix = [
                        [0,  1, 0],
                        [1, -4, 1],
                        [0,  1, 0]
                    ];
                    $kernel = ImagickKernel::fromMatrix($kernelMatrix);
                    $im->convolveImage($kernel);

                    // Compute channel standard deviation and variance (normalized to 8-bit scale 0..255)
                    $stats = $im->getImageChannelStatistics();
                    $stdDev = 0.0;
                    if (isset($stats[0]['standardDeviation'])) {
                        $stdDev = (float)$stats[0]['standardDeviation'];
                    } elseif (isset($stats[Imagick::CHANNEL_DEFAULT]['standardDeviation'])) {
                        $stdDev = (float)$stats[Imagick::CHANNEL_DEFAULT]['standardDeviation'];
                    }

                    $quantum = 65535.0;
                    if (method_exists('Imagick', 'getQuantumRange')) {
                        $q = Imagick::getQuantumRange();
                        $quantum = (float)($q['quantumRangeLong'] ?? 65535);
                    }
                    $stdDev8 = ($stdDev / $quantum) * 255.0;
                    $variance = $stdDev8 * $stdDev8;

                    $im->clear();
                    $im->destroy();

                    $debugMessages[] = "[Imagick Laplacian Variance: " . round($variance, 2) . "]";
                    return $variance;
                } catch (Throwable $e) {
                    $debugMessages[] = "[Imagick failed: " . $e->getMessage() . ". Using GD fallback.]";
                }
            }

            // GD Fallback
            return self::calculateLaplacianVarianceGD($imagePath, $debugMessages);
        } finally {
            if ($isTemp && file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }
    }

    /**
     * GD implementation of discrete Laplacian variance
     */
    private static function calculateLaplacianVarianceGD(string $imagePath, array &$debugMessages = []): float
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imageconvolution')) {
            $debugMessages[] = '[GD convolution unavailable]';
            return -1.0;
        }

        $data = @file_get_contents($imagePath);
        if (!$data) return -2.0;

        $img = @imagecreatefromstring($data);
        if (!$img) return -2.0;

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= 0 || $h <= 0) {
            imagedestroy($img);
            return -2.0;
        }

        // Normalize width to exactly 800px (preserve aspect ratio)
        $targetW = 800;
        $targetH = (int)round($h * ($targetW / $w));
        if ($targetH < 1) $targetH = 1;

        $resized = imagecreatetruecolor($targetW, $targetH);
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $targetW, $targetH, $w, $h);
        imagedestroy($img);

        // Convert to grayscale
        imagefilter($resized, IMG_FILTER_GRAYSCALE);

        // Discrete Laplacian kernel [[0, 1, 0], [1, -4, 1], [0, 1, 0]]
        $kernel = [
            [0,  1, 0],
            [1, -4, 1],
            [0,  1, 0]
        ];

        if (!imageconvolution($resized, $kernel, 1, 0)) {
            imagedestroy($resized);
            return -3.0;
        }

        // Compute variance over all pixel values
        $sum = 0.0;
        $sumSq = 0.0;
        $totalPixels = $targetW * $targetH;

        for ($y = 0; $y < $targetH; $y++) {
            for ($x = 0; $x < $targetW; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $gray = $rgb & 0xFF; // Already grayscale, R=G=B
                $sum += $gray;
                $sumSq += ($gray * $gray);
            }
        }
        imagedestroy($resized);

        if ($totalPixels === 0) return 0.0;

        $mean = $sum / $totalPixels;
        $variance = ($sumSq / $totalPixels) - ($mean * $mean);
        if ($variance < 0) $variance = 0.0;

        $debugMessages[] = "[GD Laplacian Variance: " . round($variance, 2) . "]";
        return $variance;
    }

    /**
     * Legacy alias for getBlurriness using discrete Laplacian variance
     */
    public static function getBlurriness(string $filePath, array &$debugMessages = []): float
    {
        return self::calculateLaplacianVariance($filePath, $debugMessages);
    }

    /**
     * Run Tesseract OCR and extract raw text from page 1
     * 
     * @param string $filePath Physical path to file
     * @param array $debugMessages Optional array for debug logs
     * @return array ['text' => string, 'confidence' => float]
     */
    public static function runOcr(string $filePath, array &$debugMessages = []): array
    {
        $tessBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'tesseract.exe' : 'tesseract';
        $tessPath = null;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $path = trim(shell_exec("where $tessBin 2>nul") ?: '');
            if ($path) $tessPath = explode("\n", str_replace("\r", "", $path))[0];
        } else {
            $path = trim(shell_exec("which $tessBin 2>/dev/null") ?: '');
            if ($path) $tessPath = $path;
        }

        if (!$tessPath) {
            $debugMessages[] = '[Tesseract binary not installed or found on PATH]';
            return ['text' => '', 'confidence' => 0.0];
        }

        $raster = self::rasterizeToImage($filePath);
        $workImage = $raster['image_path'];
        $isTemp = $raster['is_temp'];

        $outFileBase = tempnam(sys_get_temp_dir(), 'ocr_out_');
        @unlink($outFileBase);

        $cmd = sprintf('"%s" "%s" "%s" --psm 3 -l eng tsv 2>&1', $tessPath, $workImage, $outFileBase);
        $output = shell_exec($cmd);

        if ($isTemp && file_exists($workImage)) {
            @unlink($workImage);
        }

        $tsvFile = $outFileBase . '.tsv';
        $fullText = '';
        $confidences = [];

        if (file_exists($tsvFile)) {
            $handle = fopen($tsvFile, "r");
            if ($handle) {
                fgetcsv($handle, 0, "\t"); // Skip header
                while (($data = fgetcsv($handle, 0, "\t")) !== false) {
                    $conf = isset($data[10]) ? (int)$data[10] : -1;
                    $text = isset($data[11]) ? trim($data[11]) : '';
                    if (!empty($text)) {
                        $fullText .= $text . ' ';
                        if ($conf >= 0) {
                            $confidences[] = $conf;
                        }
                    }
                }
                fclose($handle);
            }
            @unlink($tsvFile);
        }

        $avgConf = count($confidences) > 0 ? (array_sum($confidences) / count($confidences)) : 0.0;
        return [
            'text'       => trim($fullText),
            'confidence' => (float)$avgConf
        ];
    }

    /**
     * Extract fields from OCR text using regex patterns
     * 
     * @param string $ocrText Extracted text from document
     * @param string $documentName Stated document name
     * @return array [field_key => extracted_value]
     */
    public static function extractFields(string $ocrText, string $documentName): array
    {
        $registryPath = __DIR__ . '/../config/document_patterns.php';
        if (!file_exists($registryPath)) {
            return [];
        }

        $patterns = require $registryPath;
        $matchedCategory = null;

        $docLower = strtolower($documentName);
        foreach ($patterns as $categoryKey => $category) {
            foreach ($category['keywords'] as $keyword) {
                if (str_contains($docLower, strtolower($keyword))) {
                    $matchedCategory = $categoryKey;
                    break 2;
                }
            }
        }

        // Fallback: check OCR text for category keywords if documentName is generic
        if (!$matchedCategory) {
            $textLower = strtolower($ocrText);
            foreach ($patterns as $categoryKey => $category) {
                foreach ($category['keywords'] as $keyword) {
                    if (str_contains($textLower, strtolower($keyword))) {
                        $matchedCategory = $categoryKey;
                        break 2;
                    }
                }
            }
        }

        if (!$matchedCategory || empty($patterns[$matchedCategory]['fields'])) {
            // Default to admission letter patterns if unclassified
            $matchedCategory = 'admission_letter';
        }

        $extracted = [];
        foreach ($patterns[$matchedCategory]['fields'] as $fieldKey => $fieldConfig) {
            $pattern = $fieldConfig['pattern'];
            if (preg_match($pattern, $ocrText, $matches)) {
                $extracted[$fieldKey] = trim($matches[1]);
            }
        }

        return $extracted;
    }

    /**
     * Cross-check extracted fields against student database record
     * 
     * @param array $extracted Extracted fields
     * @param array $user Student record from users table
     * @return array ['match_status' => string, 'discrepancies' => array, 'explanation' => string]
     */
    public static function crossCheckWithStudent(array $extracted, array $user): array
    {
        if (empty($extracted)) {
            return [
                'match_status'  => 'unreadable',
                'discrepancies' => ['No identifiable fields could be extracted from document'],
                'explanation'   => 'OCR could not extract structured student metadata. Flagged for manual review.'
            ];
        }

        $discrepancies = [];

        // 1. Cross-check Registration / Matric Number
        $extractedMatric = $extracted['registration_number'] 
            ?? $extracted['matric_number'] 
            ?? $extracted['candidate_number'] 
            ?? null;

        if ($extractedMatric !== null) {
            $cleanUserMatric = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $user['matric_number'] ?? ''));
            $cleanDocMatric  = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $extractedMatric));

            if ($cleanUserMatric !== '' && $cleanDocMatric !== '' && !str_contains($cleanDocMatric, $cleanUserMatric) && !str_contains($cleanUserMatric, $cleanDocMatric)) {
                $discrepancies[] = "Matric/Registration mismatch: Document has '{$extractedMatric}', student record is '{$user['matric_number']}'";
            }
        }

        // 2. Cross-check Candidate Name
        $extractedName = $extracted['candidate_name'] 
            ?? $extracted['child_name'] 
            ?? null;

        if ($extractedName !== null) {
            $userNameParts = preg_split('/[\s,\.\-]+/', strtolower($user['full_name'] ?? ''));
            $userNameParts = array_filter($userNameParts, fn($p) => strlen($p) > 2);

            $docNameLower = strtolower($extractedName);
            $foundMatch = false;

            foreach ($userNameParts as $part) {
                if (str_contains($docNameLower, $part)) {
                    $foundMatch = true;
                    break;
                }
            }

            if (!$foundMatch && !empty($userNameParts)) {
                $discrepancies[] = "Candidate name mismatch: Document has '{$extractedName}', student record is '{$user['full_name']}'";
            }
        }

        // 3. Cross-check Date of Birth if present
        if (!empty($extracted['date_of_birth']) && !empty($user['date_of_birth'])) {
            $userDob = date('Y-m-d', strtotime($user['date_of_birth']));
            $docDobTime = strtotime($extracted['date_of_birth']);
            if ($docDobTime !== false) {
                $docDob = date('Y-m-d', $docDobTime);
                if ($userDob !== $docDob) {
                    $discrepancies[] = "Date of birth discrepancy: Document has '{$extracted['date_of_birth']}', student record is '{$user['date_of_birth']}'";
                }
            }
        }

        if (!empty($discrepancies)) {
            return [
                'match_status'  => 'mismatch',
                'discrepancies' => $discrepancies,
                'explanation'   => implode('; ', $discrepancies)
            ];
        }

        return [
            'match_status'  => 'matched',
            'discrepancies' => [],
            'explanation'   => 'Extracted document fields matched student record.'
        ];
    }
}