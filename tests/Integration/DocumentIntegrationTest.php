<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/models/User.php';
require_once __DIR__ . '/../../app/models/Document.php';

class DocumentIntegrationTest extends TestCase {
    
    protected function setUp(): void {
        resetDatabase();
    }

    protected function tearDown(): void {
        // Clean up temporary uploads created during integration tests
        $testUploadDir = __DIR__ . '/../../secure_uploads/test_artifacts/';
        if (is_dir($testUploadDir)) {
            $files = glob($testUploadDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) @unlink($file);
            }
        }
    }
    
    public function testCreateAndRetrieveDocument() {
        // 1. Create a User (Constraint: Document needs valid user_id)
        $studentData = [
            'matric_number' => 'DOC/TEST/001',
            'full_name' => 'Doc Owner',
            'level' => 400,
            'session' => '2025/2026',
            'programme' => 'Computer Science',
            'program_type' => 'Full Time',
            'mode' => 'UTME',
            'department_id' => 1,
            'email' => 'docowner@example.com'
        ];
        User::createStudent($studentData);
        $user = User::getByMatricNumber('DOC/TEST/001');
        $this->assertNotNull($user, "User creation failed");
        
        // 2. Simulate Upload (Direct DB Insert for now, or use Model method if available)
        // Document::upload involves $_FILES, which is hard to mock in Integration without actually uploading.
        // We can manually insert to test the Retrieval logic 'Integration' 
        // OR we can try to mock $_FILES superglobal if the method uses it?
        // Document::upload signature: ($user_id, $document_name, $generated_filename, $file, $classification_data)
        // It likely acts on $file['tmp_name'].
        
        // Let's test specific DB methods like getDocumentsByUserId after manual insertion to verify schema.
        global $conn;
        $stmt = $conn->prepare("INSERT INTO documents (user_id, document_name, file_name, file_path, upload_date, status, department_id) VALUES (?, ?, ?, ?, NOW(), 'Pending Quality Check', ?)");
        $docName = 'Final Year Project';
        $fileName = 'project.pdf';
        $filePath = '/uploads/docs/project.pdf';
        $deptId = 1;
        $stmt->bind_param("isssi", $user['id'], $docName, $fileName, $filePath, $deptId);
        $stmt->execute();
        $docId = $stmt->insert_id;
        $this->assertGreaterThan(0, $docId);
        
        // 3. Test Retrieval via Model
        $docs = Document::getDocumentsByUserId($user['id']);
        $this->assertIsArray($docs);
        $this->assertCount(1, $docs);
        $this->assertEquals('Final Year Project', $docs[0]['document_name']);
        
        // 4. Test GetById
        $fetchedDoc = Document::getById($docId);
        $this->assertEquals($docId, $fetchedDoc['id']);
        $this->assertEquals('Pending Quality Check', $fetchedDoc['status']);
    }
}
