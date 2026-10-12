<?php
/**
 * DFMS Blur Calibration Evaluation Harness
 * 
 * Measures discrete Laplacian variance distribution across sharp document images,
 * Gaussian defocus blur levels, and motion blur to empirically validate the
 * threshold sigma_Lap^2 >= 100 stated in the thesis.
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/QualityCheckService.php';

class BlurCalibrationHarness
{
    private string $tempDir;

    public function __construct()
    {
        $this->tempDir = sys_get_temp_dir() . '/dfms_blur_eval_' . bin2hex(random_bytes(4));
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
     * Synthesize a base academic certificate image (1200x800) with text, borders, seals
     */
    private function createBaseCertificate(): GdImage
    {
        $w = 1200;
        $h = 800;
        $img = imagecreatetruecolor($w, $h);

        // White background
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 15, 23, 42);
        $navy = imagecolorallocate($img, 1, 1, 122);
        $gold = imagecolorallocate($img, 180, 140, 20);
        $gray = imagecolorallocate($img, 100, 116, 139);

        imagefilledrectangle($img, 0, 0, $w, $h, $white);

        // Decorative borders
        imagesetthickness($img, 4);
        imagerectangle($img, 20, 20, $w - 20, $h - 20, $navy);
        imagesetthickness($img, 2);
        imagerectangle($img, 26, 26, $w - 26, $h - 26, $gold);
        imagesetthickness($img, 1);
        imagerectangle($img, 30, 30, $w - 30, $h - 30, $navy);

        // Header and institution lines
        imagestring($img, 5, 420, 60, "UNIVERSITY OF IBADAN", $navy);
        imagestring($img, 4, 450, 90, "FACULTY OF SCIENCE", $black);
        imagestring($img, 3, 400, 115, "DEPARTMENT OF COMPUTER SCIENCE", $gray);

        imageline($img, 200, 145, $w - 200, 145, $gold);

        // Document title
        imagestring($img, 5, 430, 175, "STATEMENT OF RESULTS", $navy);

        // Student details in crisp monospace grid
        $details = [
            "NAME OF STUDENT:         OLAWALE ADEDAYO JOHNSON",
            "MATRICULATION NO:        123456",
            "FACULTY:                 SCIENCE",
            "DEPARTMENT:              COMPUTER SCIENCE",
            "PROGRAMME:               B.Sc. COMPUTER SCIENCE",
            "DEGREE AWARDED:          FIRST CLASS HONOURS",
            "CUMULATIVE GPA:          6.85 / 7.00",
            "SESSION OF GRADUATION:   2023/2024",
            "DATE OF SENATE APPROVAL: 24TH OCTOBER, 2024",
            "CERTIFICATE SERIAL NO:   UI/SCI/CSC/2024/00842"
        ];

        $y = 230;
        foreach ($details as $line) {
            imagestring($img, 4, 150, $y, $line, $black);
            $y += 35;
        }

        // Table simulation for courses
        imagerectangle($img, 150, 600, $w - 150, 700, $black);
        imageline($img, 150, 630, $w - 150, 630, $black);
        imagestring($img, 4, 160, 608, "COURSE CODE | COURSE TITLE                       | UNITS | GRADE", $navy);
        imagestring($img, 4, 160, 642, "CSC 411     | ADVANCED ALGORITHM DESIGN          |   3   |   A  ", $black);
        imagestring($img, 4, 160, 668, "CSC 421     | CRYPTOGRAPHY & NETWORK SECURITY   |   3   |   A  ", $black);

        // Official signature placeholders
        imageline($img, 200, 755, 450, 755, $black);
        imagestring($img, 3, 240, 760, "HEAD OF DEPARTMENT", $black);

        imageline($img, $w - 450, 755, $w - 200, 755, $black);
        imagestring($img, 3, $w - 400, 760, "REGISTRAR / SENATE", $black);

        return $img;
    }

    /**
     * Apply Gaussian defocus blur passes
     */
    private function applyGaussianBlur(GdImage $source, int $passes): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $blurred = imagecreatetruecolor($w, $h);
        imagecopy($blurred, $source, 0, 0, 0, 0, $w, $h);

        for ($i = 0; $i < $passes; $i++) {
            imagefilter($blurred, IMG_FILTER_GAUSSIAN_BLUR);
        }

        return $blurred;
    }

    /**
     * Apply 2D camera shake / motion blur kernel
     */
    private function applyMotionBlur(GdImage $source, int $passes = 3): GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $blurred = imagecreatetruecolor($w, $h);
        imagecopy($blurred, $source, 0, 0, 0, 0, $w, $h);

        $motionKernel = [
            [1, 2, 1],
            [2, 4, 2],
            [1, 2, 1]
        ];

        for ($p = 0; $p < $passes; $p++) {
            imageconvolution($blurred, $motionKernel, 16, 0);
        }

        return $blurred;
    }

    public function run(): array
    {
        $baseImg = $this->createBaseCertificate();
        $results = [];

        $testCases = [
            [
                'name' => 'Sharp Pristine Certificate',
                'type' => 'sharp',
                'description' => 'Original high-contrast vector rasterization (300 DPI layout)',
                'generator' => fn() => $baseImg,
                'expected_pass' => true
            ],
            [
                'name' => 'Mild Defocus Blur (Pass 1)',
                'type' => 'gaussian_defocus',
                'description' => 'Single pass Gaussian blur (slight camera misfocus, legible text)',
                'generator' => fn() => $this->applyGaussianBlur($baseImg, 1),
                'expected_pass' => true
            ],
            [
                'name' => 'Moderate Defocus Blur (Pass 2)',
                'type' => 'gaussian_defocus',
                'description' => 'Two pass Gaussian blur (soft edges, clearly legible)',
                'generator' => fn() => $this->applyGaussianBlur($baseImg, 2),
                'expected_pass' => true
            ],
            [
                'name' => 'Severe Defocus Blur (Pass 5)',
                'type' => 'gaussian_defocus',
                'description' => 'Five pass Gaussian blur (heavily degraded text, illegible)',
                'generator' => fn() => $this->applyGaussianBlur($baseImg, 5),
                'expected_pass' => false
            ],
            [
                'name' => 'Heavy Defocus Blur (Pass 8)',
                'type' => 'gaussian_defocus',
                'description' => 'Eight pass Gaussian blur (severe defocus, characters unreadable)',
                'generator' => fn() => $this->applyGaussianBlur($baseImg, 8),
                'expected_pass' => false
            ],
            [
                'name' => 'Extreme Defocus Blur (Pass 12)',
                'type' => 'gaussian_defocus',
                'description' => 'Twelve pass Gaussian blur (unusable blurry blob)',
                'generator' => fn() => $this->applyGaussianBlur($baseImg, 12),
                'expected_pass' => false
            ],
            [
                'name' => 'Mild Camera Tremor (Pass 2)',
                'type' => 'motion_blur',
                'description' => 'Slight camera shake during smartphone capture',
                'generator' => fn() => $this->applyMotionBlur($baseImg, 2),
                'expected_pass' => true
            ],
            [
                'name' => 'Moderate Motion Blur (Pass 6)',
                'type' => 'motion_blur',
                'description' => 'Significant hand movement during shutter exposure',
                'generator' => fn() => $this->applyMotionBlur($baseImg, 6),
                'expected_pass' => false
            ],
            [
                'name' => 'Severe Motion Blur (Pass 10)',
                'type' => 'motion_blur',
                'description' => 'Rapid hand jerk causing smearing of characters',
                'generator' => fn() => $this->applyMotionBlur($baseImg, 10),
                'expected_pass' => false
            ],
        ];

        foreach ($testCases as $idx => $tc) {
            $gdImg = $tc['generator']();
            $path = $this->tempDir . '/test_' . $idx . '.png';
            imagepng($gdImg, $path);

            $debug = [];
            $variance = QualityCheckService::calculateLaplacianVariance($path, $debug);
            $passed = ($variance >= 100.0);

            $results[] = [
                'name'          => $tc['name'],
                'type'          => $tc['type'],
                'description'   => $tc['description'],
                'variance'      => round($variance, 2),
                'threshold'     => 100.0,
                'decision'      => $passed ? 'PASS' : 'REJECT',
                'expected_pass' => $tc['expected_pass'],
                'concordant'    => ($passed === $tc['expected_pass'])
            ];

            if ($gdImg !== $baseImg) {
                imagedestroy($gdImg);
            }
        }

        imagedestroy($baseImg);
        return $results;
    }
}

// Standalone CLI execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    echo "========================================================================\n";
    echo "DFMS Discrete Laplacian Variance Blur Calibration Benchmark\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")\n";
    echo "Threshold: sigma_Lap^2 >= 100.0 (Thesis Ch. 1 & 2)\n";
    echo "========================================================================\n\n";

    $harness = new BlurCalibrationHarness();
    $data = $harness->run();

    printf("%-35s | %-16s | %-10s | %-8s | %s\n", "Test Image Condition", "Degradation", "Variance", "Result", "Concordance");
    echo str_repeat("-", 85) . "\n";

    foreach ($data as $row) {
        printf(
            "%-35s | %-16s | %10.2f | %-8s | %s\n",
            $row['name'],
            $row['type'],
            $row['variance'],
            $row['decision'],
            $row['concordant'] ? "YES (Correct)" : "NO (Misclassified)"
        );
    }

    echo "\nSummary Statistics:\n";
    $sharpVariances = array_column(array_filter($data, fn($d) => $d['type'] === 'sharp' || ($d['expected_pass'] && $d['type'] !== 'sharp')), 'variance');
    $blurVariances = array_column(array_filter($data, fn($d) => !$d['expected_pass']), 'variance');

    $meanSharp = count($sharpVariances) ? array_sum($sharpVariances) / count($sharpVariances) : 0;
    $meanBlur = count($blurVariances) ? array_sum($blurVariances) / count($blurVariances) : 0;

    printf("  - Legible / Sharp Images Mean Variance:   %.2f\n", $meanSharp);
    printf("  - Degraded / Blurred Images Mean Variance: %.2f\n", $meanBlur);
    printf("  - Mathematical Separation Margin:         %.2f\n", $meanSharp - $meanBlur);
    printf("  - Threshold Discrimination Accuracy:      %.1f%%\n", (count(array_filter($data, fn($d) => $d['concordant'])) / count($data)) * 100);
}
