<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/utils/AccessScope.php';
require_once __DIR__ . '/../../app/utils/AccessControlMiddleware.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Document.php';

class AuthorizationMatrixTest extends TestCase
{
    protected function setUp(): void
    {
        resetDatabase();
    }

    /**
     * Test role normalization and hierarchy resolution
     */
    public function testRoleScopingCheckForFacultyAdminAndHierarchy()
    {
        $superAdmin = [
            'id' => 1,
            'matric_number' => 'SUPER_ADMIN',
            'role' => 'admin',
            'is_admin' => 1
        ];
        $this->assertEquals('super_admin', AccessScope::resolveRole($superAdmin));

        $facultyAdmin = [
            'id' => 5,
            'matric_number' => 'FAC_ADMIN_SCI',
            'role' => 'faculty_admin',
            'is_admin' => 1,
            'faculty_id' => 1,
            'department_id' => 1
        ];
        $this->assertEquals('faculty_admin', AccessScope::resolveRole($facultyAdmin));

        $deptAdmin = [
            'id' => 6,
            'matric_number' => 'DEPT_ADMIN_CS',
            'role' => 'department_admin',
            'is_admin' => 1,
            'department_id' => 1
        ];
        $this->assertEquals('department_admin', AccessScope::resolveRole($deptAdmin));

        $student = [
            'id' => 10,
            'matric_number' => 'STUDENT_001',
            'role' => 'student',
            'is_admin' => 0,
            'department_id' => 1
        ];
        $this->assertEquals('student', AccessScope::resolveRole($student));
    }

    /**
     * Test centralized permissions config and route inheritance
     */
    public function testRoutePermissionsMatrix()
    {
        $permissions = require __DIR__ . '/../../app/config/permissions.php';
        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('hierarchy', $permissions);
        $this->assertArrayHasKey('routes', $permissions);
        $this->assertArrayHasKey('public_allowlist', $permissions);

        // Check public allowlist
        $this->assertContains('login.php', $permissions['public_allowlist']);
        $this->assertContains('activate.php', $permissions['public_allowlist']);

        // Check super admin routes
        $this->assertEquals(['super_admin'], $permissions['routes']['backup.php']);
        $this->assertEquals(['super_admin'], $permissions['routes']['audit_log_viewer.php']);

        // Check admin routes requiring minimum department_admin
        $this->assertContains('department_admin', $permissions['routes']['admin.php']);
        $this->assertContains('department_admin', $permissions['routes']['verifications.php']);
        $this->assertContains('department_admin', $permissions['routes']['students.php']);

        // Check student routes
        $this->assertEquals(['student'], $permissions['routes']['uploads.php']);
        $this->assertEquals(['student'], $permissions['routes']['profile.php']);

        // Hierarchy inheritance check:
        // super_admin inherits faculty_admin, department_admin, student
        $hierarchy = $permissions['hierarchy'];
        $this->assertContains('faculty_admin', $hierarchy['super_admin']);
        $this->assertContains('department_admin', $hierarchy['super_admin']);
        $this->assertContains('department_admin', $hierarchy['faculty_admin']);
    }

    /**
     * Test non-sequential UUIDv4 generation, storage, and resolution
     */
    public function testPublicIdUuidGenerationAndResolution()
    {
        $uuidRegex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        // 1. User UUID generation
        $studentData = [
            'matric_number' => 'MATRIC/UUID/001',
            'full_name' => 'UUID Test Student',
            'level' => 300,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'program_type' => 'Full Time',
            'mode' => 'UTME',
            'department_id' => 1,
            'email' => 'uuid_student@example.com'
        ];
        $res = User::createStudent($studentData);
        $this->assertTrue($res['success']);

        $user = User::getByMatricNumber('MATRIC/UUID/001');
        $this->assertNotNull($user);
        $this->assertNotEmpty($user['public_id']);
        $this->assertMatchesRegularExpression($uuidRegex, $user['public_id']);

        // Test User ID resolution
        $this->assertEquals($user['id'], User::resolveId($user['public_id']));
        $this->assertEquals($user['id'], User::resolveId($user['id']));

        $userByPublicId = User::findByPublicId($user['public_id']);
        $this->assertNotNull($userByPublicId);
        $this->assertEquals($user['id'], $userByPublicId['id']);

        // 2. Document UUID generation
        global $conn;
        $docPublicId = Document::generateUUID();
        $this->assertMatchesRegularExpression($uuidRegex, $docPublicId);

        $stmt = $conn->prepare("INSERT INTO documents (public_id, user_id, document_name, file_name, file_path, status, department_id, upload_date) VALUES (?, ?, 'UUID Test Doc', 'uuid.pdf', 'secure_uploads/uuid.pdf', 'Pending Quality Check', ?, NOW())");
        $deptId = (int)$user['department_id'];
        $stmt->bind_param("sii", $docPublicId, $user['id'], $deptId);
        $stmt->execute();
        $docId = $stmt->insert_id;
        $stmt->close();

        $this->assertGreaterThan(0, $docId);
        $this->assertEquals($docId, Document::resolveId($docPublicId));
        $this->assertEquals($docId, Document::resolveId($docId));

        $docByPublicId = Document::findByPublicId($docPublicId);
        $this->assertNotNull($docByPublicId);
        $this->assertEquals($docId, $docByPublicId['id']);
        $this->assertEquals($docPublicId, $docByPublicId['public_id']);
    }

    /**
     * Test object-level scoping for documents across Super Admin, Faculty Admin, Department Admin, and Student
     */
    public function testDocumentObjectScopingAcrossAllRoles()
    {
        global $conn;

        // Create Students in different departments/faculties
        // Dept 1 = Computer Science (Faculty 1: Science)
        // Dept 2 = Psychology (Faculty 2: Social Sciences)
        // Dept 7 = Computer Science & AI (Faculty 6: Computing)
        User::createStudent([
            'matric_number' => 'STU_D1',
            'full_name' => 'Student Dept1',
            'level' => 100,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'department_id' => 1,
            'email' => 'stu1@example.com'
        ]);
        User::createStudent([
            'matric_number' => 'STU_D1_B',
            'full_name' => 'Student Dept1 Peer',
            'level' => 100,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'department_id' => 1,
            'email' => 'stu1b@example.com'
        ]);
        User::createStudent([
            'matric_number' => 'STU_D2',
            'full_name' => 'Student Dept2',
            'level' => 100,
            'session' => '2025/2026',
            'programme' => 'Psychology',
            'department_id' => 2,
            'email' => 'stu2@example.com'
        ]);

        $stu1 = User::getByMatricNumber('STU_D1');
        $stu1b = User::getByMatricNumber('STU_D1_B');
        $stu2 = User::getByMatricNumber('STU_D2');

        // Create test documents for each student
        $docUuid1 = Document::generateUUID();
        $docUuid1b = Document::generateUUID();
        $docUuid2 = Document::generateUUID();

        $stmt = $conn->prepare("INSERT INTO documents (public_id, user_id, document_name, file_name, file_path, status, department_id, upload_date) VALUES (?, ?, ?, ?, ?, 'Pending Approval', ?, NOW())");
        
        $name1 = 'Doc Dept1'; $fn1 = 'd1.pdf'; $fp1 = 'p1.pdf'; $d1 = 1;
        $stmt->bind_param("sisssi", $docUuid1, $stu1['id'], $name1, $fn1, $fp1, $d1);
        $stmt->execute();
        $doc1Id = $stmt->insert_id;

        $name1b = 'Doc Dept1 Peer'; $fn1b = 'd1b.pdf'; $fp1b = 'p1b.pdf'; $d1 = 1;
        $stmt->bind_param("sisssi", $docUuid1b, $stu1b['id'], $name1b, $fn1b, $fp1b, $d1);
        $stmt->execute();
        $doc1bId = $stmt->insert_id;

        $name2 = 'Doc Dept2'; $fn2 = 'd2.pdf'; $fp2 = 'p2.pdf'; $d2 = 2;
        $stmt->bind_param("sisssi", $docUuid2, $stu2['id'], $name2, $fn2, $fp2, $d2);
        $stmt->execute();
        $doc2Id = $stmt->insert_id;
        $stmt->close();

        $doc1 = Document::getById($doc1Id);
        $doc1b = Document::getById($doc1bId);
        $doc2 = Document::getById($doc2Id);

        // Define actors
        $deptAdmin1 = [
            'id' => 201,
            'matric_number' => 'ADMIN_D1',
            'role' => 'department_admin',
            'is_admin' => 1,
            'department_id' => 1
        ];
        $deptAdmin2 = [
            'id' => 202,
            'matric_number' => 'ADMIN_D2',
            'role' => 'department_admin',
            'is_admin' => 1,
            'department_id' => 2
        ];
        $facultyAdmin1 = [
            'id' => 203,
            'matric_number' => 'FAC_SCI',
            'role' => 'faculty_admin',
            'is_admin' => 1,
            'faculty_id' => 1, // Faculty of Science (includes dept 1)
            'department_id' => 1
        ];
        $facultyAdmin2 = [
            'id' => 204,
            'matric_number' => 'FAC_SOCSCI',
            'role' => 'faculty_admin',
            'is_admin' => 1,
            'faculty_id' => 2, // Faculty of Social Sciences (includes dept 2, 4)
            'department_id' => 2
        ];
        $superAdmin = [
            'id' => 1,
            'matric_number' => 'SUPER_ADMIN',
            'role' => 'super_admin',
            'is_admin' => 1
        ];

        // 1. Student Scoping:
        // Student can access own doc, but CANNOT access peer's doc (even in same department)
        $this->assertTrue(AccessScope::isDocumentInScope($stu1, $doc1));
        $this->assertFalse(AccessScope::isDocumentInScope($stu1, $doc1b));
        $this->assertFalse(AccessScope::isDocumentInScope($stu1, $doc2));

        $this->assertNotNull(Document::getByIdScoped($doc1['public_id'], $stu1));
        $this->assertNull(Document::getByIdScoped($doc1b['public_id'], $stu1));
        $this->assertNull(Document::getByIdScoped($doc2['public_id'], $stu1));

        // 2. Department Admin Scoping:
        // Admin of Dept 1 can access all documents in Dept 1, but NOT Dept 2
        $this->assertTrue(AccessScope::isDocumentInScope($deptAdmin1, $doc1));
        $this->assertTrue(AccessScope::isDocumentInScope($deptAdmin1, $doc1b));
        $this->assertFalse(AccessScope::isDocumentInScope($deptAdmin1, $doc2));

        $this->assertNotNull(Document::getByIdScoped($doc1['public_id'], $deptAdmin1));
        $this->assertNotNull(Document::getByIdScoped($doc1b['public_id'], $deptAdmin1));
        $this->assertNull(Document::getByIdScoped($doc2['public_id'], $deptAdmin1));

        // Admin of Dept 2 can access Doc 2, but NOT Doc 1
        $this->assertTrue(AccessScope::isDocumentInScope($deptAdmin2, $doc2));
        $this->assertFalse(AccessScope::isDocumentInScope($deptAdmin2, $doc1));
        $this->assertNotNull(Document::getByIdScoped($doc2['public_id'], $deptAdmin2));
        $this->assertNull(Document::getByIdScoped($doc1['public_id'], $deptAdmin2));

        // 3. Faculty Admin Scoping:
        // Faculty of Science covers Dept 1 (CS), but NOT Dept 2 (Psychology)
        $this->assertTrue(AccessScope::isDocumentInScope($facultyAdmin1, $doc1));
        $this->assertTrue(AccessScope::isDocumentInScope($facultyAdmin1, $doc1b));
        $this->assertFalse(AccessScope::isDocumentInScope($facultyAdmin1, $doc2));

        $this->assertNotNull(Document::getByIdScoped($doc1['public_id'], $facultyAdmin1));
        $this->assertNull(Document::getByIdScoped($doc2['public_id'], $facultyAdmin1));

        // Faculty of Social Sciences covers Dept 2, but NOT Dept 1
        $this->assertTrue(AccessScope::isDocumentInScope($facultyAdmin2, $doc2));
        $this->assertFalse(AccessScope::isDocumentInScope($facultyAdmin2, $doc1));

        // 4. Super Admin Scoping: Universal access
        $this->assertTrue(AccessScope::isDocumentInScope($superAdmin, $doc1));
        $this->assertTrue(AccessScope::isDocumentInScope($superAdmin, $doc1b));
        $this->assertTrue(AccessScope::isDocumentInScope($superAdmin, $doc2));

        $this->assertNotNull(Document::getByIdScoped($doc1['public_id'], $superAdmin));
        $this->assertNotNull(Document::getByIdScoped($doc2['public_id'], $superAdmin));
    }

    /**
     * Test object-level scoping for user profiles
     */
    public function testUserObjectScopingAcrossAllRoles()
    {
        User::createStudent([
            'matric_number' => 'STU_USER_D1',
            'full_name' => 'Student Profile Dept1',
            'level' => 200,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'department_id' => 1,
            'email' => 'prof1@example.com'
        ]);
        User::createStudent([
            'matric_number' => 'STU_USER_D2',
            'full_name' => 'Student Profile Dept2',
            'level' => 200,
            'session' => '2025/2026',
            'programme' => 'Psychology',
            'department_id' => 2,
            'email' => 'prof2@example.com'
        ]);

        $stu1 = User::getByMatricNumber('STU_USER_D1');
        $stu2 = User::getByMatricNumber('STU_USER_D2');

        $deptAdmin1 = ['id' => 301, 'role' => 'department_admin', 'is_admin' => 1, 'department_id' => 1];
        $facAdmin1 = ['id' => 302, 'role' => 'faculty_admin', 'is_admin' => 1, 'faculty_id' => 1, 'department_id' => 1];
        $superAdmin = ['id' => 1, 'matric_number' => 'SUPER_ADMIN', 'role' => 'super_admin', 'is_admin' => 1];

        // Student can view own profile, but cannot view other student's profile
        $this->assertTrue(AccessScope::isUserInScope($stu1, $stu1));
        $this->assertFalse(AccessScope::isUserInScope($stu1, $stu2));
        $this->assertNotNull(User::getByIdScoped($stu1['public_id'], $stu1));
        $this->assertNull(User::getByIdScoped($stu2['public_id'], $stu1));

        // Dept Admin 1 can view student in Dept 1, but cannot view student in Dept 2
        $this->assertTrue(AccessScope::isUserInScope($deptAdmin1, $stu1));
        $this->assertFalse(AccessScope::isUserInScope($deptAdmin1, $stu2));
        $this->assertNotNull(User::getByIdScoped($stu1['public_id'], $deptAdmin1));
        $this->assertNull(User::getByIdScoped($stu2['public_id'], $deptAdmin1));

        // Faculty Admin 1 (Science) can view student in Dept 1, but cannot view student in Dept 2 (Social Sciences)
        $this->assertTrue(AccessScope::isUserInScope($facAdmin1, $stu1));
        $this->assertFalse(AccessScope::isUserInScope($facAdmin1, $stu2));
        $this->assertNotNull(User::getByIdScoped($stu1['public_id'], $facAdmin1));
        $this->assertNull(User::getByIdScoped($stu2['public_id'], $facAdmin1));

        // Super Admin can view any student
        $this->assertTrue(AccessScope::isUserInScope($superAdmin, $stu1));
        $this->assertTrue(AccessScope::isUserInScope($superAdmin, $stu2));
        $this->assertNotNull(User::getByIdScoped($stu2['public_id'], $superAdmin));
    }

    /**
     * Test query predicate builders for SQL injection resistance and correct boundaries
     */
    public function testQueryLevelScopingFilters()
    {
        $superAdmin = ['id' => 1, 'matric_number' => 'SUPER_ADMIN', 'role' => 'super_admin', 'is_admin' => 1];
        $facAdmin = ['id' => 2, 'role' => 'faculty_admin', 'is_admin' => 1, 'faculty_id' => 1, 'department_id' => 1];
        $deptAdmin = ['id' => 3, 'role' => 'department_admin', 'is_admin' => 1, 'department_id' => 1];
        $student = ['id' => 4, 'role' => 'student', 'is_admin' => 0, 'department_id' => 1];

        // Super Admin
        $scope = AccessScope::buildDocumentScopeFilter($superAdmin);
        $this->assertEquals('1=1', $scope['sql']);
        $this->assertEmpty($scope['params']);

        // Department Admin
        $scope = AccessScope::buildDocumentScopeFilter($deptAdmin, null, 'd.');
        $this->assertEquals('d.department_id = ?', $scope['sql']);
        $this->assertEquals([1], $scope['params']);

        // Faculty Admin
        $scope = AccessScope::buildDocumentScopeFilter($facAdmin, null, 'u.');
        $this->assertStringContainsString('u.department_id IN', $scope['sql']);
        $this->assertContains(1, $scope['params']);

        // Student
        $scope = AccessScope::buildDocumentScopeFilter($student, null, 'd.');
        $this->assertEquals('d.user_id = ?', $scope['sql']);
        $this->assertEquals([4], $scope['params']);
    }

    /**
     * Test strict state machine transitions in Document::updateStatus
     */
    public function testDocumentStateMachineTransitions()
    {
        global $conn;

        User::createStudent([
            'matric_number' => 'STU_SM_001',
            'full_name' => 'State Machine Test Student',
            'level' => 400,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'department_id' => 1,
            'email' => 'sm_student@example.com'
        ]);
        $student = User::getByMatricNumber('STU_SM_001');

        $docPublicId = Document::generateUUID();
        $stmt = $conn->prepare("INSERT INTO documents (public_id, user_id, document_name, file_name, file_path, status, department_id, upload_date) VALUES (?, ?, 'State Machine Doc', 'sm.pdf', 'secure_uploads/sm.pdf', 'Pending Quality Check', ?, NOW())");
        $deptId = (int)$student['department_id'];
        $stmt->bind_param("sii", $docPublicId, $student['id'], $deptId);
        $stmt->execute();
        $docId = $stmt->insert_id;
        $stmt->close();

        $deptAdmin1 = [
            'id' => 501,
            'matric_number' => 'ADMIN_SM_D1',
            'role' => 'department_admin',
            'is_admin' => 1,
            'department_id' => 1
        ];
        $deptAdmin2 = [
            'id' => 502,
            'matric_number' => 'ADMIN_SM_D2',
            'role' => 'department_admin',
            'is_admin' => 1,
            'department_id' => 2
        ];

        // 1. Invalid Transition: Pending Quality Check -> Approved (skipping pipeline)
        $result = Document::updateStatus($docPublicId, 'Approved', null, $deptAdmin1);
        $this->assertFalse($result, "Should not allow Pending Quality Check -> Approved directly");
        $doc = Document::getById($docId);
        $this->assertEquals('Pending Quality Check', $doc['status']);

        // 2. Valid Pipeline Transition: Pending Quality Check -> Pending Approval
        $result = Document::updateStatus($docPublicId, 'pipeline_pass', null, null);
        $this->assertTrue($result, "Pipeline pass should move to Pending Approval");
        $doc = Document::getById($docId);
        $this->assertEquals('Pending Approval', $doc['status']);

        // 3. Invalid Rejection: Rejection with empty reason
        $result = Document::updateStatus($docPublicId, 'reject', '', $deptAdmin1);
        $this->assertFalse($result, "Rejection without a valid reason must be rejected");
        $doc = Document::getById($docId);
        $this->assertEquals('Pending Approval', $doc['status']);

        // 4. Out-of-scope Reviewer Attempt
        $result = Document::updateStatus($docPublicId, 'Approved', null, $deptAdmin2);
        $this->assertFalse($result, "Out-of-scope reviewer must not update document status");
        $doc = Document::getById($docId);
        $this->assertEquals('Pending Approval', $doc['status']);

        // 5. Valid In-scope Reviewer Approval
        $result = Document::updateStatus($docPublicId, 'Approved', null, $deptAdmin1);
        $this->assertTrue($result, "In-scope reviewer should approve Pending Approval document");
        $doc = Document::getById($docId);
        $this->assertEquals('Approved', $doc['status']);

        // 6. Invalid Transition: Approved -> Pending Quality Check
        $result = Document::updateStatus($docPublicId, 'reupload', null, $deptAdmin1);
        $this->assertFalse($result, "Approved documents cannot transition to Pending Quality Check directly");
    }
}
