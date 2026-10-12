<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Document.php';
require_once __DIR__ . '/../../app/services/AuditService.php';
require_once __DIR__ . '/../../app/utils/StoragePathResolver.php';

class NDPAComplianceTest extends TestCase
{
    private mysqli $db;
    private int $testUserId;
    private string $matric;

    protected function setUp(): void
    {
        global $conn;
        $this->db = $conn;

        // Create test student user
        $this->matric = 'TEST/NDPA/' . bin2hex(random_bytes(3));
        $email = 'ndpa_test_' . bin2hex(random_bytes(3)) . '@ui.edu.ng';
        $hashed = password_hash('Pass12345!', PASSWORD_BCRYPT);
        $publicId = User::generateUUID();

        $stmt = $this->db->prepare("
            INSERT INTO users (public_id, matric_number, full_name, email, password, role, is_admin, is_activated, processing_restricted, session, level, programme, department_id, created_at)
            VALUES (?, ?, 'NDPA Test Student', ?, ?, 'student', 0, 1, 0, '2025/2026', 100, 'Computer Science', 1, NOW())
        ");
        $stmt->bind_param("ssss", $publicId, $this->matric, $email, $hashed);
        $stmt->execute();
        $this->testUserId = $stmt->insert_id;
        $stmt->close();
    }

    protected function tearDown(): void
    {
        // Clean up test documents, consents, and user
        $stmt = $this->db->prepare("DELETE FROM documents WHERE user_id = ?");
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare("DELETE FROM consent_records WHERE user_id = ?");
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();
    }

    public function testConsentCaptureAndRetrieval(): void
    {
        // Record consent under NDPA 2023 s.25
        $recorded = User::recordConsent(
            $this->testUserId,
            'email_notifications',
            true,
            '192.168.1.100',
            'Mozilla/5.0 Test',
            '2025.1'
        );
        $this->assertTrue($recorded);

        $consents = User::getUserConsents($this->testUserId);
        $this->assertNotEmpty($consents);
        $this->assertEquals('email_notifications', $consents[0]['consent_type']);
        $this->assertEquals(1, (int)$consents[0]['consent_given']);
        $this->assertEquals('2025.1', $consents[0]['policy_version']);
        $this->assertEquals('192.168.1.100', $consents[0]['ip_address']);
    }

    public function testOptionalConsentWithdrawalSucceeds(): void
    {
        User::recordConsent($this->testUserId, 'portal_analytics', true);

        $withdrawRes = User::withdrawConsent($this->testUserId, 'portal_analytics');
        $this->assertTrue($withdrawRes['success']);
        $this->assertStringContainsString('successfully withdrawn', $withdrawRes['message']);

        $consents = User::getUserConsents($this->testUserId);
        $this->assertEquals(0, (int)$consents[0]['consent_given']);
        $this->assertNotNull($consents[0]['consent_withdrawn_at']);
    }

    public function testCoreAcademicConsentWithdrawalRefusedUnderNDPA(): void
    {
        User::recordConsent($this->testUserId, 'core_academic_processing', true);

        // Core academic record processing cannot be withdrawn (NDPA s.25(1)(b))
        $withdrawRes = User::withdrawConsent($this->testUserId, 'core_academic_processing');
        $this->assertFalse($withdrawRes['success']);
        $this->assertStringContainsString('NDPA 2023 Section 25(1)(b)', $withdrawRes['message']);

        // Verify status remained active
        $consents = User::getUserConsents($this->testUserId);
        $this->assertEquals(1, (int)$consents[0]['consent_given']);
    }

    public function testAuditedRectificationLogsDiff(): void
    {
        // Update single field
        $updated = User::updateSingleField($this->testUserId, 'phone_number', '08012345678');
        $this->assertTrue($updated);

        // Verify user row updated
        $user = User::getById($this->testUserId);
        $this->assertEquals('08012345678', $user['phone_number']);

        // Verify cryptographic audit entry was emitted
        $stmt = $this->db->prepare("
            SELECT details FROM audit_logs 
            WHERE action = 'data_rectification' AND target = ? 
            ORDER BY id DESC LIMIT 1
        ");
        $target = 'user:' . $this->testUserId;
        $stmt->bind_param("s", $target);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->assertNotNull($res);
        $details = json_decode($res['details'], true);
        $this->assertEquals('phone_number', $details['field']);
        $this->assertEquals('08012345678', $details['new_value']);
        $this->assertEquals('NDPA 2023 s.36', $details['statutory_clause']);
    }

    public function testProcessingRestrictionLocksProfileUpdates(): void
    {
        $this->assertFalse(User::isProcessingRestricted($this->testUserId));

        // Apply restriction under NDPA 2023 s.37
        $applyRes = User::setProcessingRestriction($this->testUserId, true, 'Dispute pending review');
        $this->assertTrue($applyRes['success']);
        $this->assertTrue(User::isProcessingRestricted($this->testUserId));

        // Profile update must be rejected while processing is restricted
        $updateSuccess = User::updateSingleField($this->testUserId, 'address', 'New Address Line');
        $this->assertFalse($updateSuccess);

        // User address must remain unchanged
        $user = User::getById($this->testUserId);
        $this->assertNotEquals('New Address Line', $user['address'] ?? null);

        // Lift restriction
        $liftRes = User::setProcessingRestriction($this->testUserId, false, 'Dispute resolved');
        $this->assertTrue($liftRes['success']);
        $this->assertFalse(User::isProcessingRestricted($this->testUserId));

        // Profile update should now succeed
        $updateSuccessAfter = User::updateSingleField($this->testUserId, 'address', 'New Address Line');
        $this->assertTrue($updateSuccessAfter);
    }

    public function testProcessingRestrictionBlocksDocumentUpload(): void
    {
        // Restrict processing
        User::setProcessingRestriction($this->testUserId, true, 'Suspension under investigation');

        // Create dummy upload file
        $tmp = tempnam(sys_get_temp_dir(), 'ndpa_doc_');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        $fakeFile = [
            'name'     => 'test_upload.pdf',
            'type'     => 'application/pdf',
            'tmp_name' => $tmp,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($tmp)
        ];

        $classification = ['department_id' => 1, 'session' => '2025/2026', 'level' => 100];
        $uploadRes = Document::upload($this->testUserId, 'medical_report', 'doc_test.pdf', $fakeFile, $classification);
        @unlink($tmp);

        $this->assertFalse($uploadRes['success']);
        $this->assertEquals(403, $uploadRes['http_code']);
        $this->assertStringContainsString('NDPA 2023 Section 37', $uploadRes['message']);
    }

    public function testRetentionEnforcerPurgesExpiredNonCoreAndPreservesCore(): void
    {
        // 1. Create a dummy physical storage file for non-core document
        $storageRoot = defined('STORAGE_ROOT') ? STORAGE_ROOT : (getenv('STORAGE_ROOT') ?: '../dfms_private/secure_uploads');
        $nonCoreRelPath = '2025_2026/1/100/ab/test_medical_record.pdf';
        $nonCoreFullPath = rtrim($storageRoot, '/\\') . '/' . $nonCoreRelPath;
        if (!is_dir(dirname($nonCoreFullPath))) {
            @mkdir(dirname($nonCoreFullPath), 0750, true);
        }
        file_put_contents($nonCoreFullPath, "%PDF-1.4\nDummy Medical Report");

        // 2. Insert expired non-core document (Medical Fitness Certificate uploaded 1200 days ago, limit is 1095 days)
        $publicIdNonCore = Document::generateUUID();
        $stmt = $this->db->prepare("
            INSERT INTO documents (public_id, user_id, document_name, file_path, file_hash, upload_date, status, file_scan_status)
            VALUES (?, ?, 'Medical Fitness Certificate', ?, 'fakehash123', DATE_SUB(NOW(), INTERVAL 1200 DAY), 'Approved', 'clean')
        ");
        $stmt->bind_param("sis", $publicIdNonCore, $this->testUserId, $nonCoreRelPath);
        $stmt->execute();
        $nonCoreDocId = $stmt->insert_id;
        $stmt->close();

        // 3. Insert core academic document (JAMB Admission Letter uploaded 1200 days ago, is_core_academic = 1)
        $coreRelPath = '2025_2026/1/100/cd/test_admission_letter.pdf';
        $coreFullPath = rtrim($storageRoot, '/\\') . '/' . $coreRelPath;
        if (!is_dir(dirname($coreFullPath))) {
            @mkdir(dirname($coreFullPath), 0750, true);
        }
        file_put_contents($coreFullPath, "%PDF-1.4\nDummy Admission Letter");

        $publicIdCore = Document::generateUUID();
        $stmt = $this->db->prepare("
            INSERT INTO documents (public_id, user_id, document_name, file_path, file_hash, upload_date, status, file_scan_status)
            VALUES (?, ?, 'JAMB Admission Letter', ?, 'fakehash456', DATE_SUB(NOW(), INTERVAL 1200 DAY), 'Approved', 'clean')
        ");
        $stmt->bind_param("sis", $publicIdCore, $this->testUserId, $coreRelPath);
        $stmt->execute();
        $coreDocId = $stmt->insert_id;
        $stmt->close();

        // 4. Run retention enforcer script
        putenv('DB_NAME=dfms_test');
        $_SERVER['DB_NAME'] = 'dfms_test';
        $cmd = 'php ' . escapeshellarg(__DIR__ . '/../../app/scripts/enforce_retention.php');
        exec($cmd, $output, $retCode);
        $this->assertEquals(0, $retCode, "Retention enforcer script failed: " . implode("\n", $output));

        // 5. Verify non-core document was purged and physical file unlinked
        $nonCoreDoc = Document::getById($nonCoreDocId);
        $this->assertEquals('Purged', $nonCoreDoc['status']);
        $this->assertNull($nonCoreDoc['file_path']);
        $this->assertNotNull($nonCoreDoc['purged_at']);
        $this->assertEquals('retention_policy', $nonCoreDoc['purge_reason']);
        $this->assertFalse(file_exists($nonCoreFullPath), "Expired non-core physical file should have been deleted");

        // 6. Verify core academic document was PRESERVED
        $coreDoc = Document::getById($coreDocId);
        $this->assertEquals('Approved', $coreDoc['status']);
        $this->assertEquals($coreRelPath, $coreDoc['file_path']);
        $this->assertNull($coreDoc['purged_at']);
        $this->assertTrue(file_exists($coreFullPath), "Core academic file must be permanently preserved");

        // Clean up core physical file
        @unlink($coreFullPath);
    }
}
