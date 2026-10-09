<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/services/AuditService.php';

class AuditLogChainTest extends TestCase
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
        // Clear any previous session mutation bypass flags
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = NULL");
    }

    /**
     * Test SQL layer triggers block UPDATE and DELETE queries
     */
    public function testAppendOnlyTriggersBlockUpdateAndDelete(): void
    {
        // 1. Log an event to test on
        $entry = AuditService::log(
            'trigger_protection_test',
            ['purpose' => 'verify trigger failure'],
            'super_admin',
            'doc:999',
            '/app/controllers/test',
            'success',
            1,
            '127.0.0.1',
            'PHPUnit',
            self::$db
        );

        $this->assertTrue($entry['success']);
        $id = $entry['id'];
        $this->assertNotNull($id);

        // 2. Attempt UPDATE - must fail with SQLSTATE 45000
        $updateCaught = false;
        try {
            self::$db->query("UPDATE audit_logs SET action = 'tampered_action' WHERE id = {$id}");
        } catch (mysqli_sql_exception $e) {
            $updateCaught = true;
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
        $this->assertTrue($updateCaught, "Database trigger failed to block direct UPDATE query on audit_logs");

        // 3. Attempt DELETE - must fail with SQLSTATE 45000
        $deleteCaught = false;
        try {
            self::$db->query("DELETE FROM audit_logs WHERE id = {$id}");
        } catch (mysqli_sql_exception $e) {
            $deleteCaught = true;
            $this->assertStringContainsString('append-only', $e->getMessage());
        }
        $this->assertTrue($deleteCaught, "Database trigger failed to block direct DELETE query on audit_logs");

        // 4. Attempt calling deprecated CleanupOldAuditLogs procedure
        $cleanupCaught = false;
        try {
            self::$db->query("CALL CleanupOldAuditLogs()");
        } catch (mysqli_sql_exception $e) {
            $cleanupCaught = true;
            $this->assertStringContainsString('CleanupOldAuditLogs is deprecated', $e->getMessage());
        }
        $this->assertTrue($cleanupCaught, "Stored procedure failed to block invocation of CleanupOldAuditLogs");
    }

    /**
     * Test sequential chained logging and cryptographic verification passes
     */
    public function testChainedLoggingAndVerificationPasses(): void
    {
        // Append 5 sequential audit events
        $events = [
            ['action' => 'user_login', 'details' => ['username' => 'student1', 'auth_type' => 'password'], 'outcome' => 'success'],
            ['action' => 'file_upload', 'details' => ['filename' => 'doc1.pdf', 'size' => 1024], 'outcome' => 'success'],
            ['action' => 'quality_check', 'details' => ['blur_variance' => 150.5, 'ocr_status' => 'matched'], 'outcome' => 'success'],
            ['action' => 'status_change', 'details' => ['from' => 'pending', 'to' => 'approved'], 'outcome' => 'success'],
            ['action' => 'file_download', 'details' => ['mode' => 'x-sendfile'], 'outcome' => 'success']
        ];

        $prev = null;
        foreach ($events as $ev) {
            $res = AuditService::log(
                $ev['action'],
                $ev['details'],
                'student',
                'system',
                '/api/events',
                $ev['outcome'],
                1,
                '192.168.1.100',
                'Mozilla/5.0 Test',
                self::$db
            );

            $this->assertTrue($res['success']);
            $this->assertEquals(64, strlen($res['entry_hash']));
            $this->assertEquals(64, strlen($res['prev_hash']));

            if ($prev !== null) {
                $this->assertEquals($prev['entry_hash'], $res['prev_hash'], "Chain linkage broken between sequential entries");
            }
            $prev = $res;
        }

        // Run internal verifier
        $report = AuditService::verifyChain(self::$db);
        $this->assertTrue($report['valid'], "Expected chain to be valid");
        $this->assertGreaterThanOrEqual(5, $report['checked_count']);

        // Run external CLI verifier script
        $phpBinary = PHP_BINARY;
        $scriptPath = realpath(__DIR__ . '/../../app/scripts/verify_audit_chain.php');
        $output = [];
        $exitCode = 0;
        exec("\"{$phpBinary}\" \"{$scriptPath}\" --json", $output, $exitCode);

        $this->assertSame(0, $exitCode, "verify_audit_chain.php CLI script should return 0 for untampered log");
        $cliJson = json_decode(implode("\n", $output), true);
        $this->assertTrue($cliJson['valid'] ?? false);
    }

    /**
     * Test tampering with an entry's payload breaks the chain and is detected at exact row
     */
    public function testPayloadTamperingDetectedAtExactRow(): void
    {
        // Log an event that will be corrupted
        $tamperTarget = AuditService::log(
            'sensitive_financial_clearance',
            ['approved' => true, 'amount' => 5000],
            'faculty_admin',
            'student:1001',
            '/admin/clearance',
            'success',
            2,
            '10.0.0.1',
            'AuditTest',
            self::$db
        );
        $this->assertTrue($tamperTarget['success']);
        $targetId = $tamperTarget['id'];

        // Log a subsequent event chained to it
        $nextEntry = AuditService::log(
            'post_clearance_event',
            ['info' => 'following action'],
            'student',
            'doc:1001',
            '/student/view',
            'success',
            1,
            '10.0.0.1',
            'AuditTest',
            self::$db
        );
        $this->assertTrue($nextEntry['success']);

        // Ensure chain is valid before attack
        $preCheck = AuditService::verifyChain(self::$db);
        $this->assertTrue($preCheck['valid']);

        // SIMULATE ADVERSARY ATTACK:
        // Attacker with DBA access sets bypass variable and alters the stored payload without re-hashing
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = 1");
        $corruptSql = "UPDATE audit_logs SET details = '{\"amount\":0,\"approved\":false}', event_description = '{\"amount\":0,\"approved\":false}' WHERE id = {$targetId}";
        self::$db->query($corruptSql);
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = NULL");

        // Run verification - must fail at targetId
        $tamperReport = AuditService::verifyChain(self::$db);
        $this->assertFalse($tamperReport['valid'], "Chain verification should have failed due to payload tampering");
        $this->assertEquals($targetId, $tamperReport['broken_id'], "Tamper detection must pinpoint the exact corrupted row ID");
        $this->assertStringContainsString('payload mismatch', strtolower($tamperReport['reason']));

        // Verify CLI exit code is 1
        $phpBinary = PHP_BINARY;
        $scriptPath = realpath(__DIR__ . '/../../app/scripts/verify_audit_chain.php');
        $output = [];
        $exitCode = 0;
        exec("\"{$phpBinary}\" \"{$scriptPath}\" 2>&1", $output, $exitCode);

        $this->assertSame(1, $exitCode, "verify_audit_chain.php must exit with 1 on tampered database");
        $cliOutputText = implode("\n", $output);
        $this->assertStringContainsString("Broken Entry ID: {$targetId}", $cliOutputText);
        $this->assertStringContainsString("FAILED [TAMPER DETECTED]", $cliOutputText);

        // Revert tampering so subsequent tests have a valid baseline
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = 1");
        $origJson = json_encode(['amount' => 5000, 'approved' => true], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        self::$db->query("UPDATE audit_logs SET details = '{$origJson}', event_description = '{$origJson}' WHERE id = {$targetId}");
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = NULL");
    }

    /**
     * Test tampering with prev_hash linkage breaks the chain and is detected
     */
    public function testPrevHashTamperingDetectedAtExactRow(): void
    {
        // Find the latest entry
        $res = self::$db->query("SELECT id, prev_hash FROM audit_logs ORDER BY id DESC LIMIT 1");
        $row = $res->fetch_assoc();
        $latestId = (int)$row['id'];
        $origPrevHash = (string)$row['prev_hash'];

        // Corrupt its prev_hash
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = 1");
        $fakePrevHash = str_repeat('f', 64);
        self::$db->query("UPDATE audit_logs SET prev_hash = '{$fakePrevHash}' WHERE id = {$latestId}");
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = NULL");

        // Run verification
        $report = AuditService::verifyChain(self::$db);
        $this->assertFalse($report['valid']);
        $this->assertEquals($latestId, $report['broken_id']);
        $this->assertStringContainsString('prev_hash mismatch', strtolower($report['reason']));

        // Restore original prev_hash
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = 1");
        self::$db->query("UPDATE audit_logs SET prev_hash = '{$origPrevHash}' WHERE id = {$latestId}");
        self::$db->query("SET @ALLOW_AUDIT_LOG_MUTATION = NULL");
    }
}
