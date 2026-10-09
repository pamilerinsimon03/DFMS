<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/utils/SecurityUtils.php';

class SecurityUtilsTest extends TestCase {

    public function testValidateInput() {
        $this->assertEquals('test', SecurityUtils::validateInput('test  '));
        $this->assertFalse(SecurityUtils::validateInput('test', 'string', 3)); // Max length check
        $this->assertEquals('valid@email.com', SecurityUtils::validateInput('valid@email.com', 'email'));
        $this->assertFalse(SecurityUtils::validateInput('invalid-email', 'email'));
        $this->assertEquals(123, SecurityUtils::validateInput('123', 'int'));
        $this->assertFalse(SecurityUtils::validateInput('abc', 'int'));
        $this->assertEquals('ABC123', SecurityUtils::validateInput('ABC123', 'alphanumeric'));
        $this->assertEquals('MAT/123/456', SecurityUtils::validateInput('MAT/123/456', 'matric'));
    }
    
    public function testCheckRateLimit() {
        // Since rate limit uses static store and time(), we might test simply that it allows N attempts
        // Resetting state isn't easy without reflection or method, but since it's per identifier:
        $id = 'test_ip_' . uniqid();
        
        for ($i = 0; $i < 5; $i++) {
            $this->assertTrue(SecurityUtils::checkRateLimit($id), "Attempt $i should be allowed");
        }
        
        $this->assertFalse(SecurityUtils::checkRateLimit($id), "6th attempt should be blocked");
    }
    
    public function testGenerateSecureFilename() {
        $filename = SecurityUtils::generateSecureFilename('test.pdf', 123);
        $this->assertStringStartsWith('doc_123_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
        $this->assertMatchesRegularExpression('/^doc_123_\d+_[a-f0-9]{16}\.pdf$/', $filename);
    }

    public function testZeroByteFileUploadRejection() {
        $file = [
            'name' => 'empty_document.pdf',
            'type' => 'application/pdf',
            'tmp_name' => sys_get_temp_dir() . '/empty_test_' . uniqid() . '.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 0
        ];
        file_put_contents($file['tmp_name'], '');

        $errors = SecurityUtils::validateFileUpload($file);
        @unlink($file['tmp_name']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('File MIME type does not match', implode(' ', $errors));
    }

    public function testSpoofedMimeTypeRejection() {
        $file = [
            'name' => 'script.pdf',
            'type' => 'application/pdf',
            'tmp_name' => sys_get_temp_dir() . '/spoof_test_' . uniqid() . '.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 100
        ];
        file_put_contents($file['tmp_name'], '<?php echo "evil payload"; ?>');

        $errors = SecurityUtils::validateFileUpload($file);
        @unlink($file['tmp_name']);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('MIME type does not match', implode(' ', $errors));
    }
}
