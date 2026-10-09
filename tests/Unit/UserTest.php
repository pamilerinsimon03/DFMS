<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/User.php';

class UserTest extends TestCase {
    
    private $originalConn;

    protected function setUp(): void {
        $this->originalConn = $GLOBALS['conn'] ?? null;
    }

    protected function tearDown(): void {
        if ($this->originalConn !== null) {
            $GLOBALS['conn'] = $this->originalConn;
        }
    }

    // Test pure logic
    public function testGeneratePassword() {
        $fullName = "John Doe";
        $password = User::generatePassword($fullName);
        $this->assertEquals("doe12345", $password);

        $fullName = "Jane";
        $password = User::generatePassword($fullName);
        $this->assertEquals("jane12345", $password);

        $fullName = "A B C D";
        $password = User::generatePassword($fullName);
        $this->assertEquals("d12345", $password);
    }
    
    // Test database interaction with Mock
    public function testCreateStudent() {
        // Mock Result
        $mockResult = $this->createMock(mysqli_result::class);
        $mockResult->method('fetch_assoc')->willReturn(null); // No existing user

        // Mock Statement
        $mockStmt = $this->getMockBuilder(mysqli_stmt::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);
        
        // Mock Connection
        $mockConn = $this->getMockBuilder(mysqli::class)
                         ->disableOriginalConstructor()
                         ->getMock();
                         
        // We need distinct statements strictly? 
        // Or can we reuse the same mock statement for simplicity if the behavior is compatible?
        // First call: getByMatricNumber -> prepare -> bind -> execute -> get_result -> fetch_assoc
        // Second call: INSERT -> prepare -> bind -> execute
        // If we reuse $mockStmt, checking get_result on the second call (INSERT) shouldn't happen, so it's fine.
        // Let's try reusing the same statement to reduce complexity and potential "consecutive" matching error.
        
        $mockConn->method('prepare')->willReturn($mockStmt); // Always return our good mock stmt
        $mockConn->method('query')->willReturn(true);
        
        // Inject Mock
        $GLOBALS['conn'] = $mockConn;
        
        $studentData = [
            'matric_number' => 'TEST/2026/001',
            'full_name' => 'Test User',
            'level' => 100,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'program_type' => 'Full Time',
            'mode' => 'UTME',
            'department_id' => 1,
            'email' => 'testuser@example.com'
        ];

        $result = User::createStudent($studentData);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('password', $result);
    }
}
