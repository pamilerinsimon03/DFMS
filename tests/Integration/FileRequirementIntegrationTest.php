<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/FileRequirement.php';

class FileRequirementIntegrationTest extends TestCase {
    
    protected function setUp(): void {
        resetDatabase();
    }

    public function testCreateAndGetRequirement() {
        // Create
        $result = FileRequirement::create(1, 'Int Req', 'Test Desc', '2030-01-01', 1);
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['id']);
        $reqId = $result['id'];
        
        // Get By Department
        $reqs = FileRequirement::getByDepartment(1);
        $this->assertIsArray($reqs);
        // We expect at least the one we created (resetDatabase clears tables but seeding might run? No, resetDatabase clears all then imports dump. Dump might have some, but we created one.)
        // Actually resetDatabase creates empty tables then imports from dfms_database.sql.
        // dfms_database.sql might have data.
        
        $found = false;
        foreach ($reqs as $r) {
            if ($r['id'] == $reqId) {
                $found = true;
                $this->assertEquals('Int Req', $r['document_name']);
            }
        }
        $this->assertTrue($found, "Created requirement not found in department list");
    }
    
    public function testUpdateRequirement() {
        $result = FileRequirement::create(1, 'To Update', 'Desc', '2030-01-01', 1);
        $id = $result['id'];
        
        $update = FileRequirement::update($id, 'Updated Name', 'New Desc', '2031-01-01', 0);
        $this->assertTrue($update['success']);
        
        $fetched = FileRequirement::getById($id);
        $this->assertEquals('Updated Name', $fetched['document_name']);
        $this->assertEquals(0, $fetched['is_active']);
    }
    
    public function testDeleteRequirement() {
        $result = FileRequirement::create(1, 'To Delete', 'Desc', '2030-01-01', 1);
        $id = $result['id'];
        
        $del = FileRequirement::delete($id);
        $this->assertTrue($del['success']);
        
        $fetched = FileRequirement::getById($id);
        $this->assertNull($fetched);
    }
}
