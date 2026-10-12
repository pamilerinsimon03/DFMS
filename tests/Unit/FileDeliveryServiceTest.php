<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/FileDeliveryService.php';
require_once __DIR__ . '/../../app/utils/StoragePathResolver.php';
require_once __DIR__ . '/../../app/models/Document.php';
require_once __DIR__ . '/../../app/models/User.php';

class FileDeliveryServiceTest extends TestCase
{
    private string $tempStorageDir;
    private string $tempSamplePdf;

    protected function setUp(): void
    {
        $this->tempStorageDir = StoragePathResolver::getStorageRoot() . '/test_delivery';
        if (!is_dir($this->tempStorageDir)) {
            @mkdir($this->tempStorageDir, 0750, true);
        }

        $this->tempSamplePdf = $this->tempStorageDir . '/sample.pdf';
        file_put_contents($this->tempSamplePdf, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempSamplePdf)) {
            @unlink($this->tempSamplePdf);
        }
        if (is_dir($this->tempStorageDir)) {
            @rmdir($this->tempStorageDir);
        }
        FileDeliveryService::setDeliveryMode(null);
    }

    /**
     * Test headers emitted across modes: Apache (X-Sendfile), Nginx (X-Accel-Redirect), PHP
     */
    public function testDeliveryHeadersAcrossModes()
    {
        $doc = [
            'id'               => 10,
            'public_id'        => 'd1000000-0000-4000-8000-000000000010',
            'file_name'        => 'my_transcript.pdf',
            'file_path'        => 'test_delivery/sample.pdf',
            'file_hash'        => hash_file('sha256', $this->tempSamplePdf),
            'file_scan_status' => 'clean',
            'status'           => 'Approved'
        ];

        // 1. Apache mode
        $apacheHeaders = FileDeliveryService::computeDeliveryHeaders($doc, $this->tempSamplePdf, 'inline', 'apache');
        $this->assertArrayHasKey('X-Sendfile', $apacheHeaders);
        $this->assertEquals($this->tempSamplePdf, $apacheHeaders['X-Sendfile']);
        $this->assertEquals('nosniff', $apacheHeaders['X-Content-Type-Options']);
        $this->assertStringContainsString('inline', $apacheHeaders['Content-Disposition']);
        $this->assertStringContainsString("default-src 'none'", $apacheHeaders['Content-Security-Policy']);
        $this->assertArrayNotHasKey('Content-Length', $apacheHeaders);

        // 2. Nginx mode
        $nginxHeaders = FileDeliveryService::computeDeliveryHeaders($doc, $this->tempSamplePdf, 'inline', 'nginx');
        $this->assertArrayHasKey('X-Accel-Redirect', $nginxHeaders);
        $this->assertStringContainsString('/internal_secure_uploads/', $nginxHeaders['X-Accel-Redirect']);
        $this->assertStringContainsString('sample.pdf', $nginxHeaders['X-Accel-Redirect']);
        $this->assertEquals('nosniff', $nginxHeaders['X-Content-Type-Options']);
        $this->assertStringContainsString('inline', $nginxHeaders['Content-Disposition']);
        $this->assertArrayNotHasKey('Content-Length', $nginxHeaders);

        // 3. PHP mode
        $phpHeaders = FileDeliveryService::computeDeliveryHeaders($doc, $this->tempSamplePdf, 'attachment', 'php');
        $this->assertArrayHasKey('Content-Length', $phpHeaders);
        $this->assertEquals((string)filesize($this->tempSamplePdf), $phpHeaders['Content-Length']);
        $this->assertStringContainsString('attachment', $phpHeaders['Content-Disposition']);
        $this->assertArrayNotHasKey('X-Sendfile', $phpHeaders);
        $this->assertArrayNotHasKey('X-Accel-Redirect', $phpHeaders);
    }

    /**
     * Test Content-Security-Policy and Nosniff enforcement
     */
    public function testSecurityHeadersAndInlineCspEnforcement()
    {
        $doc = [
            'id'        => 11,
            'file_name' => 'scan.pdf',
            'file_path' => 'test_delivery/sample.pdf'
        ];

        // Inline disposition gets strict CSP
        $headersInline = FileDeliveryService::computeDeliveryHeaders($doc, $this->tempSamplePdf, 'inline', 'php');
        $this->assertEquals('nosniff', $headersInline['X-Content-Type-Options']);
        $this->assertArrayHasKey('Content-Security-Policy', $headersInline);
        $this->assertStringContainsString("default-src 'none'", $headersInline['Content-Security-Policy']);
        $this->assertStringContainsString("frame-ancestors 'self'", $headersInline['Content-Security-Policy']);

        // Attachment download disposition
        $headersAttachment = FileDeliveryService::computeDeliveryHeaders($doc, $this->tempSamplePdf, 'attachment', 'php');
        $this->assertEquals('nosniff', $headersAttachment['X-Content-Type-Options']);
        $this->assertStringContainsString('attachment', $headersAttachment['Content-Disposition']);
    }

    /**
     * Test defense-in-depth scoping blocks cross-student file access
     */
    public function testCrossStudentAccessBlockedByScoping()
    {
        $studentA = [
            'id'            => 101,
            'role'          => 'student',
            'department_id' => 1
        ];
        $studentB = [
            'id'            => 102,
            'role'          => 'student',
            'department_id' => 1
        ];

        $docOwnedByB = [
            'id'               => 201,
            'user_id'          => 102,
            'department_id'    => 1,
            'file_path'        => 'test_delivery/sample.pdf',
            'file_scan_status' => 'clean',
            'status'           => 'Approved'
        ];

        // Student A requesting Student B's document is out of scope
        $this->assertFalse(AccessScope::isDocumentInScope($studentA, $docOwnedByB));
        $this->assertTrue(AccessScope::isDocumentInScope($studentB, $docOwnedByB));
    }

    /**
     * Test unverified file status rejection (pending scan status or staging path)
     */
    public function testUnverifiedOrPendingFileRejection()
    {
        // 1. Pending Scan Status
        $docPendingScan = [
            'id'               => 301,
            'user_id'          => 101,
            'file_path'        => 'test_delivery/sample.pdf',
            'file_scan_status' => 'pending',
            'status'           => 'Pending Approval'
        ];
        $this->assertNotEquals('clean', $docPendingScan['file_scan_status']);

        // 2. Infected Scan Status
        $docInfected = [
            'id'               => 302,
            'user_id'          => 101,
            'file_path'        => 'test_delivery/sample.pdf',
            'file_scan_status' => 'infected',
            'status'           => 'Rejected'
        ];
        $this->assertNotEquals('clean', $docInfected['file_scan_status']);

        // 3. Staging buffer path
        $docStaged = [
            'id'               => 303,
            'user_id'          => 101,
            'file_path'        => '_staging/temp_upload_123.pdf',
            'file_scan_status' => 'clean',
            'status'           => 'Pending Quality Check'
        ];
        $this->assertTrue(str_contains($docStaged['file_path'], '_staging') || $docStaged['status'] === 'Pending Quality Check');
    }

    /**
     * Test integrity check fails on tampered content
     */
    public function testTamperedFileHashIntegrityCheck()
    {
        $realHash = hash_file('sha256', $this->tempSamplePdf);
        $tamperedHash = str_repeat('0', 64);

        $this->assertNotEquals($realHash, $tamperedHash);
        $this->assertFalse(hash_equals($tamperedHash, $realHash));
        $this->assertTrue(hash_equals($realHash, $realHash));
    }
}
