<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Document.php';
require_once __DIR__ . '/../../app/services/AuditService.php';
require_once __DIR__ . '/../../app/services/AcademicCalendarService.php';
require_once __DIR__ . '/../../app/services/SessionRollOverService.php';
require_once __DIR__ . '/../../app/utils/StoragePathResolver.php';

class DefectRegressionTest extends TestCase
{
    private mysqli $db;
    private int $testUserId;
    private string $matric;
    private array $createdDocIds = [];

    protected function setUp(): void
    {
        global $conn;
        $this->db = $conn;

        $this->matric = 'TEST/REG/' . bin2hex(random_bytes(3));
        $email = 'reg_test_' . bin2hex(random_bytes(3)) . '@ui.edu.ng';
        $hashed = password_hash('Pass12345!', PASSWORD_BCRYPT);
        $publicId = User::generateUUID();

        $stmt = $this->db->prepare("
            INSERT INTO users (public_id, matric_number, full_name, email, password, role, is_admin, is_activated, processing_restricted, session, level, programme, department_id, created_at)
            VALUES (?, ?, 'Defect Regression Student', ?, ?, 'student', 0, 1, 0, '2024/2025', 200, 'Computer Science', 1, NOW())
        ");
        $stmt->bind_param("ssss", $publicId, $this->matric, $email, $hashed);
        $stmt->execute();
        $this->testUserId = $stmt->insert_id;
        $stmt->close();

        $this->createdDocIds = [];
    }

    protected function tearDown(): void
    {
        foreach ($this->createdDocIds as $docId) {
            $stmt = $this->db->prepare("SELECT file_path FROM documents WHERE id = ?");
            $stmt->bind_param("i", $docId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($res && !empty($res['file_path'])) {
                try {
                    $p = StoragePathResolver::resolvePhysicalPath($res['file_path']);
                    if (file_exists($p)) {
                        @unlink($p);
                    }
                } catch (Throwable $e) {}
            }

            $stmt = $this->db->prepare("DELETE FROM document_processing_queue WHERE document_id = ?");
            $stmt->bind_param("i", $docId);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->bind_param("i", $docId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $this->db->prepare("DELETE FROM session_history WHERE user_id = ?");
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();

        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $this->testUserId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * 1. Defect 1: User::getAllStudents SQL injection whitelisting
     */
    public function testGetAllStudentsSortWhitelisting(): void
    {
        $superAdmin = ['id' => 1, 'role' => 'super_admin', 'is_admin' => 1, 'department_id' => null];

        // Valid sorting by allowed columns
        $resultsAsc = User::getAllStudents('', null, null, null, null, null, null, 1, 10, 'matric_number', 'asc', $superAdmin);
        $this->assertIsArray($resultsAsc);

        $resultsDesc = User::getAllStudents('', null, null, null, null, null, null, 1, 10, 'created_at', 'desc', $superAdmin);
        $this->assertIsArray($resultsDesc);

        // SQL injection payload in sort_by
        $maliciousSortBy = "matric_number; DROP TABLE users; --";
        $resultsSafe = User::getAllStudents('', null, null, null, null, null, null, 1, 10, $maliciousSortBy, 'ASC', $superAdmin);
        $this->assertIsArray($resultsSafe, "Whitelisting must prevent SQL injection and fallback gracefully to default sort.");

        // SQL injection payload in sort_order
        $maliciousSortOrder = "DESC; DROP TABLE users; --";
        $resultsSafeOrder = User::getAllStudents('', null, null, null, null, null, null, 1, 10, 'full_name', $maliciousSortOrder, $superAdmin);
        $this->assertIsArray($resultsSafeOrder, "Whitelisting must sanitize sort_order to ASC or DESC.");
    }

    /**
     * 2. Defect 2: AuditService::getRecentEvents and inverted argument support
     */
    public function testAuditServiceGetRecentEventsWithInvertedArguments(): void
    {
        // Log a test audit event
        AuditService::log(
            'defect_test_action',
            ['detail' => 'testing getRecentEvents'],
            'super_admin',
            'system',
            '/app/controllers/analytics.php',
            'success',
            $this->testUserId,
            '127.0.0.1'
        );

        // Standard call: getRecentEvents(10)
        $events1 = AuditService::getRecentEvents(5);
        $this->assertIsArray($events1);
        $this->assertNotEmpty($events1);
        $first = $events1[0];
        $this->assertArrayHasKey('event_type', $first);
        $this->assertArrayHasKey('event_description', $first);
        $this->assertArrayHasKey('created_at', $first);

        // Inverted argument call: getRecentEvents('super_admin', 5)
        $eventsInverted = AuditService::getRecentEvents('super_admin', 5);
        $this->assertIsArray($eventsInverted);
        $this->assertLessThanOrEqual(5, count($eventsInverted));
        foreach ($eventsInverted as $ev) {
            $this->assertEquals('super_admin', $ev['role']);
        }

        // Filtered call: getRecentEvents(5, 'super_admin')
        $eventsFiltered = AuditService::getRecentEvents(5, 'super_admin');
        $this->assertIsArray($eventsFiltered);
        $this->assertLessThanOrEqual(5, count($eventsFiltered));
    }

    /**
     * 3. Defect 3: Document status enum mismatch and filter normalization
     */
    public function testDocumentStatusFilterNormalization(): void
    {
        $superAdmin = ['id' => 1, 'role' => 'super_admin', 'is_admin' => 1, 'department_id' => null];
        $startDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('+1 day'));

        // Insert documents with different statuses
        $statuses = ['Pending Quality Check', 'Pending Approval', 'Approved', 'Rejected'];
        foreach ($statuses as $st) {
            $publicId = Document::generateUUID();
            $stmt = $this->db->prepare("
                INSERT INTO documents (public_id, user_id, document_name, file_path, file_hash, status, created_at)
                VALUES (?, ?, ?, 'secure_uploads/dummy.pdf', 'abc123hash', ?, NOW())
            ");
            $docName = 'Doc ' . $st;
            $stmt->bind_param("siss", $publicId, $this->testUserId, $docName, $st);
            $stmt->execute();
            $this->createdDocIds[] = $stmt->insert_id;
            $stmt->close();
        }

        // 'all' filter must count all documents, not filter for status = 'all'
        $countAll = Document::countAllDocuments('all', $startDate, $endDate, null, null, null, null, null, $superAdmin);
        $this->assertGreaterThanOrEqual(4, $countAll, "'all' status filter should return total documents without restricting status.");

        // 'pending' filter must count both 'Pending Quality Check' and 'Pending Approval'
        $countPending = Document::countAllDocuments('pending', $startDate, $endDate, null, null, null, null, null, $superAdmin);
        $this->assertGreaterThanOrEqual(2, $countPending, "'pending' status filter must aggregate Pending Quality Check and Pending Approval.");

        $pendingList = Document::getAllDocuments('pending', $startDate, $endDate, null, 1, 10, null, null, null, null, 'upload_date', 'desc', $superAdmin);
        foreach ($pendingList as $pDoc) {
            $this->assertContains($pDoc['status'], ['Pending Quality Check', 'Pending Approval']);
        }

        // 'approved' (case-insensitive) filter
        $countApproved = Document::countAllDocuments('approved', $startDate, $endDate, null, null, null, null, null, $superAdmin);
        $this->assertGreaterThanOrEqual(1, $countApproved);

        // 'rejected' filter
        $countRejected = Document::countAllDocuments('rejected', $startDate, $endDate, null, null, null, null, null, $superAdmin);
        $this->assertGreaterThanOrEqual(1, $countRejected);
    }

    /**
     * 4. Defect 4: Academic session key synchronization
     */
    public function testAcademicSessionKeySynchronization(): void
    {
        $settingsFile = __DIR__ . '/../../app/config/settings.json';
        $originalSettings = file_exists($settingsFile) ? file_get_contents($settingsFile) : null;
        $originalSession = $_SESSION ?? [];

        try {
            // Test AcademicCalendarService reading from $_SESSION['academic_session']
            $_SESSION['academic_session'] = '2030/2031';
            unset($_SESSION['current_academic_session']);
            $this->assertEquals('2030/2031', AcademicCalendarService::getCurrentSession());

            // Test AcademicCalendarService reading from $_SESSION['current_academic_session']
            unset($_SESSION['academic_session']);
            $_SESSION['current_academic_session'] = '2031/2032';
            $this->assertEquals('2031/2032', AcademicCalendarService::getCurrentSession());

            // Test SessionRollOverService writes both keys
            unset($_SESSION['academic_session'], $_SESSION['current_academic_session']);
            $res = SessionRollOverService::executeRollOver('2032/2033', $this->testUserId);
            $this->assertTrue($res['success'], "RollOver failed: " . ($res['message'] ?? ''));

            $this->assertEquals('2032/2033', $_SESSION['academic_session']);
            $this->assertEquals('2032/2033', $_SESSION['current_academic_session']);

            if (file_exists($settingsFile)) {
                $saved = json_decode(file_get_contents($settingsFile), true);
                $this->assertEquals('2032/2033', $saved['current_session']);
                $this->assertEquals('2032/2033', $saved['session_year']);
            }

            $this->assertEquals('2032/2033', AcademicCalendarService::getCurrentSession());

        } finally {
            if ($originalSettings !== null) {
                file_put_contents($settingsFile, $originalSettings);
            }
            $_SESSION = $originalSession;
        }
    }

    /**
     * 5. Defect 5: Document::reupload unlinks prior physical file
     */
    public function testDocumentReuploadPurgesOldPhysicalFile(): void
    {
        // Create an old physical file inside secure_uploads
        $storageRoot = StoragePathResolver::getStorageRoot();
        $oldRelDir = '2024_2025/CS/200L/ab';
        $oldAbsDir = $storageRoot . '/' . $oldRelDir;
        if (!is_dir($oldAbsDir)) {
            mkdir($oldAbsDir, 0750, true);
        }

        $oldFileName = 'test_old_doc_' . bin2hex(random_bytes(8)) . '.pdf';
        $oldRelPath = $oldRelDir . '/' . $oldFileName;
        $oldAbsPath = $oldAbsDir . '/' . $oldFileName;

        $pdfMagicBytes = "%PDF-1.4\n%EOF\n" . str_repeat("A", 100);
        file_put_contents($oldAbsPath, $pdfMagicBytes);
        $this->assertFileExists($oldAbsPath);

        // Insert rejected document record
        $publicId = Document::generateUUID();
        $stmt = $this->db->prepare("
            INSERT INTO documents (public_id, user_id, document_name, file_path, file_hash, status, created_at)
            VALUES (?, ?, 'Old Certificate', ?, 'oldhash123', 'Rejected', NOW())
        ");
        $stmt->bind_param("sis", $publicId, $this->testUserId, $oldRelPath);
        $stmt->execute();
        $docId = $stmt->insert_id;
        $this->createdDocIds[] = $docId;
        $stmt->close();

        // Create new temporary file for reupload
        $newTmpFile = tempnam(sys_get_temp_dir(), 'reup_');
        $newPdfBytes = "%PDF-1.5\n%NewContent\n" . str_repeat("B", 120);
        file_put_contents($newTmpFile, $newPdfBytes);

        $uploadFileArray = [
            'name' => 'replacement.pdf',
            'type' => 'application/pdf',
            'tmp_name' => $newTmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($newPdfBytes)
        ];

        // Execute reupload
        $res = Document::reupload($docId, $uploadFileArray, 'replacement.pdf');
        $this->assertTrue($res['success'], "Reupload should succeed: " . ($res['message'] ?? ''));

        // Verify the old physical file has been unlinked
        $this->assertFileDoesNotExist($oldAbsPath, "Prior physical file must be unlinked upon successful reupload to prevent storage orphaned files.");

        // Clean up temp file if still present
        if (file_exists($newTmpFile)) {
            @unlink($newTmpFile);
        }
    }
}
