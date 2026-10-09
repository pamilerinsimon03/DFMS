<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/User.php';

class UserIntegrationTest extends TestCase {
    
    protected function setUp(): void {
        resetDatabase();
    }
    
    public function testCreateAndRetrieveStudent() {
        // Create
        $studentData = [
            'matric_number' => 'INT/2026/001',
            'full_name' => 'Integration User',
            'level' => 100,
            'session' => '2025/2026',
            'programme' => 'Computer Science', // IDs seeded in SQL
            'program_type' => 'Full Time',
            'mode' => 'UTME',
            'department_id' => 1,
            'email' => 'intuser@example.com'
        ];
        
        $result = User::createStudent($studentData);
        $this->assertTrue($result['success'], "Failed to create student: " . ($result['message'] ?? ''));
        
        // Retrieve
        $user = User::getByMatricNumber('INT/2026/001');
        $this->assertNotNull($user);
        $this->assertEquals('Integration User', $user['full_name']);
        $this->assertEquals(1, $user['department_id']);
    }
    
    public function testUpdateProfile() {
        // Seed a user first (or use one from dump if known, but safer to create)
        $studentData = [
            'matric_number' => 'INT/2026/UPD',
            'full_name' => 'Original Name',
            'level' => 100,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'program_type' => 'Full Time',
            'mode' => 'UTME',
            'department_id' => 1,
            'email' => 'update@example.com'
        ];
        User::createStudent($studentData);
        $user = User::getByMatricNumber('INT/2026/UPD');
        
        // Update
        $success = User::updateProfile(
            $user['id'], 
            'Updated Name', 
            200, 
            'Computer Science', 
            '2026/2027', 
            1, 
            'Full Time', 
            'Direct Entry'
        );
        
        $this->assertTrue($success, "Profile update failed");
        
        // Verify
        $updatedUser = User::getById($user['id']);
        $this->assertEquals('Updated Name', $updatedUser['full_name']);
        $this->assertEquals(200, $updatedUser['level']);
        $this->assertEquals('Direct Entry', $updatedUser['mode']);
    }
}
