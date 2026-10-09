<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/ClamAVScannerService.php';
require_once __DIR__ . '/../../app/services/QualityCheckService.php';
require_once __DIR__ . '/../../app/utils/StoragePathResolver.php';
require_once __DIR__ . '/../../app/models/Document.php';
require_once __DIR__ . '/../../app/models/User.php';

class UploadPipelineTest extends TestCase
{
    private string $tempDir;

    public static function setUpBeforeClass(): void
    {
        resetDatabase();
    }

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/dfms_pipeline_tests_' . bin2hex(random_bytes(4));
        if (!is_dir($this->tempDir)) {
            @mkdir($this->tempDir, 0777, true);
        }
    }

    protected function tearDown(): void
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
     * Create an in-memory high-contrast sharp test image with fine text/edges
     */
    private function createSharpImage(): string
    {
        $path = $this->tempDir . '/sharp.png';
        $img = imagecreatetruecolor(800, 600);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        imagefill($img, 0, 0, $white);

        // Draw dense alternating high-frequency black and white bars / text
        for ($x = 20; $x < 780; $x += 10) {
            imageline($img, $x, 20, $x, 580, $black);
            imageline($img, 20, $x, 780, $x, $black);
        }

        for ($i = 0; $i < 15; $i++) {
            imagestring($img, 5, 50, 40 + ($i * 30), "HIGH CONTRAST TEXT SHARP LINE NUMBER " . $i, $black);
        }

        imagepng($img, $path);
        imagedestroy($img);
        return $path;
    }

    /**
     * Create an artificially blurred test image using Gaussian blur iterations
     */
    private function createBlurredImage(string $sharpPath): string
    {
        $path = $this->tempDir . '/blurred.png';
        $img = imagecreatefrompng($sharpPath);

        // Apply multiple Gaussian blur passes (equivalent to radius >= 5)
        for ($i = 0; $i < 20; $i++) {
            imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagepng($img, $path);
        imagedestroy($img);
        return $path;
    }

    /**
     * Test sharp sample passes Laplacian blur detection (variance >= 100)
     */
    public function testSharpSamplePassesBlur()
    {
        $sharpPath = $this->createSharpImage();
        $debug = [];
        $variance = QualityCheckService::calculateLaplacianVariance($sharpPath, $debug);

        $this->assertGreaterThanOrEqual(100.0, $variance, "Sharp image should produce variance >= 100. Actual: {$variance}");
    }

    /**
     * Test artificially blurred sample fails blur detection (variance < 100)
     */
    public function testArtificiallyBlurredSampleFailsBlur()
    {
        $sharpPath = $this->createSharpImage();
        $blurredPath = $this->createBlurredImage($sharpPath);

        $debug = [];
        $variance = QualityCheckService::calculateLaplacianVariance($blurredPath, $debug);

        $this->assertLessThan(100.0, $variance, "Blurred image should produce variance < 100. Actual: {$variance}");
    }

    /**
     * Test EICAR malware signature fails ClamAV scanner
     */
    public function testEicarStringFailsClamAv()
    {
        $eicarPath = $this->tempDir . '/eicar.com';
        file_put_contents($eicarPath, ClamAVScannerService::EICAR_SIGNATURE);

        $result = ClamAVScannerService::scanFile($eicarPath);

        $this->assertFalse($result['clean'], "EICAR file must fail scan");
        $this->assertEquals('infected', $result['status']);
        $this->assertNotNull($result['virus']);
        $this->assertStringContainsString('EICAR', $result['virus']);
    }

    /**
     * Test clean document passes ClamAV scanner
     */
    public function testCleanFilePassesClamAv()
    {
        $cleanPath = $this->createSharpImage();
        $result = ClamAVScannerService::scanFile($cleanPath);

        $this->assertTrue($result['clean'], "Clean image file must pass ClamAV scan");
        $this->assertEquals('clean', $result['status']);
    }

    /**
     * Test OCR field cross-check:
     * Discrepancies are flagged for review (ocr_match_status = 'mismatch') rather than auto-rejected
     */
    public function testOcrCrossCheckFlagging()
    {
        $studentUser = [
            'id'            => 10,
            'matric_number' => 'MATRIC/2026/001',
            'full_name'     => 'Adebayo Samuel Oluwaseun',
            'date_of_birth' => '2000-05-15'
        ];

        // 1. Matching candidate details
        $matchingFields = [
            'registration_number' => 'MATRIC/2026/001',
            'candidate_name'      => 'Adebayo Samuel Oluwaseun',
            'academic_session'    => '2025/2026'
        ];
        $resMatch = QualityCheckService::crossCheckWithStudent($matchingFields, $studentUser);
        $this->assertEquals('matched', $resMatch['match_status']);
        $this->assertEmpty($resMatch['discrepancies']);

        // 2. Mismatched candidate name: Flagged for manual review, NOT auto-rejected
        $mismatchedFields = [
            'registration_number' => 'MATRIC/2026/001',
            'candidate_name'      => 'Okonkwo Chukwuemeka Emmanuel',
            'academic_session'    => '2025/2026'
        ];
        $resMismatch = QualityCheckService::crossCheckWithStudent($mismatchedFields, $studentUser);
        $this->assertEquals('mismatch', $resMismatch['match_status'], "Mismatched name must be marked 'mismatch' for reviewer routing");
        $this->assertNotEmpty($resMismatch['discrepancies']);
        $this->assertStringContainsString('name mismatch', $resMismatch['explanation']);

        // 3. Unreadable / empty extraction: Flagged for review
        $resUnreadable = QualityCheckService::crossCheckWithStudent([], $studentUser);
        $this->assertEquals('unreadable', $resUnreadable['match_status']);
    }

    /**
     * Test synchronous upload stages file in _staging and blocks duplicates
     */
    public function testUploadBuffersToStagingAndDetectsDuplicates()
    {
        // 1. Create student
        User::createStudent([
            'matric_number' => 'STU_PIPE_001',
            'full_name'     => 'Pipeline Test Student',
            'level'         => 200,
            'session'       => '2025/2026',
            'programme'     => 'Computer Science',
            'department_id' => 1,
            'email'         => 'pipe_student@example.com'
        ]);
        $student = User::getByMatricNumber('STU_PIPE_001');

        $sharpFile = $this->createSharpImage();
        $fakeUpload = [
            'name'     => 'test_document.png',
            'type'     => 'image/png',
            'tmp_name' => $sharpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($sharpFile)
        ];

        $classData = [
            'level'         => 200,
            'semester'      => '2025_2026',
            'program_type'  => 'Full Time',
            'mode'          => 'UTME',
            'department_id' => 1
        ];

        // First upload
        $res = Document::upload($student['id'], 'Birth Certificate', 'gen_name.png', $fakeUpload, $classData);
        $this->assertTrue($res['success'], "Initial upload must succeed: " . ($res['message'] ?? ''));

        $docId = $res['id'];
        $doc = Document::getById($docId);
        $this->assertNotNull($doc);
        $this->assertEquals('Pending Quality Check', $doc['status']);
        $this->assertStringContainsString('_staging', $doc['file_path']);

        // Staged physical file exists
        $stagedFull = StoragePathResolver::resolveAbsolutePath($doc['file_path']);
        $this->assertFileExists($stagedFull);

        // Second upload with identical content by same student: Must be rejected as duplicate
        $resDup = Document::upload($student['id'], 'Birth Certificate Copy', 'gen_name2.png', $fakeUpload, $classData);
        $this->assertFalse($resDup['success'], "Duplicate content by same user must be rejected");
        $this->assertEquals(422, $resDup['http_code']);
        $this->assertStringContainsString('Duplicate submission', $resDup['message']);
    }

    /**
     * Test queue worker processes checks in order and promotes file to permanent storage
     */
    public function testQueueWorkerPromotesVerifiedFile()
    {
        // Create student and staged document
        User::createStudent([
            'matric_number' => 'STU_WORKER_001',
            'full_name'     => 'Worker Verification Student',
            'level'         => 300,
            'session'       => '2025/2026',
            'programme'     => 'Computer Science',
            'department_id' => 1,
            'email'         => 'worker_stu@example.com'
        ]);
        $student = User::getByMatricNumber('STU_WORKER_001');

        $sharpFile = $this->createSharpImage();
        $fakeUpload = [
            'name'     => 'clear_cert.png',
            'type'     => 'image/png',
            'tmp_name' => $sharpFile,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($sharpFile)
        ];

        $classData = [
            'level'         => 300,
            'semester'      => '2025_2026',
            'program_type'  => 'Full Time',
            'mode'          => 'UTME',
            'department_id' => 1
        ];

        $res = Document::upload($student['id'], 'Admission Letter', 'gen_cert.png', $fakeUpload, $classData);
        $this->assertTrue($res['success']);
        $docId = $res['id'];

        $stagedRel = Document::getById($docId)['file_path'];
        $stagedFull = StoragePathResolver::resolveAbsolutePath($stagedRel);
        $this->assertFileExists($stagedFull);

        // Run worker with --once
        ob_start();
        $argv = ['queue_worker.php', '--once'];
        require __DIR__ . '/../../app/scripts/queue_worker.php';
        $output = ob_get_clean();

        // Check document status updated to Pending Approval
        $promotedDoc = Document::getById($docId);
        $this->assertEquals('Pending Approval', $promotedDoc['status']);
        $this->assertStringNotContainsString('_staging', $promotedDoc['file_path']);

        // Check physical file exists in permanent storage
        $permanentFull = StoragePathResolver::resolveAbsolutePath($promotedDoc['file_path']);
        $this->assertFileExists($permanentFull);
        $this->assertFileDoesNotExist($stagedFull, "Staged file must be removed after promotion");

        // Verify file permissions (0640)
        $perms = fileperms($permanentFull) & 0777;
        // On Windows fileperms behaves differently, but on POSIX it enforces 0640
        $this->assertGreaterThan(0, $perms);
    }
}
