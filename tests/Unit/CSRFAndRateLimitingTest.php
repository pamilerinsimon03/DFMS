<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/CSRFProtection.php';
require_once __DIR__ . '/../../app/services/RateLimiter.php';

class CSRFAndRateLimitingTest extends TestCase
{
    private static mysqli $db;

    public static function setUpBeforeClass(): void
    {
        resetDatabase();
        global $conn;
        self::$db = $conn;
    }

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        unset($_POST['csrf_token']);
    }

    public function testCSRFTokenGenerationAndInputHelper(): void
    {
        $token = generateCSRFToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));

        $inputHtml = getCSRFTokenInput();
        $this->assertStringContainsString('type="hidden"', $inputHtml);
        $this->assertStringContainsString('name="csrf_token"', $inputHtml);
        $this->assertStringContainsString($token, $inputHtml);
    }

    public function testMissingCSRFTokenFailsValidation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $extracted = CSRFProtection::extractToken();
        $this->assertNull($extracted);
        $this->assertFalse(CSRFProtection::validateToken($extracted));
    }

    public function testInvalidCSRFTokenFailsValidation(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = 'invalid_tampered_token_string_123456';
        $extracted = CSRFProtection::extractToken();
        $this->assertEquals('invalid_tampered_token_string_123456', $extracted);
        $this->assertFalse(CSRFProtection::validateToken($extracted));
    }

    public function testValidCSRFTokenAcceptedViaFormPost(): void
    {
        $token = CSRFProtection::getToken();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['csrf_token'] = $token;

        $extracted = CSRFProtection::extractToken();
        $this->assertEquals($token, $extracted);
        $this->assertTrue(CSRFProtection::validateToken($extracted));
    }

    public function testValidCSRFTokenAcceptedViaHeader(): void
    {
        $token = CSRFProtection::getToken();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;

        $extracted = CSRFProtection::extractToken();
        $this->assertEquals($token, $extracted);
        $this->assertTrue(CSRFProtection::validateToken($extracted));
    }

    public function testLogoutRejectsGetRequestWithMethodNotAllowed(): void
    {
        $php = PHP_BINARY;
        $logoutScript = realpath(__DIR__ . '/../../app/controllers/logout.php');

        $cmd = "\"{$php}\" -r \"\$_SERVER['REQUEST_METHOD'] = 'GET'; \$_SERVER['SCRIPT_NAME'] = '/app/controllers/logout.php'; require '{$logoutScript}';\" 2>&1";
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        $outputText = implode("\n", $out);
        $this->assertStringContainsString('Method Not Allowed: Logout must be performed via POST', $outputText);
    }

    public function testAuthEndpointRateLimitRejects6thAttempt(): void
    {
        $testIp = '10.254.1.' . rand(1, 250);
        $key = "ip:{$testIp}";

        // Clear any previous records for this key
        $keyHash = hash('sha256', $key);
        self::$db->query("DELETE FROM rate_limits WHERE key_hash = '{$keyHash}'");

        // First 5 attempts on Auth endpoint (limit = 5) must succeed
        for ($i = 1; $i <= 5; $i++) {
            $allowed = RateLimiter::checkHit($key, RateLimiter::AUTH_LIMIT, 60, self::$db);
            $this->assertTrue($allowed, "Attempt {$i} of 5 should have succeeded");
        }

        // 6th attempt must be rejected (rate limited)
        $blocked = RateLimiter::checkHit($key, RateLimiter::AUTH_LIMIT, 60, self::$db);
        $this->assertFalse($blocked, "The 6th attempt within 60s must be blocked by the auth rate limit");
    }

    public function testGeneralEndpointRateLimitRejects61stAttempt(): void
    {
        $testIp = '10.254.2.' . rand(1, 250);
        $key = "ip:{$testIp}";

        // Clear any previous records for this key
        $keyHash = hash('sha256', $key);
        self::$db->query("DELETE FROM rate_limits WHERE key_hash = '{$keyHash}'");

        // First 60 attempts on general endpoints (limit = 60) must succeed
        for ($i = 1; $i <= 60; $i++) {
            $allowed = RateLimiter::checkHit($key, RateLimiter::GENERAL_LIMIT, 60, self::$db);
            $this->assertTrue($allowed, "Attempt {$i} of 60 should have succeeded");
        }

        // 61st attempt must be rejected
        $blocked = RateLimiter::checkHit($key, RateLimiter::GENERAL_LIMIT, 60, self::$db);
        $this->assertFalse($blocked, "The 61st attempt within 60s must be blocked by the general rate limit");
    }

    public function testPerUserRateLimitingEnforced(): void
    {
        $matric = 'TEST/RATELIMIT/' . rand(1000, 9999);
        $key = "user:matric:{$matric}";

        // Clear any previous records for this key
        $keyHash = hash('sha256', $key);
        self::$db->query("DELETE FROM rate_limits WHERE key_hash = '{$keyHash}'");

        for ($i = 1; $i <= 5; $i++) {
            $allowed = RateLimiter::checkHit($key, RateLimiter::AUTH_LIMIT, 60, self::$db);
            $this->assertTrue($allowed, "Attempt {$i} for user should succeed");
        }

        $blocked = RateLimiter::checkHit($key, RateLimiter::AUTH_LIMIT, 60, self::$db);
        $this->assertFalse($blocked, "The 6th attempt for user identifier must be blocked");
    }
}
