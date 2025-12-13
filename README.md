# University of Ibadan Digital File Management System (DFMS)

A secure web portal for students, administrators, and lecturers to manage academic documents with comprehensive security features and NDPR compliance.

## Tech Stack

* **Backend:** PHP
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
* **Database:** MySQL

## Server Requirements

For the application to run correctly, the server must meet the following requirements:

* PHP 8.0 or higher
* MySQL 5.7 or higher
* GD extension (bundled with PHP) for blur detection
* A web server such as Apache or Nginx

## Image Quality & OCR Validation

These features automate rejection of blurry or unreadable uploads. Install and configure the following:

### Linux (Debian/Ubuntu 20.04/22.04+)

Run the following commands to install the required dependencies (Tesseract OCR, ImageMagick, and PHP extension):

```bash
# 1. Update package list
sudo apt-get update

# 2. Install Tesseract OCR and its language pack
sudo apt-get install tesseract-ocr tesseract-ocr-eng -y

# 3. Install ImageMagick and PHP Extension
# (Replace php8.1 with your installed version if different)
sudo apt-get install imagemagick php-imagick -y

# 4. Restart Web Server to load new PHP extension
sudo systemctl restart apache2
# OR if using Nginx+FPM:
# sudo systemctl restart php8.1-fpm
```

### Windows (XAMPP/WAMP)

1.  **Check PHP Version**: Create an `info.php` file (`<?php phpinfo(); ?>`) to see your PHP version (e.g., 8.2), Architecture (x64), and Thread Safety (TS).
2.  **Install ImageMagick**:
    *   Download from [ImageMagick Windows Downloads](https://imagemagick.org/script/download.php#windows) (select DLL version, e.g., `ImageMagick-7.1.x-Q16-HDRI-x64-dll.exe`).
    *   **Important**: Check **"Add to System Path"** during installation.
3.  **Install PHP Extension**:
    *   Download from [PECL Imagick](https://pecl.php.net/package/imagick). Choose the DLL matching your PHP version/TS.
    *   Extract `php_imagick.dll` to `C:\xampp\php\ext\`.
    *   Extract `CORE_RL_*.dll` and `IM_MOD_RL_*.dll` files to `C:\xampp\apache\bin\`.
4.  **Install Tesseract OCR**:
    *   Download installer from [UB Mannheim Tesseract Wiki](https://github.com/UB-Mannheim/tesseract/wiki).
    *   **Important**: Check **"Add to Path"** during installation so the system can find `tesseract.exe`.
5.  **Configure PHP**:
    *   Edit `php.ini` and add `extension=imagick`.
6.  **Restart Apache**: Restart the server from the XAMPP Control Panel.

---

## Getting Started

1. **Clone the repository:**

   ```bash
   git clone <repository_url>
   cd DFMS
   ```
2. **Database Setup:**

   * Import the `dfms_database.sql` file into MySQL database.
   * The system now uses environment variables for secure credential management.
3. **Install Dependencies:**

   * Follow the steps under **Image Quality & OCR Validation** if you need automated checks.
4. **Web Server Configuration:**

   * Point your web server’s document root to the `/public` directory.

## Project Structure

```
DFMS/
├── app/
│   ├── config/      # Database and app configuration
│   ├── controllers/ # Handles user requests and business logic
│   ├── models/      # Database interaction and data models
│   ├── views/       # PHP templates for the UI
│   ├── src/         # CSS, JS, and image assets
│   ├── utils/       # Security utilities and helper functions
│   └── logs/        # Security and audit logs
├── docs/            # Documentation (see docs/README.md)
├── secure_uploads/  # Secure file storage (outside webroot)
├── public/
│   └── index.php    # Front controller
├── dfms_database.sql # Complete database schema with security updates
├── .htaccess        # Security headers and access controls
├── env.example      # Environment variables template
└── README.md        # This file
```

## Login Credentials

### Admin Users
- **Main Admin**: Matric `E0500627`, Password `test101`
- **Psychology Admin**: Matric `ADMIN_PSYC`, Password `admin12345`
- **English Admin**: Matric `ADMIN_ENG`, Password `admin12345`
- **Sociology Admin**: Matric `ADMIN_SOC`, Password `admin12345`
- **Linguistics Admin**: Matric `ADMIN_LING`, Password `admin12345`
- **Super Admin**: Matric `SUPER_ADMIN`, Password `admin12345`

### Student Users
Default Password format: `surname12345` (e.g., for "John Doe" → `doe12345`)

**Note:** The system now supports automatic password generation based on student surnames. New students will have passwords in the format: `surname12345` (e.g., "John Doe" → password: "doe12345").

## Admin Features

### Student Management
- **Add Individual Students**: Navigate to "Add Student" in the admin panel to add students one by one
- **Bulk Import Students**: Use "Bulk Import" to upload a CSV file with multiple students
- **Update Passwords**: Use "Update Passwords" to change all existing student passwords to the new format

### CSV Import Format
The bulk import feature accepts CSV files with the following columns:
- `matric_number` (required)
- `full_name` (required) 
- `level` (required)
- `session` (required)
- `programme` (required)
- `department_id` (required)
- `program_type` (optional, default: Undergraduate)
- `mode` (optional, default: Regular)
- `email` (optional)

Download the CSV template from the bulk import page for the correct format.

### Quick Login Reference

**Admin Users:**
- Main Admin: `E0500627` / `test101`
- Psychology Admin: `ADMIN_PSYC` / `admin12345`
- English Admin: `ADMIN_ENG` / `admin12345`
- Sociology Admin: `ADMIN_SOC` / `admin12345`
- Linguistics Admin: `ADMIN_LING` / `admin12345`
- Super Admin: `SUPER_ADMIN` / `admin12345`

**Student Users:**
- Computer Science: `U123001`, `U123002` (password: `surname12345`)
- Psychology: `U123003`, `U123004` (password: `surname12345`)
- English: `U123005`, `U123006` (password: `surname12345`)
- Sociology: `U123007`, `U123008` (password: `surname12345`)
Linguistics: U123009, U123010

## 📚 Documentation

All documentation is available in the [`docs/`](docs/) directory:

- **[System Documentation](docs/DOCUMENTATION.md)** - Complete setup and usage guide
- **[Test Cases](docs/Security_Test_Cases.md)** - Security testing procedures

## 🚀 Quick Security Setup

1. **Environment Setup:**
   ```bash
   cp env.example .env
   # Edit .env with your database credentials
   ```

2. **Database Setup:**
   ```bash
   mysql -u your_user -p your_database < dfms_database.sql
   ```

3. **Security Validation:**
   ```bash
   # Run the deployment script (optional - fixes already applied)
   ./deploy_security_fixes.sh
   ```

## 🔐 Security Features

- **CSRF Protection** - All forms protected against cross-site request forgery
- **Input Validation** - Comprehensive validation and sanitization
- **Rate Limiting** - Protection against brute force attacks
- **Secure File Uploads** - Files stored outside webroot with validation
- **Audit Logging** - Complete security event tracking
- **NDPR Compliance** - Full data protection regulation compliance
- **Session Security** - Secure session management with proper flags

