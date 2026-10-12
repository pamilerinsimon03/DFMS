<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/utils/StoragePathResolver.php';
require_once __DIR__ . '/../../app/models/Document.php';
require_once __DIR__ . '/../../app/models/User.php';

class StorageIsolationTest extends TestCase
{
    public function testStorageRootOutsideWebRoot()
    {
        $storageRoot = StoragePathResolver::getStorageRoot();
        $repoRoot = realpath(__DIR__ . '/../..');

        $this->assertNotEmpty($storageRoot);
        
        $normStorage = rtrim(str_replace('\\', '/', realpath($storageRoot) ?: $storageRoot), '/');
        $normRepo = rtrim(str_replace('\\', '/', $repoRoot), '/');

        // Storage root should not be inside repository root
        $this->assertFalse(
            str_starts_with($normStorage, $normRepo . '/'),
            "STORAGE_ROOT ({$normStorage}) must not be inside repo root ({$normRepo})."
        );
    }

    public function testDeterministicHmacNaming()
    {
        $content = "UNIVERSITY_OF_IBADAN_SAMPLE_DOCUMENT_CONTENT_" . microtime(true);
        $salt = StoragePathResolver::getStorageSalt();

        $hash1 = StoragePathResolver::computeHmac($content);
        $hash2 = hash_hmac('sha256', $content, $salt);

        $this->assertEquals($hash1, $hash2);
        $this->assertEquals(64, strlen($hash1));

        $pathInfo1 = StoragePathResolver::resolveStoragePath('2024_2025', 'CS', 300, $hash1, 'pdf');
        $pathInfo2 = StoragePathResolver::resolveStoragePath('2024_2025', 'CS', 300, $hash1, 'pdf');

        $this->assertEquals($pathInfo1['relative'], $pathInfo2['relative']);
        $this->assertEquals($pathInfo1['filename'], "{$hash1}.pdf");

        // Verify 2-hex prefix sharding
        $prefix = substr($hash1, 0, 2);
        $this->assertStringContainsString("/{$prefix}/{$hash1}.pdf", $pathInfo1['relative']);
    }

    public function testPathTraversalBlocked()
    {
        $this->expectException(InvalidArgumentException::class);
        StoragePathResolver::resolveAbsolutePath('../../etc/passwd');
    }

    public function testBackslashTraversalBlocked()
    {
        $this->expectException(InvalidArgumentException::class);
        StoragePathResolver::resolveAbsolutePath('..\\..\\windows\\win.ini');
    }

    public function testMagicBytesExtensionDetermination()
    {
        $tempDir = sys_get_temp_dir();

        // 1. Valid PDF magic bytes
        $pdfPath = $tempDir . '/test_' . uniqid() . '.tmp';
        file_put_contents($pdfPath, "%PDF-1.4\n%test pdf content");
        $pdfExt = StoragePathResolver::determineExtension($pdfPath);
        @unlink($pdfPath);
        $this->assertEquals('pdf', $pdfExt);

        // 2. Valid PNG magic bytes
        $pngPath = $tempDir . '/test_' . uniqid() . '.tmp';
        file_put_contents($pngPath, "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4");
        $pngExt = StoragePathResolver::determineExtension($pngPath);
        @unlink($pngPath);
        $this->assertEquals('png', $pngExt);

        // 3. Spoofed PHP file with jpg name
        $spoofPath = $tempDir . '/test_' . uniqid() . '.jpg';
        file_put_contents($spoofPath, "<?php phpinfo(); ?>");
        $spoofExt = StoragePathResolver::determineExtension($spoofPath);
        @unlink($spoofPath);
        $this->assertNull($spoofExt, "Malicious script content must not be recognized as an allowed image/pdf extension.");
    }

    public function testServerRulesDenySensitivePaths()
    {
        $htaccessPath = __DIR__ . '/../../.htaccess';
        $this->assertFileExists($htaccessPath);
        $content = file_get_contents($htaccessPath);

        $this->assertStringContainsString('app/logs', $content);
        $this->assertStringContainsString('app/config', $content);
        $this->assertStringContainsString('.env', $content);
        $this->assertStringContainsString('secure_uploads', $content);
        $this->assertStringContainsString('tests', $content);
        $this->assertStringContainsString('docs', $content);
    }
}
