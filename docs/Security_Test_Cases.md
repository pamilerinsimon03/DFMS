# DFMS Security Test Cases

## Overview
This document provides test cases for validating the security fixes implemented in the DFMS system. These tests should be run after implementing each security patch to ensure vulnerabilities are properly mitigated.

---

## Test Case 1: Hardcoded Credentials Fix

### Objective
Verify that database credentials are no longer hardcoded in source code.

### Pre-conditions
- Environment variables are properly configured
- `.env` file exists with secure credentials
- Database connection is working

### Test Steps
1. **Check Source Code**
   - Open `app/config/config.php`
   - Verify no hardcoded credentials are present
   - Confirm environment variables are used

2. **Test Database Connection**
   - Access the application
   - Verify database connectivity works
   - Check that application functions normally

3. **Verify Environment Variables**
   - Check `.env` file exists
   - Verify `.env` is in `.gitignore`
   - Confirm credentials are not in version control

### Expected Results
- ✅ No hardcoded credentials in source code
- ✅ Database connection works with environment variables
- ✅ `.env` file is properly configured and ignored by git

### Validation Commands
```bash
# Check for hardcoded credentials
grep -r "password.*=" app/config/ --include="*.php"

# Verify .env file exists
ls -la .env

# Check .gitignore
grep -q "\.env" .gitignore && echo "✅ .env in .gitignore" || echo "❌ .env not in .gitignore"
```

---

## Test Case 2: CSRF Protection

### Objective
Verify that CSRF protection is implemented on all forms.

### Pre-conditions
- CSRF token generation function is implemented
- CSRF validation function is implemented
- All forms include CSRF tokens

### Test Steps
1. **Check Form Implementation**
   - Open any form in the application
   - Verify CSRF token is present in form
   - Check token is properly generated

2. **Test CSRF Validation**
   - Submit form without CSRF token
   - Verify request is rejected
   - Submit form with valid CSRF token
   - Verify request is accepted

3. **Test Token Rotation**
   - Generate new CSRF token
   - Verify old token is invalidated
   - Test with multiple concurrent sessions

### Expected Results
- ✅ All forms include CSRF tokens
- ✅ Requests without tokens are rejected
- ✅ Requests with valid tokens are accepted
- ✅ Token rotation works properly

### Validation Commands
```bash
# Check for CSRF tokens in forms
grep -r "csrf_token" app/views/ --include="*.php"

# Check CSRF validation in controllers
grep -r "validateCSRFToken" app/controllers/ --include="*.php"
```

---

## Test Case 3: Password Reset Security

### Objective
Verify that password reset tokens are not exposed in HTML.

### Pre-conditions
- Password reset functionality is implemented
- Tokens are not displayed in HTML responses

### Test Steps
1. **Test Password Reset Request**
   - Request password reset for valid user
   - Check HTML response for token exposure
   - Verify token is not displayed in browser

2. **Test Token Security**
   - Verify token is not in browser history
   - Check that token is not accessible via direct URL
   - Test token expiration

3. **Test Rate Limiting**
   - Attempt multiple password reset requests
   - Verify rate limiting is implemented
   - Check for abuse prevention

### Expected Results
- ✅ No tokens displayed in HTML responses
- ✅ Tokens are not accessible via browser history
- ✅ Rate limiting prevents abuse
- ✅ Tokens expire properly

### Validation Commands
```bash
# Check for token exposure in reset password
grep -A 5 -B 5 "reset_link" app/controllers/reset_password.php

# Check for rate limiting implementation
grep -r "rate.*limit\|brute.*force" app/controllers/ --include="*.php"
```

---

## Test Case 4: IDOR Protection - Student Profile Access

### Objective
Verify that department-based access control is implemented for student profiles.

### Pre-conditions
- Department-based access control is implemented
- Authorization checks are in place

### Test Steps
1. **Test Authorized Access**
   - Login as admin from Department A
   - Access student profile from Department A
   - Verify access is granted

2. **Test Unauthorized Access**
   - Login as admin from Department A
   - Attempt to access student profile from Department B
   - Verify access is denied

3. **Test Super Admin Access**
   - Login as SUPER_ADMIN
   - Access student profiles from any department
   - Verify access is granted

### Expected Results
- ✅ Department admins can only access their department's students
- ✅ Unauthorized access attempts are blocked
- ✅ Super admin has access to all students
- ✅ Proper error messages are displayed

### Validation Commands
```bash
# Check for department-based access control
grep -A 10 -B 5 "department_id.*user" app/controllers/student_profile.php

# Check for authorization checks
grep -r "403 Forbidden\|Access denied" app/controllers/ --include="*.php"
```

---

## Test Case 5: Document Download Authorization

### Objective
Verify that document downloads require proper authorization.

### Pre-conditions
- Document ownership verification is implemented
- Department-based access control is in place

### Test Steps
1. **Test Authorized Download**
   - Login as admin from Department A
   - Download document from Department A student
   - Verify download is successful

2. **Test Unauthorized Download**
   - Login as admin from Department A
   - Attempt to download document from Department B student
   - Verify download is blocked

3. **Test Super Admin Download**
   - Login as SUPER_ADMIN
   - Download documents from any department
   - Verify downloads are successful

### Expected Results
- ✅ Department admins can only download their department's documents
- ✅ Unauthorized download attempts are blocked
- ✅ Super admin can download all documents
- ✅ Proper error messages are displayed

### Validation Commands
```bash
# Check for document authorization
grep -A 10 -B 5 "department_id.*doc" app/controllers/download_document.php

# Check for access control implementation
grep -r "403 Forbidden\|Access denied" app/controllers/download_document.php
```

---

## Test Case 6: Session Security

### Objective
Verify that session cookies have proper security flags.

### Pre-conditions
- Session security flags are implemented
- Proper session configuration is in place

### Test Steps
1. **Check Cookie Flags**
   - Login to the application
   - Check browser developer tools for session cookie
   - Verify httpOnly, secure, and SameSite flags

2. **Test Session Timeout**
   - Login to the application
   - Leave session idle for timeout period
   - Verify session expires properly

3. **Test Session Regeneration**
   - Login to the application
   - Verify session ID changes after login
   - Check for session fixation protection

### Expected Results
- ✅ Session cookies have httpOnly flag
- ✅ Session cookies have secure flag (in HTTPS)
- ✅ Session cookies have SameSite attribute
- ✅ Session timeout works properly
- ✅ Session regeneration occurs on login

### Validation Commands
```bash
# Check session configuration
grep -A 5 -B 5 "session_set_cookie_params" app/config/config.php

# Check for session security flags
grep -r "httpOnly\|secure\|SameSite" app/config/ --include="*.php"
```

---

## Test Case 7: Input Validation

### Objective
Verify that all user inputs are properly validated.

### Pre-conditions
- Input validation functions are implemented
- Validation is applied to all user inputs

### Test Steps
1. **Test SQL Injection Prevention**
   - Attempt SQL injection in all input fields
   - Verify queries are not affected
   - Check for proper parameter binding

2. **Test XSS Prevention**
   - Attempt XSS attacks in all input fields
   - Verify output is properly encoded
   - Check for script execution

3. **Test Input Sanitization**
   - Submit malformed data
   - Verify data is properly sanitized
   - Check for data corruption

### Expected Results
- ✅ SQL injection attempts are blocked
- ✅ XSS attacks are prevented
- ✅ Input data is properly sanitized
- ✅ No data corruption occurs

### Validation Commands
```bash
# Check for input validation
grep -r "filter_var\|htmlspecialchars\|strip_tags" app/ --include="*.php"

# Check for prepared statements
grep -r "prepare\|bind_param" app/models/ --include="*.php"
```

---

## Test Case 8: File Upload Security

### Objective
Verify that file uploads are properly secured.

### Pre-conditions
- File upload validation is implemented
- Files are stored securely
- Access controls are in place

### Test Steps
1. **Test File Type Validation**
   - Attempt to upload malicious files
   - Verify only allowed file types are accepted
   - Check for MIME type validation

2. **Test File Size Limits**
   - Attempt to upload oversized files
   - Verify size limits are enforced
   - Check for proper error handling

3. **Test File Storage Security**
   - Upload a file
   - Verify file is stored outside webroot
   - Check for proper access controls

### Expected Results
- ✅ Only allowed file types are accepted
- ✅ File size limits are enforced
- ✅ Files are stored securely
- ✅ Proper access controls are in place

### Validation Commands
```bash
# Check file upload validation
grep -A 10 -B 5 "move_uploaded_file" app/models/Document.php

# Check file type validation
grep -r "allowed_types\|allowed_exts" app/models/Document.php
```

---

## Test Case 9: NDPR Compliance

### Objective
Verify that NDPR compliance requirements are implemented.

### Pre-conditions
- Privacy policy is implemented
- Consent mechanisms are in place
- Data subject rights are available

### Test Steps
1. **Test Privacy Policy**
   - Access privacy policy page
   - Verify policy is comprehensive
   - Check for data subject rights information

2. **Test Consent Collection**
   - Submit forms requiring consent
   - Verify consent is properly collected
   - Check for consent withdrawal option

3. **Test Data Subject Rights**
   - Access data subject rights portal
   - Test data access request
   - Verify data rectification functionality

### Expected Results
- ✅ Privacy policy is accessible and comprehensive
- ✅ Consent is properly collected and tracked
- ✅ Data subject rights are available
- ✅ Data retention policies are implemented

### Validation Commands
```bash
# Check for privacy policy
ls -la privacy-policy.*

# Check for consent mechanisms
grep -r "consent\|agree\|accept" app/views/ --include="*.php"

# Check for data subject rights
grep -r "data.*subject\|right.*access\|right.*rectification" app/ --include="*.php"
```

---

## Test Case 10: Security Headers

### Objective
Verify that proper security headers are implemented.

### Pre-conditions
- Security headers are configured
- HTTPS is properly implemented

### Test Steps
1. **Check Security Headers**
   - Access the application
   - Check HTTP response headers
   - Verify security headers are present

2. **Test Content Security Policy**
   - Attempt to inject scripts
   - Verify CSP blocks malicious content
   - Check for proper CSP configuration

3. **Test HTTPS Enforcement**
   - Access application via HTTP
   - Verify redirect to HTTPS
   - Check for HSTS header

### Expected Results
- ✅ Security headers are present
- ✅ CSP blocks malicious content
- ✅ HTTPS is properly enforced
- ✅ HSTS header is configured

### Validation Commands
```bash
# Check security headers
curl -I https://your-domain.com | grep -E "(X-Frame-Options|X-Content-Type-Options|X-XSS-Protection|Content-Security-Policy|Strict-Transport-Security)"

# Check HTTPS enforcement
curl -I http://your-domain.com | grep -E "(Location|Strict-Transport-Security)"
```

---

## Automated Testing Script

### Security Test Suite
```bash
#!/bin/bash
# DFMS Security Test Suite

echo "Starting DFMS Security Tests..."

# Test 1: Check for hardcoded credentials
echo "Test 1: Checking for hardcoded credentials..."
if grep -r "password.*=" app/config/ --include="*.php"; then
    echo "❌ FAIL: Hardcoded credentials found"
else
    echo "✅ PASS: No hardcoded credentials found"
fi

# Test 2: Check for CSRF protection
echo "Test 2: Checking for CSRF protection..."
if grep -r "csrf_token\|CSRF" app/; then
    echo "✅ PASS: CSRF protection found"
else
    echo "❌ FAIL: No CSRF protection found"
fi

# Test 3: Check for SQL injection protection
echo "Test 3: Checking for SQL injection protection..."
if grep -r "prepare\|bind_param" app/models/ --include="*.php"; then
    echo "✅ PASS: Prepared statements found"
else
    echo "❌ FAIL: No prepared statements found"
fi

# Test 4: Check for XSS protection
echo "Test 4: Checking for XSS protection..."
if grep -r "htmlspecialchars\|htmlentities" app/views/ --include="*.php"; then
    echo "✅ PASS: Output encoding found"
else
    echo "❌ FAIL: No output encoding found"
fi

# Test 5: Check for session security
echo "Test 5: Checking for session security..."
if grep -r "httpOnly\|secure\|SameSite" app/config/ --include="*.php"; then
    echo "✅ PASS: Session security found"
else
    echo "❌ FAIL: No session security found"
fi

echo "Security tests completed."
```

---

## Test Results Template

### Security Test Results
```
Test Case: [Test Name]
Date: [Date]
Tester: [Name]
Environment: [Environment]

Results:
- [ ] Test 1: [Result]
- [ ] Test 2: [Result]
- [ ] Test 3: [Result]
- [ ] Test 4: [Result]
- [ ] Test 5: [Result]

Overall Result: [PASS/FAIL]
Comments: [Comments]
```

---

## Conclusion

These test cases provide comprehensive validation of the security fixes implemented in the DFMS system. Regular testing should be performed to ensure ongoing security compliance and to detect any regressions.

**Testing Schedule:**
- **After each fix:** Run relevant test cases
- **Weekly:** Run full test suite
- **Monthly:** Perform comprehensive security testing
- **Quarterly:** Conduct penetration testing
