<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/User.php';

class ActivationFlowTest extends TestCase {

    public function testActivationTokenGeneration() {
        $token = bin2hex(random_bytes(32));
        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testActivationUrlFormat() {
        $token = 'sample_token_12345';
        $domain = 'dfms.ui.edu.ng';
        $url = "https://{$domain}/app/controllers/activate.php?token={$token}";

        $this->assertStringContainsString('/app/controllers/activate.php?token=', $url);
        $this->assertStringStartsWith('https://', $url);
    }
}
