<?php
use PHPUnit\Framework\TestCase;

class ControllerSecurityTest extends TestCase {
    
    private $baseUrl = 'http://localhost:9000/app/controllers/';
    
    /**
     * Helper to check if a URL redirects to login or forbids access
     */
    private function checkProtectedEndpoint($filename) {
        $url = $this->baseUrl . $filename;
        
        // Context to NOT follow redirects and fetch headers
        $options = [
            'http' => [
                'method' => 'GET',
                'follow_location' => 0,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($options);
        
        $content = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        
        // Logic:
        // 1. Check for 302 redirect
        // 2. Check content for redirection script or "Login"
        // 3. Or check Location header
        
        $isRedirect = false;
        foreach ($headers as $header) {
            if (stripos($header, 'Location:') !== false) {
                // Check if redirecting to login or index
                if (stripos($header, 'index.php') !== false || stripos($header, 'login.php') !== false) {
                    $isRedirect = true;
                }
            }
        }
        
        // Also check status code
        $statusLine = $headers[0] ?? '';
        $is302 = stripos($statusLine, '302') !== false;
        
        $this->assertTrue($isRedirect || $is302, "Controller $filename is NOT protected! It returned status: $statusLine and no login redirect.");
    }
    
    public function testDashboardProtected() {
        $this->checkProtectedEndpoint('dashboard.php');
    }
    
    public function testAdminProtected() {
        $this->checkProtectedEndpoint('admin.php');
    }
    
    public function testUploadProtected() {
        $this->checkProtectedEndpoint('uploads.php');
    }
    
    public function testSettingsProtected() {
        $this->checkProtectedEndpoint('settings.php');
    }
    
    public function testFileManagementProtected() {
        $this->checkProtectedEndpoint('file_management.php');
    }

    public function testPublicRouteAccessible() {
        $url = 'http://localhost:9000/public/index.php';
        $content = file_get_contents($url);
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Login', $content);
    }
}
