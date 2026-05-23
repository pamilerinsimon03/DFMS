<?php

class QualityCheckService
{
    public static function isImage($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) return false;
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($mime, $imageMimes);
    }

    public static function getBlurriness($filePath, array &$debug_messages)
    {
        if (extension_loaded('imagick')) {
            try {
                $image = new Imagick($filePath);
                
                $image->setImageType(Imagick::IMGTYPE_GRAYSCALE);
                
                $matrix = [
                    [-1, -1, -1],
                    [-1,  8, -1],
                    [-1, -1, -1]
                ];
                
                $image->convolveImage($matrix);
                
                $stats = $image->getImageChannelStatistics();
                
                $stdDev = 0;
                if (isset($stats[Imagick::CHANNEL_DEFAULT]) && isset($stats[Imagick::CHANNEL_DEFAULT]['standardDeviation'])) {
                     $stdDev = (float)$stats[Imagick::CHANNEL_DEFAULT]['standardDeviation'];
                } elseif (isset($stats[0]) && isset($stats[0]['standardDeviation'])) {
                     $stdDev = (float)$stats[0]['standardDeviation'];
                }

                $image->clear();
                $image->destroy();
                
                $debug_messages[] = "[Debug: Imagick Laplacian StdDev: " . round($stdDev, 2) . "]";
                return $stdDev;
                
            } catch (Exception $e) {
                $debug_messages[] = "[Debug: Imagick failed: " . $e->getMessage() . ". Falling back to GD.]";
            }
        }
        
        return self::getBlurrinessGD($filePath, $debug_messages);
    }

    private static function getBlurrinessGD($filePath, array &$debug_messages)
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imageconvolution')) {
            $debug_messages[] = '[Debug: GD extension with convolution not available.]';
            return -1;
        }

        $data = @file_get_contents($filePath);
        if (!$data) return -2;
        
        $img = @imagecreatefromstring($data);
        if (!$img) {
            $debug_messages[] = '[Debug: GD failed to load image.]';
            return -2;
        }

        $maxDim = 400;
        $w = imagesx($img);
        $h = imagesy($img);
        
        if (max($w, $h) > $maxDim) {
            $scale = $maxDim / max($w, $h);
            $newW = (int)($w * $scale);
            $newH = (int)($h * $scale);
            $small = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($small, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagedestroy($img);
            $img = $small;
            $w = $newW;
            $h = $newH;
        }

        $kernel = [[-1, -1, -1], [-1, 8, -1], [-1, -1, -1]];
        if (!imageconvolution($img, $kernel, 1, 0)) {
            imagedestroy($img);
            return -3;
        }

        $sum = 0;
        $sumSq = 0;
        $pixelCount = 0;
        
        $step = 2; 
        
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                $rgb = imagecolorat($img, $x, $y);
                $gray = (($rgb >> 16) & 0xFF) * 0.299 + 
                        (($rgb >> 8) & 0xFF) * 0.587 + 
                        ($rgb & 0xFF) * 0.114;
                
                $sum += $gray;
                $sumSq += ($gray * $gray);
                $pixelCount++;
            }
        }
        imagedestroy($img);

        if ($pixelCount === 0) return -4;

        $mean = $sum / $pixelCount;
        $variance = ($sumSq / $pixelCount) - ($mean * $mean);
        $stdDev = sqrt($variance);

        $debug_messages[] = '[Debug: GD (Resized) Laplacian StdDev: ' . round($stdDev, 2) . ']';
        return $stdDev;
    }

    public static function getOcrConfidence($filePath, array &$debug_messages)
    {
        $tessBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'tesseract.exe' : 'tesseract';
        $tessPath = null;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $path = trim(shell_exec("where $tessBin 2>nul"));
            if ($path) $tessPath = explode("\n", $path)[0];
        } else {
             $path = trim(shell_exec("which $tessBin"));
             if ($path) $tessPath = $path;
        }

        if (!$tessPath) {
            $debug_messages[] = '[Debug: Tesseract binary not found.]';
            return -1;
        }

        $workImage = $filePath;
        $isTemp = false;

        if (extension_loaded('imagick')) {
            try {
                $im = new Imagick();
                
                $mime = mime_content_type($filePath);
                if (strpos($mime, 'pdf') !== false) {
                    $im->setResolution(300, 300); // Good balance for OCR
                    $im->readImage($filePath . '[0]'); // First page only
                    $im->setImageFormat('png');
                } else {
                    $im->readImage($filePath);
                }

                $im->setImageType(Imagick::IMGTYPE_GRAYSCALE);
                $im->contrastImage(true); // Increase contrast
                // $im->adaptiveThresholdImage(10, 10, 0); // Local binarization (can be aggressive, optional)
                $im->despeckleImage(); // Remove noise

                $tempPath = tempnam(sys_get_temp_dir(), 'ocr_prep_') . '.png';
                $im->writeImage($tempPath);
                $im->clear();
                $im->destroy();
                
                $workImage = $tempPath;
                $isTemp = true;
                $debug_messages[] = "[Debug: Preprocessed image created at $tempPath]";
            } catch (Exception $e) {
                $debug_messages[] = "[Debug: Preprocessing failed: " . $e->getMessage() . ". Using original.]";
            }
        }

        $outFileBase = tempnam(sys_get_temp_dir(), 'ocr_out_');
        @unlink($outFileBase);

        $cmd = sprintf(
            '"%s" "%s" "%s" tsv --psm 3 -l eng 2>&1',
            $tessPath,
            $workImage,
            $outFileBase
        );
        $debug_messages[] = "[Debug: Executing $cmd]";
        
        $output = shell_exec($cmd);
        
        if ($isTemp && file_exists($workImage)) {
            @unlink($workImage);
        }

        $tsvFile = $outFileBase . '.tsv';
        if (!file_exists($tsvFile)) {
            $debug_messages[] = '[Debug: No TSV output generated. Tesseract error?]';
            return 0;
        }

        $handle = fopen($tsvFile, "r");
        $confidences = [];
        $header = fgetcsv($handle, 0, "\t"); // skip header
        
        while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
            $conf = isset($data[10]) ? (int)$data[10] : -1;
            $text = isset($data[11]) ? trim($data[11]) : '';
            
            if ($conf >= 0 && !empty($text)) {
                $confidences[] = $conf;
            }
        }
        fclose($handle);
        @unlink($tsvFile);

        if (count($confidences) === 0) {
            $debug_messages[] = '[Debug: No text detected with confidence.]';
            return 0; // Or treat as legible if strictly checking for blur? No, low confident text = bad scan.
        }

        $avgConf = array_sum($confidences) / count($confidences);
        $debug_messages[] = '[Debug: Average OCR Confidence: ' . round($avgConf) . '%]';
        
        return $avgConf;
    }

    public static function logDebugMessages($user_id, $messages)
    {
        $logPath = __DIR__ . '/../logs/upload_debug.log';
        if (!is_dir(dirname($logPath))) mkdir(dirname($logPath), 0755, true);
        
        $entry = date('[Y-m-d H:i:s] ') . "User $user_id: " . implode(' | ', $messages) . PHP_EOL;
        file_put_contents($logPath, $entry, FILE_APPEND);
    }
}