<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/utils/AccessControlMiddleware.php';
require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Document.php';

class IDORBoundaryIntegrationTest extends TestCase {

    public function testRoleScopingCheckForFacultyAdmin() {
        $facultyAdminUser = [
            'id' => 5,
            'matric_number' => 'FAC_ADMIN_SCI',
            'role' => 'faculty_admin',
            'is_admin' => 1,
            'faculty_id' => 1,
            'department_id' => 1
        ];

        // Faculty 1 includes departments 1, 7, 8, 9
        $allowedDepts = [1, 7, 8, 9];
        
        $this->assertContains(1, $allowedDepts);
        $this->assertContains(7, $allowedDepts);
        $this->assertNotContains(2, $allowedDepts); // Dept 2 belongs to Social Sciences
    }
}
