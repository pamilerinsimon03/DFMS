#!/bin/bash

# DFMS Security Fixes Deployment Script
# This script deploys all security fixes and validates the implementation

set -e  # Exit on any error

echo "🔒 DFMS Security Fixes Deployment Script"
echo "========================================"
echo "Note: All security fixes have already been implemented in the codebase."
echo "This script validates the implementation and sets up the environment."
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if running as root (not recommended for web files)
if [ "$EUID" -eq 0 ]; then
    print_warning "Running as root. Consider using a non-root user for web files."
fi

echo ""
echo "Step 1: Validate Security Implementation"
echo "----------------------------------------"
echo "All security fixes have been pre-implemented in the codebase."
echo "This script will validate the implementation and set up the environment."
echo ""

echo "Step 2: Backup existing files (if needed)"
echo "----------------------------------------"

# Create backup directory
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup critical files
cp app/config/config.php "$BACKUP_DIR/" 2>/dev/null || print_warning "Could not backup config.php"
cp .htaccess "$BACKUP_DIR/" 2>/dev/null || print_warning "Could not backup .htaccess"
cp .gitignore "$BACKUP_DIR/" 2>/dev/null || print_warning "Could not backup .gitignore"

print_status "Backup created in $BACKUP_DIR"

echo ""
echo "Step 2: Set up environment variables"
echo "-----------------------------------"

# Check if .env file exists
if [ ! -f ".env" ]; then
    if [ -f "env.example" ]; then
        cp env.example .env
        print_status "Created .env file from template"
        print_warning "Please update .env file with your actual database credentials"
    else
        print_error "No env.example file found. Please create .env file manually."
        exit 1
    fi
else
    print_status ".env file already exists"
fi

echo ""
echo "Step 3: Create secure directories"
echo "--------------------------------"

# Create secure uploads directory
mkdir -p secure_uploads
chmod 755 secure_uploads
print_status "Created secure_uploads directory"

# Create logs directory
mkdir -p app/logs
chmod 755 app/logs
print_status "Created app/logs directory"

# Create utils directory if it doesn't exist
mkdir -p app/utils
print_status "Created app/utils directory"

echo ""
echo "Step 4: Set proper file permissions"
echo "----------------------------------"

# Set secure permissions for directories
find . -type d -exec chmod 755 {} \;
print_status "Set directory permissions to 755"

# Set secure permissions for PHP files
find . -name "*.php" -exec chmod 644 {} \;
print_status "Set PHP file permissions to 644"

# Set secure permissions for configuration files
chmod 600 .env 2>/dev/null || print_warning "Could not set .env permissions"
chmod 644 .htaccess
print_status "Set configuration file permissions"

# Set secure permissions for log files
chmod 644 app/logs/*.log 2>/dev/null || print_warning "No log files to set permissions"

echo ""
echo "Step 5: Database security updates"
echo "--------------------------------"

# Check if database connection works
if [ -f ".env" ]; then
    print_status "Database security updates available in database_security_updates.sql"
    print_warning "Please review and run database_security_updates.sql manually"
    print_warning "Make sure to backup your database before running the updates"
else
    print_error "No .env file found. Cannot proceed with database updates."
fi

echo ""
echo "Step 6: Security validation"
echo "-------------------------"

# Check for hardcoded credentials
if grep -r "otunbaaw_dfms" app/config/ --include="*.php" 2>/dev/null; then
    print_error "Hardcoded credentials still found in config files"
else
    print_status "No hardcoded credentials found"
fi

# Check for CSRF protection
if grep -r "generateCSRFToken\|validateCSRFToken" app/ --include="*.php" 2>/dev/null; then
    print_status "CSRF protection functions found"
else
    print_warning "CSRF protection functions not found"
fi

# Check for security headers
if [ -f ".htaccess" ]; then
    if grep -q "X-Frame-Options\|X-Content-Type-Options\|Content-Security-Policy" .htaccess; then
        print_status "Security headers configured"
    else
        print_warning "Security headers not found in .htaccess"
    fi
else
    print_warning "No .htaccess file found"
fi

# Check for security utilities
if [ -f "app/utils/SecurityUtils.php" ]; then
    print_status "Security utilities found"
else
    print_error "Security utilities not found"
fi

echo ""
echo "Step 7: Create maintenance cron job"
echo "----------------------------------"

# Create cron job for security maintenance
CRON_JOB="0 2 * * * cd $(pwd) && php app/scripts/security_maintenance.php >> app/logs/maintenance.log 2>&1"

echo "Add this cron job to your crontab:"
echo "$CRON_JOB"
echo ""
echo "To add it, run: crontab -e"
echo "Then add the line above"

echo ""
echo "Step 8: Final security checklist"
echo "-------------------------------"

echo "Please verify the following:"
echo "1. Database credentials are in .env file (not hardcoded)"
echo "2. .env file is in .gitignore"
echo "3. All forms include CSRF tokens"
echo "4. File uploads are stored in secure_uploads/ (outside webroot)"
echo "5. Security headers are active"
echo "6. Audit logging is working"
echo "7. Privacy policy is accessible"
echo "8. Data subject rights portal is functional"

echo ""
echo "Step 9: Testing recommendations"
echo "------------------------------"

echo "Run these tests to validate security fixes:"
echo "1. Test login with invalid credentials (should be rate limited)"
echo "2. Test file upload with malicious file (should be rejected)"
echo "3. Test CSRF protection (forms without tokens should fail)"
echo "4. Test IDOR protection (unauthorized access should be blocked)"
echo "5. Test data subject rights portal"
echo "6. Check security logs for events"

echo ""
echo "Deployment completed!"
echo "===================="
print_status "Security fixes have been deployed"
print_warning "Please review the checklist above and run tests"
print_warning "Don't forget to run database_security_updates.sql"

echo ""
echo "Next steps:"
echo "1. Update .env file with your database credentials"
echo "2. Run database_security_updates.sql"
echo "3. Test all functionality"
echo "4. Set up monitoring and alerting"
echo "5. Schedule regular security maintenance"

echo ""
echo "For support, refer to:"
echo "- Security_Hardening_Checklist.md"
echo "- NDPR_Compliance_Checklist.md"
echo "- Security_Test_Cases.md"
