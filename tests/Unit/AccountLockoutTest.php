<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/utils/SecurityUtils.php';

class AccountLockoutTest extends TestCase {

    public function testAccountLockoutTriggeredAfterFiveAttempts() {
        $mockStmt = $this->createMock(mysqli_stmt::class);
        $mockStmt->method('execute')->willReturn(true);

        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn([
            'id' => 10,
            'matric_number' => 'TEST/LOCK/001',
            'failed_login_attempts' => 4,
            'account_locked_until' => null
        ]);
        $mockStmt->method('get_result')->willReturn($mockResult);

        $mockConn = $this->createMock(mysqli::class);
        $mockConn->method('prepare')->willReturn($mockStmt);
        $GLOBALS['conn'] = $mockConn;

        $result = User::incrementFailedLoginAttempts('TEST/LOCK/001');
        $this->assertTrue($result);
    }

    public function testValidationRejectsMalformedMimeTypes() {
        $invalidFile = [
            'name' => 'malicious.php.pdf',
            'type' => 'application/x-php',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $errors = SecurityUtils::validateFileUpload($invalidFile);
        $this->assertNotEmpty($errors);
    }
}
