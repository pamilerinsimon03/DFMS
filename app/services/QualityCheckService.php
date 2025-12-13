<?php

class QualityCheckService
{
    /**
     * Checks if a file is an image.
     */
    public static function isImage($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) return false;
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);
        
        $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array($mime, $imageMimes);
    }

    /**
     * Calculates blur score using the Laplacian Variance method.
     * 
     * @param string $filePath Path to the image file
     * @param array &$debug_messages Array to store debug info
     * @return float Score (higher is sharper). Returns negative on error.
     */
    public static function getBlurriness($filePath, array &$debug_messages)
    {
        // Strategy 1: Imagick (Preferred - Fast & Accurate)
        if (extension_loaded('imagick')) {
            try {
                $image = new Imagick($filePath);
                
                // transform to grayscale to simplify analysis
                $image->setImageType(Imagick::IMGTYPE_GRAYSCALE);
                
                // Laplacian Kernel
                //  -1 -1 -1
                //  -1  8 -1
                //  -1 -1 -1
                $matrix = [
                    [-1, -1, -1],
                    [-1,  8, -1],
                    [-1, -1, -1]
                ];
                
                $kernel = \ImagickKernel::fromMatrix($matrix);
                $image->convolveImage($kernel);
                
                // Calculate standard deviation of the convolved image
                $stats = $image->getImageChannelStatistics();
                
                // Different Imagick versions return different keys (0 vs user_defined)
                // We use CHANNEL_DEFAULT which usually covers all active channels (Gray)
                $stdDev = 0;
                if (isset($stats[Imagick::CHANNEL_DEFAULT])) {
                     $stdDev = $stats[Imagick::CHANNEL_DEFAULT]['standardDeviation'];
                } elseif (isset($stats[0])) {
                     $stdDev = $stats[0]['standardDeviation'];
                }

                $image->clear();
                $image->destroy();
                
                $debug_messages[] = "[Debug: Imagick Laplacian StdDev: " . round($stdDev, 2) . "]";
                return $stdDev;
                
            } catch (Exception $e) {
                $debug_messages[] = "[Debug: Imagick failed: " . $e->getMessage() . ". Falling back to GD.]";
            }
        }
        
        // Strategy 2: GD (Fallback - Slower, requires memory management)
        return self::getBlurrinessGD($filePath, $debug_messages);
    }

    /**
     * Fallback GD implementation for blur detection
     */
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

        // Resize strict limit for performance (max 400px longest side)
        // Smaller images are faster to loop through in PHP
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

        // Calculate Variance
        $sum = 0;
        $sumSq = 0;
        $pixelCount = 0;
        
        // Optimize loop: Skip step to sample pixels instead of all (faster approximation)
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

    /**
     * Calculates OCR confidence using Tesseract.
     * Uses preprocessing for better accuracy.
     */
    public static function getOcrConfidence($filePath, array &$debug_messages)
    {
        // Locate Tesseract
        $tessBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'tesseract.exe' : 'tesseract';
        // Check PATH
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

        // Preparation
        $workImage = $filePath;
        $isTemp = false;

        // Preprocessing with Imagick if available
        if (extension_loaded('imagick')) {
            try {
                $im = new Imagick();
                
                // specific PDF handling
                $mime = mime_content_type($filePath);
                if (strpos($mime, 'pdf') !== false) {
                    $im->setResolution(300, 300); // Good balance for OCR
                    $im->readImage($filePath . '[0]'); // First page only
                    $im->setImageFormat('png');
                } else {
                    $im->readImage($filePath);
                }

                // Enhancements for OCR
                $im->setImageType(Imagick::IMGTYPE_GRAYSCALE);
                $im->contrastImage(1); // Increase contrast
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

        // Tesseract Command Construction
        // --psm 3 (Auto page segmentation)
        // --oem 1 (Neural Net LSTM - Faster/Better if data present)
        // tsv output for confidence extraction
        $outFileBase = tempnam(sys_get_temp_dir(), 'ocr_out_');
        // Unlink because tesseract appends extension
        @unlink($outFileBase);

        $cmd = sprintf(
            '"%s" "%s" "%s" tsv --psm 3 -l eng 2>&1',
            $tessPath,
            $workImage,
            $outFileBase
        );
        $debug_messages[] = "[Debug: Executing $cmd]";
        
        $output = shell_exec($cmd);
        
        // Cleanup temp input if we created one
        if ($isTemp && file_exists($workImage)) {
            @unlink($workImage);
        }

        $tsvFile = $outFileBase . '.tsv';
        if (!file_exists($tsvFile)) {
            $debug_messages[] = '[Debug: No TSV output generated. Tesseract error?]';
            return 0;
        }

        // Parse TSV
        // Level	Page_num	Block_num	Par_num	Line_num	Word_num	Left	Top	Width	Height	Conf	Text
        $handle = fopen($tsvFile, "r");
        $confidences = [];
        $header = fgetcsv($handle, 0, "\t"); // skip header
        
        while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
            // Index 10 is usually Conf (0-100), check header if paranoid, but standard is fixed.
            // Check if word is not empty (sometimes structural rows have -1 conf)
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