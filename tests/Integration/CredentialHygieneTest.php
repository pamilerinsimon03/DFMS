<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/PasswordReset.php';

class CredentialHygieneTest extends TestCase
{
    private static mysqli $db;

    public static function setUpBeforeClass(): void
    {
        resetDatabase();
        global $conn;
        self::$db = $conn;
    }

    public function testUnactivatedStudentCannotAuthenticate(): void
    {
        // 1. Create unactivated student (matric number <= 20 chars)
        $matric = 'U_UNACT_' . substr(strval(time()), -8);
        $studentData = [
            'matric_number' => $matric,
            'full_name'     => 'Unactivated Student',
            'level'         => 100,
            'session'       => '2023/2024',
            'programme'     => 'Computer Science',
            'department_id' => 1,
            'email'         => 'unact@test.local'
        ];

        $res = User::createStudent($studentData);
        $this->assertTrue($res['success']);
        $this->assertNotEmpty($res['activation_token']);
        $this->assertEquals('*ACTIVATION_REQUIRED*', $res['password']);

        $user = User::getByMatricNumber($matric);
        $this->assertNotNull($user);
        $this->assertEquals(0, (int)$user['is_activated']);
        $this->assertEquals('*ACTIVATION_REQUIRED*', $user['password']);

        // Verify password_verify fails against placeholder
        $this->assertFalse(password_verify('doe12345', $user['password']));
        $this->assertFalse(password_verify('admin12345', $user['password']));
        $this->assertFalse(password_verify('*ACTIVATION_REQUIRED*', $user['password']));
    }

    public function testStudentActivationSucceedsAndRecordsNDPAConsents(): void
    {
        $matric = 'TEST_ACT_' . time();
        $studentData = [
            'matric_number' => $matric,
            'full_name'     => 'Activation Test Candidate',
            'level'         => 200,
            'session'       => '2023/2024',
            'programme'     => 'Computer Science',
            'department_id' => 1,
            'email'         => 'candidate@test.local'
        ];

        $res = User::createStudent($studentData);
        $this->assertTrue($res['success']);
        $token = $res['activation_token'];

        $newPassword = 'MySecretP@ssw0rd!2026';
        $actRes = User::activateAccountByToken($token, null, $newPassword);
        $this->assertTrue($actRes['success']);

        // Verify user is now activated and password verified
        $user = User::getByMatricNumber($matric);
        $this->assertEquals(1, (int)$user['is_activated']);
        $this->assertNull($user['activation_token']);
        $this->assertTrue(password_verify($newPassword, $user['password']));

        // Verify NDPA consents were recorded during activation
        $consents = User::getUserConsents((int)$user['id']);
        $this->assertNotEmpty($consents);
        $consentTypes = array_column($consents, 'consent_type');
        $this->assertContains('core_academic_processing', $consentTypes);
        $this->assertContains('email_notifications', $consentTypes);
        $this->assertContains('portal_analytics', $consentTypes);
    }

    public function testActivationTokenIsStrictlySingleUse(): void
    {
        $matric = 'TEST_REUSE_' . time();
        $studentData = [
            'matric_number' => $matric,
            'full_name'     => 'Reuse Test Candidate',
            'level'         => 300,
            'session'       => '2023/2024',
            'programme'     => 'Psychology',
            'department_id' => 2,
            'email'         => 'reuse@test.local'
        ];

        $res = User::createStudent($studentData);
        $token = $res['activation_token'];

        // First activation succeeds
        $first = User::activateAccountByToken($token, null, 'FirstPassword#123');
        $this->assertTrue($first['success']);

        // Second activation with the same token MUST fail
        $second = User::activateAccountByToken($token, null, 'SecondPassword#456');
        $this->assertFalse($second['success']);
        $this->assertStringContainsString('Invalid or expired', $second['message']);
    }

    public function testExpiredActivationTokenIsRejected(): void
    {
        $matric = 'TEST_EXP_' . time();
        $studentData = [
            'matric_number' => $matric,
            'full_name'     => 'Expired Token Test',
            'level'         => 100,
            'session'       => '2023/2024',
            'programme'     => 'English',
            'department_id' => 3,
            'email'         => 'exp@test.local'
        ];

        $res = User::createStudent($studentData);
        $token = $res['activation_token'];

        // Expire token in database
        self::$db->query("UPDATE users SET activation_expires_at = DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE matric_number = '{$matric}'");

        $actRes = User::activateAccountByToken($token, null, 'ExpiredAttempt#789');
        $this->assertFalse($actRes['success']);
        $this->assertStringContainsString('Invalid or expired', $actRes['message']);
    }

    public function testSurnamePasswordGenerationRemoved(): void
    {
        $password = User::generatePassword('John Doe');
        $this->assertNotEquals('doe12345', $password);
        $this->assertStringNotContainsString('doe12345', $password);
        $this->assertGreaterThanOrEqual(16, strlen($password));
    }

    public function testPasswordResetStoresHashAndEnforcesSingleUse(): void
    {
        $userId = 1;
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $created = PasswordReset::create($userId, $hashedToken);
        $this->assertTrue($created);

        // Verify database stores the SHA-256 hash, not the plaintext raw token
        $check = self::$db->query("SELECT token FROM password_resets WHERE user_id = {$userId}")->fetch_assoc();
        $this->assertNotNull($check);
        $this->assertEquals($hashedToken, $check['token']);
        $this->assertNotEquals($rawToken, $check['token']);

        // Lookup via getByToken(rawToken) succeeds via internal hashing
        $found = PasswordReset::getByToken($rawToken);
        $this->assertNotNull($found);
        $this->assertEquals($userId, (int)$found['user_id']);

        // Invalidate via delete(rawToken)
        $deleted = PasswordReset::delete($rawToken);
        $this->assertTrue($deleted);

        // Subsequent lookup must return null (single use)
        $afterDelete = PasswordReset::getByToken($rawToken);
        $this->assertNull($afterDelete);
    }

    public function testExpiredPasswordResetTokenIsRejected(): void
    {
        $userId = 2;
        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        PasswordReset::create($userId, $hashedToken);

        // Expire in database
        self::$db->query("UPDATE password_resets SET expires_at = DATE_SUB(NOW(), INTERVAL 5 MINUTE) WHERE token = '{$hashedToken}'");

        $expired = PasswordReset::getByToken($rawToken);
        $this->assertNull($expired);
    }

    public function testSeedDemoScriptProducesCryptographicallyRandomCredentials(): void
    {
        $phpBinary = PHP_BINARY;
        $scriptPath = realpath(__DIR__ . '/../../app/scripts/seed_demo.php');
        $output = [];
        $exitCode = 0;

        exec("\"{$phpBinary}\" \"{$scriptPath}\" --json", $output, $exitCode);
        $this->assertSame(0, $exitCode);

        $data = json_decode(implode("\n", $output), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data['admins']);
        $this->assertNotEmpty($data['students']);

        foreach ($data['admins'] as $admin) {
            $this->assertGreaterThanOrEqual(16, strlen($admin['password']));
            $this->assertNotEquals('admin12345', $admin['password']);
            $this->assertNotEquals('test101', $admin['password']);
        }

        foreach ($data['students'] as $student) {
            $this->assertEquals('Pending Activation', $student['status']);
            $this->assertStringContainsString('activate.php?token=', $student['activation_url']);
        }
    }
}
