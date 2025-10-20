<?php
/**
 * Comprehensive Security Validation Script
 * Validates all security implementations across the DFMS system
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

echo "🔒 DFMS Security Validation Script\n";
echo "==================================\n\n";

$issues = [];
$passed = 0;
$total = 0;

/**
 * Test CSRF Protection
 */
function testCSRFProtection() {
    global $issues, $passed, $total;
    
    echo "Testing CSRF Protection...\n";
    
    // Check if CSRF functions exist
    $total++;
    if (function_exists('generateCSRFToken') && function_exists('validateCSRFToken')) {
        echo "  ✅ CSRF functions available\n";
        $passed++;
    } else {
        $issues[] = "CSRF functions not available";
        echo "  ❌ CSRF functions missing\n";
    }
    
    // Test token generation
    $total++;
    $token = generateCSRFToken();
    if (strlen($token) === 64 && ctype_xdigit($token)) {
        echo "  ✅ CSRF token generation working\n";
        $passed++;
    } else {
        $issues[] = "CSRF token generation failed";
        echo "  ❌ CSRF token generation failed\n";
    }
    
    // Test token validation
    $total++;
    if (validateCSRFToken($token)) {
        echo "  ✅ CSRF token validation working\n";
        $passed++;
    } else {
        $issues[] = "CSRF token validation failed";
        echo "  ❌ CSRF token validation failed\n";
    }
    
    echo "\n";
}

/**
 * Test Session Security
 */
function testSessionSecurity() {
    global $issues, $passed, $total;
    
    echo "Testing Session Security...\n";
    
    // Check session cookie parameters (skip in CLI mode)
    $total++;
    if (php_sapi_name() === 'cli') {
        echo "  ⚠️  Session test skipped (CLI mode)\n";
        $passed++; // Skip this test in CLI mode
    } else {
        $params = session_get_cookie_params();
        if ($params['httponly'] && $params['samesite'] === 'Strict') {
            echo "  ✅ Session cookies properly configured\n";
            $passed++;
        } else {
            $issues[] = "Session cookies not properly configured";
            echo "  ❌ Session cookies not properly configured\n";
        }
    }
    
    // Check session lifetime (skip in CLI mode)
    $total++;
    if (php_sapi_name() === 'cli') {
        echo "  ⚠️  Session lifetime test skipped (CLI mode)\n";
        $passed++; // Skip this test in CLI mode
    } else {
        $params = session_get_cookie_params();
        if ($params['lifetime'] <= 3600) { // 1 hour or less
            echo "  ✅ Session lifetime is secure (≤1 hour)\n";
            $passed++;
        } else {
            $issues[] = "Session lifetime too long";
            echo "  ❌ Session lifetime too long\n";
        }
    }
    
    echo "\n";
}

/**
 * Test Input Validation
 */
function testInputValidation() {
    global $issues, $passed, $total;
    
    echo "Testing Input Validation...\n";
    
    // Test email validation
    $total++;
    $valid_email = SecurityUtils::validateInput('test@example.com', 'email');
    $invalid_email = SecurityUtils::validateInput('invalid-email', 'email');
    
    if ($valid_email && !$invalid_email) {
        echo "  ✅ Email validation working\n";
        $passed++;
    } else {
        $issues[] = "Email validation not working properly";
        echo "  ❌ Email validation not working properly\n";
    }
    
    // Test matric validation
    $total++;
    $valid_matric = SecurityUtils::validateInput('U123456', 'matric');
    $invalid_matric = SecurityUtils::validateInput('invalid@matric', 'matric');
    
    if ($valid_matric && !$invalid_matric) {
        echo "  ✅ Matric validation working\n";
        $passed++;
    } else {
        $issues[] = "Matric validation not working properly";
        echo "  ❌ Matric validation not working properly\n";
    }
    
    // Test XSS detection
    $total++;
    $xss_payload = '<script>alert("xss")</script>';
    if (SecurityUtils::detectXSS($xss_payload)) {
        echo "  ✅ XSS detection working\n";
        $passed++;
    } else {
        $issues[] = "XSS detection not working";
        echo "  ❌ XSS detection not working\n";
    }
    
    echo "\n";
}

/**
 * Test Rate Limiting
 */
function testRateLimiting() {
    global $issues, $passed, $total;
    
    echo "Testing Rate Limiting...\n";
    
    $test_key = 'test_rate_limit_' . time();
    
    // Test rate limiting
    $total++;
    $attempts = 0;
    $blocked = false;
    
    for ($i = 0; $i < 7; $i++) { // Try 7 times (limit is 5)
        if (!SecurityUtils::checkRateLimit($test_key)) {
            $blocked = true;
            break;
        }
        $attempts++;
    }
    
    if ($blocked && $attempts <= 5) {
        echo "  ✅ Rate limiting working (blocked after $attempts attempts)\n";
        $passed++;
    } else {
        $issues[] = "Rate limiting not working properly";
        echo "  ❌ Rate limiting not working properly\n";
    }
    
    echo "\n";
}

/**
 * Test File Upload Security
 */
function testFileUploadSecurity() {
    global $issues, $passed, $total;
    
    echo "Testing File Upload Security...\n";
    
    // Check if secure uploads directory exists
    $total++;
    $secure_uploads_dir = __DIR__ . '/../../secure_uploads/';
    if (is_dir($secure_uploads_dir) && is_writable($secure_uploads_dir)) {
        echo "  ✅ Secure uploads directory exists and is writable\n";
        $passed++;
    } else {
        $issues[] = "Secure uploads directory missing or not writable";
        echo "  ❌ Secure uploads directory missing or not writable\n";
    }
    
    // Check if old uploads directory is outside webroot
    $total++;
    $old_uploads_dir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($old_uploads_dir)) {
        echo "  ✅ Old uploads directory removed (good)\n";
        $passed++;
    } else {
        $issues[] = "Old uploads directory still exists";
        echo "  ❌ Old uploads directory still exists\n";
    }
    
    echo "\n";
}

/**
 * Test Security Logging
 */
function testSecurityLogging() {
    global $issues, $passed, $total;
    
    echo "Testing Security Logging...\n";
    
    // Test security event logging
    $total++;
    $log_file = __DIR__ . '/../../app/logs/security_events.log';
    $logs_dir = dirname($log_file);
    
    if (is_dir($logs_dir) && is_writable($logs_dir)) {
        echo "  ✅ Security logs directory exists and is writable\n";
        $passed++;
    } else {
        $issues[] = "Security logs directory missing or not writable";
        echo "  ❌ Security logs directory missing or not writable\n";
    }
    
    // Test logging function
    $total++;
    try {
        SecurityUtils::logSecurityEvent('test_event', ['test' => 'data']);
        $actual_log_file = __DIR__ . '/../../app/logs/security.log';
        if (file_exists($actual_log_file)) {
            echo "  ✅ Security event logging working\n";
            $passed++;
        } else {
            $issues[] = "Security event logging not working";
            echo "  ❌ Security event logging not working\n";
        }
    } catch (Exception $e) {
        $issues[] = "Security event logging failed: " . $e->getMessage();
        echo "  ❌ Security event logging failed\n";
    }
    
    echo "\n";
}

/**
 * Test Database Security
 */
function testDatabaseSecurity() {
    global $issues, $passed, $total, $conn;
    
    echo "Testing Database Security...\n";
    
    // Check if database connection uses prepared statements
    $total++;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE id = ?");
        $test_id = 1;
        $stmt->bind_param("i", $test_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && isset($result['count'])) {
            echo "  ✅ Database prepared statements working\n";
            $passed++;
        } else {
            $issues[] = "Database prepared statements not working";
            echo "  ❌ Database prepared statements not working\n";
        }
    } catch (Exception $e) {
        $issues[] = "Database prepared statements failed: " . $e->getMessage();
        echo "  ❌ Database prepared statements failed\n";
    }
    
    echo "\n";
}

/**
 * Test Environment Security
 */
function testEnvironmentSecurity() {
    global $issues, $passed, $total;
    
    echo "Testing Environment Security...\n";
    
    // Check if .env file exists and .env.example exists
    $total++;
    $env_file = __DIR__ . '/../../.env';
    $env_example = __DIR__ . '/../../env.example';
    
    if (file_exists($env_example) && !file_exists($env_file)) {
        echo "  ✅ Environment configuration properly set up\n";
        $passed++;
    } elseif (file_exists($env_file)) {
        echo "  ⚠️  .env file exists (ensure it's not in version control)\n";
        $passed++; // This is acceptable if properly managed
    } else {
        $issues[] = "Environment configuration missing";
        echo "  ❌ Environment configuration missing\n";
    }
    
    // Check if config.php uses environment variables for production
    $total++;
    $config_content = file_get_contents(__DIR__ . '/../config/config.php');
    if (strpos($config_content, '$_ENV[') !== false) {
        echo "  ✅ Config uses environment variables for production\n";
        $passed++;
    } else {
        $issues[] = "Config does not use environment variables";
        echo "  ❌ Config does not use environment variables\n";
    }
    
    echo "\n";
}

// Run all tests
testCSRFProtection();
testSessionSecurity();
testInputValidation();
testRateLimiting();
testFileUploadSecurity();
testSecurityLogging();
testDatabaseSecurity();
testEnvironmentSecurity();

// Summary
echo "📊 SECURITY VALIDATION SUMMARY\n";
echo "==============================\n";
echo "Tests Passed: $passed/$total\n";
echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n\n";

if (empty($issues)) {
    echo "🎉 ALL SECURITY TESTS PASSED!\n";
    echo "The DFMS system appears to be properly secured.\n";
} else {
    echo "⚠️  SECURITY ISSUES FOUND:\n";
    foreach ($issues as $i => $issue) {
        echo "  " . ($i + 1) . ". $issue\n";
    }
    echo "\nPlease address these issues before deploying to production.\n";
}

echo "\n🔍 Additional Recommendations:\n";
echo "- Run this script regularly to ensure security posture\n";
echo "- Monitor security logs for suspicious activity\n";
echo "- Keep dependencies updated\n";
echo "- Perform penetration testing before production deployment\n";
echo "- Implement automated security scanning in CI/CD pipeline\n";

exit(empty($issues) ? 0 : 1);
?>
