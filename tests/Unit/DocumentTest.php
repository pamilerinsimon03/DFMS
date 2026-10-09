<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/Document.php';

class DocumentTest extends TestCase {

    private $originalConn;

    protected function setUp(): void {
        $this->originalConn = $GLOBALS['conn'] ?? null;
    }

    protected function tearDown(): void {
        if ($this->originalConn !== null) {
            $GLOBALS['conn'] = $this->originalConn;
        }
    }

    public function testGetById() {
        // Mock Result
        $mockResult = $this->createMock(mysqli_result::class);
        $expectedDoc = ['id' => 1, 'title' => 'Test Doc'];
        $mockResult->method('fetch_assoc')->willReturn($expectedDoc);

        // Mock Statement
        $mockStmt = $this->getMockBuilder(mysqli_stmt::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('bind_param')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult); // Compatible with mysqlnd

        // Mock Connection
        $mockConn = $this->getMockBuilder(mysqli::class)
                         ->disableOriginalConstructor()
                         ->getMock();
        $mockConn->method('prepare')->willReturn($mockStmt);
        
        // Inject
        $GLOBALS['conn'] = $mockConn;

        $doc = Document::getById(1);
        $this->assertEquals($expectedDoc, $doc);
    }
}
