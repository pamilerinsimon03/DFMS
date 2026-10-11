# University of Ibadan Digital File Management System (DFMS)

[![Build Status](https://img.shields.io/github/actions/workflow/status/pamilerinsimon03/DFMS/security-scan.yml?branch=main)](https://github.com/pamilerinsimon03/DFMS/actions)
[![NDPR Compliance](https://img.shields.io/badge/Compliance-NDPR-green.svg)](docs/DOCUMENTATION.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A high-security web portal for students, administrators, and academic staff at the University of Ibadan to manage, verify, and archive academic documents with automated quality checks, antivirus scanning, role-based access control, and full NDPR compliance.

---

## 📐 System Architecture

DFMS is built on a clean **Model-View-Controller (MVC)** pattern with a dedicated **Service Layer**, **Security Middleware**, and **Secure Storage Engine**.

```
                           +------------------------+
                           |     Client Browser     |
                           +-----------+------------+
                                       |
                                       v
                           +------------------------+
                           |    public/index.php    |
                           |   (Front Controller)   |
                           +-----------+------------+
                                       |
                   +-------------------+-------------------+
                   |                                       |
                   v                                       v
       +-----------------------+               +-----------------------+
       | AccessControl         |               | SecurityUtils         |
       | Middleware (RBAC)     |               | (CSRF / Rate Limit)   |
       +-----------+-----------+               +-----------+-----------+
                   |                                       |
                   +-------------------+-------------------+
                                       |
                                       v
                           +------------------------+
                           |   app/controllers/     |
                           +-----------+------------+
                                       |
          +----------------------------+----------------------------+
          |                            |                            |
          v                            v                            v
+-------------------+        +-------------------+        +-------------------+
|    app/models/    |        |   app/services/   |        |    app/views/     |
| User / Document   |        | Quality / Scan    |        |  UI & Views (SPA) |
+---------+---------+        +---------+---------+        +-------------------+
          |                            |
          v                            v
+-------------------+        +-------------------+
|  MySQL Database   |        | secure_uploads/   |
| (Relational Store)|        | (Outside Webroot) |
+-------------------+        +-------------------+
```

### Key Architectural Layers

1. **Front Controller & Routing (`public/index.php`)**: Single entry point handling security initialization, global CSRF & XSS filtering, routing, and response rendering.
2. **Controller Layer (`app/controllers/`)**: Manages request handling, input validation, authorization checks, and dispatches to services/models. Includes single-page application (SPA) endpoints (`spa.js`).
3. **Model Layer (`app/models/`)**: Encapsulates data persistence and business logic for `User`, `Document`, `FileRequirement`, `Notification`, and `PasswordReset`.
4. **Service Layer (`app/services/`)**:
   - `QualityCheckService`: Automated document blur detection (variance of Laplacian via Imagick/GD) and OCR text readability (Tesseract OCR).
   - `ClamAVScannerService`: Antivirus scanning integration (socket/CLI daemon) for uploaded files.
   - `MailService`: SMTP transactional email notifications for password resets and verification updates.
   - `SessionRollOverService`: Automated academic session transitions, student level progression, and requirement mapping.
   - `AcademicCalendarService`: Session and semester calculation engine.
   - `FilenameGenerator`: Generates secure, sanitized, hashed filenames.
5. **Security & Utilities Layer (`app/utils/`)**:
   - `AccessControlMiddleware`: Strict Role-Based Access Control (Super Admin, Departmental Admin, Lecturer, Student).
   - `SecurityUtils`: CSRF protection, rate limiting (IP & User), XSS sanitization, secure hashing, and security/audit logging (`app/logs/`).
   - `DataRetention`: Automated NDPR policy enforcement and data purging.
   - `StoragePathResolver`: Resolves storage paths for files isolated outside the webroot.
6. **Background Processing & Maintenance (`app/scripts/`)**:
   - `queue_worker.php`: Asynchronous background processing for emails, quality checks, and heavy scans.
   - `security_maintenance.php`: Scheduled maintenance script for log rotation, expired session cleanup, and NDPR data retention enforcement.

---

## 🛠️ Tech Stack & Dependencies

* **Backend:** PHP 8.0+ (PDO, GD, Imagick, Fileinfo extensions)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+), SPA routing
* **Database:** MySQL 5.7+ / MariaDB 10.3+
* **Document Processing & Validation:** Tesseract OCR, ImageMagick, ClamAV Antivirus
* **Static Analysis:** PHPStan (Level 5), Psalm
* **Testing:** PHPUnit (Unit & Integration), Playwright (E2E)
* **CI/CD & Security:** GitHub Actions (`security-scan.yml`, `ndpr-compliance.yml`)

---

## 📁 Project Structure

```
DFMS/
├── .github/
│   └── workflows/              # CI/CD Workflows (Security Scanning & NDPR Compliance)
├── app/
│   ├── config/                 # Database, document types & system settings
│   │   ├── config.php
│   │   ├── document_types.php
│   │   └── settings.json
│   ├── controllers/            # Request handlers (Uploads, Auth, Admin, Verifications)
│   ├── logs/                   # Security, audit, and system event logs
│   │   ├── audit.log
│   │   └── security.log
│   ├── models/                 # Data models (User, Document, FileRequirement, etc.)
│   ├── scripts/                # Background queue worker, security maintenance & CLI tools
│   │   ├── queue_worker.php
│   │   ├── security_maintenance.php
│   │   ├── add_department.php
│   │   ├── add_faculty.php
│   │   └── run_migrations.php
│   ├── services/               # Core business services (QualityCheck, ClamAV, Mail, Session)
│   ├── src/                    # CSS, JS (SPA logic), and visual assets
│   ├── utils/                  # Security utilities, RBAC middleware, NDPR data retention
│   └── views/                  # UI templates & partial views
├── docs/                       # Complete documentation & security test cases
├── public/                     # Web root
│   ├── index.php               # Front controller
│   └── activate.php            # Account activation portal
├── secure_uploads/             # Secure document storage (isolated outside webroot)
├── tests/                      # Automated test suite
│   ├── Unit/                   # Model & Service Unit tests
│   ├── Integration/            # Controller & Security Integration tests
│   ├── E2E/                    # Playwright End-to-End tests
│   └── bootstrap.php           # Test environment bootstrap script
├── .env.example                # Environment configuration template
├── .htaccess                   # Security headers, access rules, URL rewrites
├── deploy_security_fixes.sh    # Security validation & deployment script
├── dfms_database.sql           # Database schema & initial seed data
├── phpstan.neon                # PHPStan static analysis configuration
├── psalm.xml                   # Psalm static analysis configuration
└── README.md                   # System Overview (This file)
```

---

## 🔐 Core Security & NDPR Compliance Features

- **Isolated File Storage:** All uploaded documents are stored in `secure_uploads/` outside the web root and streamed through `secure_file_viewer.php` after authentication and permission validation (protects against IDOR and direct code execution).
- **Automated Quality & Malware Inspection:** Every upload undergoes blur detection (variance of Laplacian), OCR readability checks, and ClamAV antivirus scanning before acceptance.
- **Role-Based Access Control (RBAC):** Hierarchical permissions governing Super Admin, Departmental Admin, Lecturer, and Student roles.
- **CSRF & XSS Protection:** Anti-CSRF token verification on all POST/PUT requests and multi-layer input sanitization.
- **Rate Limiting:** Granular throttling on authentication and API endpoints to prevent brute-force attacks.
- **Audit & Security Logging:** Comprehensive security event tracking (`app/logs/security.log`) and audit trailing (`app/logs/audit.log`) with an interactive Super Admin viewer.
- **NDPR Compliance Portal:** Self-service portal for data subjects (students) to submit Subject Access Requests (SAR), request account deletion (Right to be Forgotten), and manage consent preferences (`data_subject_rights.php`). Automated log and document retention purging (`DataRetention.php`).
- **Security Headers:** Strict HTTP headers configured via `.htaccess` (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS).

---

## ⚡ Getting Started & Installation

### Server Prerequisites

Ensure your host environment has the following installed:
- PHP >= 8.0 with `pdo_mysql`, `gd`, `imagick`, `fileinfo`
- MySQL >= 5.7 or MariaDB >= 10.3
- Apache (with `mod_rewrite` & `mod_headers`) or Nginx
- Tesseract OCR & ImageMagick (for automated OCR & quality checks)
- ClamAV (optional for malware scanning)

---

### Step-by-Step Setup

#### 1. Clone Repository & Setup Environment
```bash
git clone https://github.com/pamilerinsimon03/DFMS.git
cd DFMS

# Create environment configuration file
cp env.example .env
```
Edit `.env` to configure your database credentials and environment settings:
```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=dfms_database
DB_USER=your_db_user
DB_PASS=your_db_password
APP_DEBUG=false
```

#### 2. Database Initialization
Import the database schema and seed data into MySQL:
```bash
mysql -u your_db_user -p dfms_database < dfms_database.sql
```

#### 3. Install OCR & Image Processing Dependencies

##### Linux (Ubuntu/Debian)
```bash
# Install Tesseract OCR and ImageMagick
sudo apt-get update
sudo apt-get install tesseract-ocr tesseract-ocr-eng imagemagick php-imagick clamav clamav-daemon -y

# Restart Web Server
sudo systemctl restart apache2 # or php-fpm
```

##### Windows (XAMPP / WAMP)
1. Download & install **ImageMagick** (DLL version) and ensure **"Add to System Path"** is selected.
2. Enable `extension=imagick` in `php.ini`.
3. Download & install **Tesseract OCR** installer and add the install directory to your system `PATH`.

#### 4. Configure Web Server & Permissions
Point your web server's Document Root to the `/public` directory of the project.

Run the deployment script to verify security rules and set directory permissions:
```bash
chmod +x deploy_security_fixes.sh
./deploy_security_fixes.sh
```

---

## 🔑 Initial Credential Seeding & Activation

### Administrative Account Credentials
To uphold the zero hardcoded default credentials requirement, initial administrative passwords are not committed in plain text. Generate secure initial credentials dynamically:
```bash
php app/scripts/seed_demo.php
```

| Role | Matric / Username | Credential Generation | Scope |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `SUPER_ADMIN` | Cryptographic random via `seed_demo.php` | Global System Access & Audit Logs |
| **Faculty Admin** | `E0500627` | Cryptographic random via `seed_demo.php` | Faculty of Science / Main Admin |
| **Psychology Admin** | `ADMIN_PSYC` | Cryptographic random via `seed_demo.php` | Department of Psychology |
| **English Admin** | `ADMIN_ENG` | Cryptographic random via `seed_demo.php` | Department of English |
| **Sociology Admin** | `ADMIN_SOC` | Cryptographic random via `seed_demo.php` | Department of Sociology |
| **Linguistics Admin** | `ADMIN_LING` | Cryptographic random via `seed_demo.php` | Department of Linguistics |

### Student Accounts & Onboarding Flow
Surname-derived passwords (such as `surname12345`) are eliminated. Student accounts are created in an unactivated state (`password = '*ACTIVATION_REQUIRED*'`) and onboarded via single-use email activation tokens (valid for 48 hours). Students navigate to `/app/controllers/activate.php?token=...` to set their password and record statutory NDPA 2023 consents.

| Matric Number | Department | Sample Level | Authentication Setup |
| :--- | :--- | :--- | :--- |
| `U123001` - `U123002` | Computer Science | 100L / 200L | Email Activation Token |
| `U123003` - `U123004` | Psychology | 100L / 200L | Email Activation Token |
| `U123005` - `U123006` | English | 100L / 200L | Email Activation Token |
| `U123007` - `U123008` | Sociology | 100L / 200L | Email Activation Token |
| `U123009` - `U123010` | Linguistics | 100L / 200L | Email Activation Token |

---

## 🔄 Background Workers & Scheduled Tasks

### 1. Security Maintenance Cron Job
Schedule the security maintenance script to run daily for log rotation, expired session cleanup, and NDPR data retention enforcement:
```bash
0 2 * * * cd /path/to/DFMS && php app/scripts/security_maintenance.php >> app/logs/maintenance.log 2>&1
```

### 2. Asynchronous Queue Worker
Run the background queue worker to handle asynchronous emails and quality check processing:
```bash
php app/scripts/queue_worker.php
```

---

## 🧪 Testing & Code Quality

### Static Analysis
Run static analysis tools to verify code type safety and code quality:
```bash
# Run PHPStan
./vendor/bin/phpstan analyse -c phpstan.neon

# Run Psalm
./vendor/bin/psalm
```

### Automated Unit & Integration Tests (PHPUnit)
The PHPUnit test suite includes tests for user authentication, security utilities, services, and document workflows:
```bash
# Execute unit & integration test suite
./vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
```

### End-to-End Tests (Playwright)
Run the Playwright E2E suite to test full browser flows (Login, Student Uploads, Admin Verification):
```bash
# Setup E2E data fixture
php tests/setup_e2e_data.php

# Run Playwright specs
npx playwright test
```

---

## 📚 Documentation & Guides

Additional detailed documentation is located in the [`docs/`](docs/) directory:

- **[System & Hosting Guide](docs/DOCUMENTATION.md)** - Comprehensive server, hosting, migration, and API documentation.
- **[Security Test Cases](docs/Security_Test_Cases.md)** - Comprehensive security verification manual and penetration test procedures.

---

## 📄 License

This project is open-sourced under the [MIT License](LICENSE).


