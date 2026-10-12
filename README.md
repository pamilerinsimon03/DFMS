# University of Ibadan Digital File Management System (DFMS)

[![Build Status](https://img.shields.io/github/actions/workflow/status/pamilerinsimon03/DFMS/security-scan.yml?branch=main)](https://github.com/pamilerinsimon03/DFMS/actions)
[![NDPA 2023 Compliance](https://img.shields.io/badge/Compliance-NDPA%202023%20%2F%20GAID%202025-green.svg)](docs/GAP_REPORT.md)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A high-security, web-based digital file archiving and verification portal designed for the University of Ibadan to manage, verify, and archive academic documents with automated quality checks, antivirus scanning, role-based access control, cryptographic audit logging, and statutory Nigerian Data Protection Act (NDPA 2023) compliance.

---

## 📐 System Architecture

DFMS is engineered on a custom **Model-View-Controller (MVC)** architecture with `mysqli` parameterized prepared statements, a responsive single-page application (SPA) front-controller, a dedicated **Security Middleware & Scope Pipeline**, and an **Isolated Content-Addressed Storage Engine**.

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
       | AccessControl         |               | SecurityUtils & CSRF  |
       | Middleware (RBAC)     |               | (Token / Rate Limiter)|
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
| User / Document   |        | FileDelivery / QC |        |  SPA Responsive   |
| (UUID Translation)|        | (Sendfile / Clam) |        |    UI Templates   |
+---------+---------+        +---------+---------+        +-------------------+
          |                            |
          v                            v
+-------------------+        +-------------------+
|  MariaDB Database |        | Isolated Storage  |
| (Hash Chain /     |        | (0750 / 0640      |
| Append Triggers)  |        | Outside Webroot)  |
+-------------------+        +-------------------+
```

### Key Architectural Layers

1. **Front Controller & Routing (`public/index.php`)**: Single entry point handling security bootstrap, boot-time storage isolation checks, synchronizer CSRF token validation, rate limiting, and SPA routing.
2. **Two-Stage Authorization & Scoping (`app/utils/AccessScope.php`, `AccessControlMiddleware.php`)**:
   - **Stage 1 (Route Level):** Fail-closed capability matching against centralized RBAC matrix (`super_admin` > `faculty_admin` > `department_admin` > `student`).
   - **Stage 2 (Query & Object Level):** Parameterized SQL predicate generation (`buildDocumentScopeFilter`, `buildUserScopeFilter`) applied directly to SQL queries, blocking Insecure Direct Object References (IDOR).
3. **Non-Sequential Identifiers (UUIDv4)**: Public-facing endpoints, URLs, and JSON payloads expose only RFC 4122 UUIDv4 identifiers (`public_id`), translating to internal auto-increment primary keys strictly at the model boundary.
4. **Isolated Salted HMAC Storage Engine (`app/utils/StoragePathResolver.php`)**:
   - Default path set outside the web root (`../dfms_private/secure_uploads`).
   - Content-addressed file naming via HMAC: `hash_hmac('sha256', content, STORAGE_SALT)`.
   - Sharding structure: `{session}/{department}/{level}/{first_two_hex_chars}/{hmac_hash}.{magic_ext}`.
   - Enforces directory mode `0750` and file mode `0640` with strict `realpath()` traversal blocking.
5. **Kernel-Accelerated Authenticated Delivery (`app/services/FileDeliveryService.php`)**:
   - Multi-mode streaming supporting Apache `mod_xsendfile` (`X-Sendfile`), Nginx accelerated redirect (`X-Accel-Redirect`), and PHP development fallback.
   - Emits zero file bytes from PHP in accelerated modes.
   - Enforces strict Content-Security-Policy (`default-src 'none'; sandbox; frame-ancestors 'self'`) and `X-Content-Type-Options: nosniff`.
6. **Asynchronous Upload Pipeline (`app/scripts/queue_worker.php`)**:
   - Staging in private directory `_staging/` (mode `0700`).
   - Stage 1: `ClamAVScannerService` chunked socket streaming via `nINSTREAM` (4-byte length framing, fail-closed in production).
   - Stage 2: Discrete Laplacian variance blur evaluation ($\sigma_{Lap}^2 \ge 100$).
   - Stage 3: Tesseract OCR regex field extraction (`matric_number`, `candidate_name`, `session`, `ref_number`) with student record cross-checks.
   - Permanent storage promotion upon passing all quality checks.
7. **Tamper-Evident Append-Only Audit Ledger (`app/services/AuditService.php`)**:
   - Merkle-style cryptographic chaining: $H_i = \text{SHA-256}(H_{i-1} \parallel \text{canonical JSON})$ with $H_0 = 64\text{ zeros}$.
   - Database triggers intercept and abort `UPDATE` and `DELETE` operations on `audit_logs`.
   - CLI verification tool: `php app/scripts/verify_audit_chain.php`.
8. **NDPA 2023 & GAID 2025 Statutory Compliance (`app/controllers/data_subject_rights.php`)**:
   - Consent capture and withdrawal with lawful basis refusal under s.25(1)(b) for core academic processing.
   - Machine-readable JSON export (s.34/s.38) and audited rectification diff logging (s.36).
   - Processing restriction flag and middleware gatekeeper (`processing_restricted` under s.37).
   - Automated retention enforcement worker (`enforce_retention.php`) unlinking non-core expired records while permanently preserving core academic degrees.

---

## 🛠️ Tech Stack & Dependencies

* **Backend Runtime:** PHP 8.2+ (ZTS / NTS, `mysqli`, `fileinfo`, `gd`, `imagick`, `mbstring`, `openssl`, `sodium`)
* **Database Engine:** MySQL 5.7+ / MariaDB 10.4+ (`mysqlnd`, strict mode, append-only triggers)
* **Frontend:** Vanilla JavaScript (ES6+), CSS3, SPA routing, Synchronizer CSRF tokens
* **Document Processing & Malware Defense:** Tesseract OCR (v5.x), ImageMagick (v7.x), ClamAV Antivirus Daemon
* **Static Analysis:** PHPStan (Level 5, 0 errors), Psalm (0 errors)
* **Test Suite:** PHPUnit 9.6 (85 automated tests, 540 assertions)
* **Continuous Integration:** GitHub Actions (`.github/workflows/security-scan.yml`, `ci.yml`)

---

## 📁 Repository Structure

```
DFMS/
├── .github/
│   └── workflows/              # GitHub Actions CI & Security Scanning workflows
├── app/
│   ├── config/                 # Centralized permissions, document patterns, system config
│   │   ├── config.php          # Security bootstrap & storage boundary validation
│   │   ├── permissions.php     # Centralized RBAC matrix and route capabilities
│   │   ├── document_patterns.php # OCR regex extraction pattern registry
│   │   └── settings.json       # System runtime settings & rate limiter thresholds
│   ├── controllers/            # Request handlers (Uploads, Auth, Admin, File Delivery)
│   ├── models/                 # Data access layer (User, Document, PasswordReset)
│   ├── scripts/                # Background workers & CLI utilities
│   │   ├── queue_worker.php    # Asynchronous upload pipeline worker
│   │   ├── process_email_queue.php # Asynchronous activation email dispatcher
│   │   ├── enforce_retention.php # Statutory retention automated purge worker
│   │   ├── verify_audit_chain.php # Audit ledger hash chain cryptographic verifier
│   │   ├── run_migrations.php  # Schema migration runner with schema_migrations tracking
│   │   ├── seed_demo.php       # Dynamic cryptographic credential seeding
│   │   └── migrate_storage.php # Storage reorganization & salted HMAC hashing utility
│   ├── services/               # Core services (FileDelivery, QualityCheck, ClamAV, Audit, RateLimiter)
│   ├── src/                    # CSS stylesheets, client-side SPA routing (`spa.js`)
│   ├── utils/                  # AccessScope, AccessControlMiddleware, CSRFProtection
│   └── views/                  # View templates, partials, and modals
├── docs/
│   ├── GAP_REPORT.md           # Formal thesis security design compliance matrix (All 20 Met)
│   ├── DOC_CHANGES.md          # Thesis documentation corrections and errata
│   ├── evaluation/             # Automated empirical evaluation harnesses & evidence
│   │   ├── blur_calibration.php # Discrete Laplacian variance benchmark
│   │   ├── clamav_latency.php   # nINSTREAM chunked streaming throughput benchmark
│   │   ├── ocr_accuracy.php     # Character Error Rate (CER) and entity extraction harness
│   │   ├── auth_benchmark.php   # Two-stage authorization overhead benchmark (1,000 runs)
│   │   └── RESULTS.md           # Empirical benchmark measurement output and analysis
│   └── deploy/                 # Production deployment & hardening guides
│       ├── apache.sample.conf  # Production Apache VirtualHost configuration
│       ├── nginx.sample.conf   # Production Nginx server block with X-Accel-Redirect
│       ├── htaccess.sample     # Production .htaccess hardening sample
│       ├── cron.sample         # Production cron job definitions
│       ├── STORAGE_SECURITY.md # Filesystem mount options, ClamAV socket, and DB PoLP
│       └── systemd/            # Systemd service units (queue worker, email worker, retention)
├── migrations/                 # Idempotent incremental SQL migrations
├── public/                     # Web server DocumentRoot
│   └── index.php               # Front controller
├── tests/                      # Comprehensive PHPUnit test suite (Unit & Integration)
├── dfms_database.sql           # Canonical database schema and baseline data
├── phpstan.neon                # PHPStan static analysis configuration
├── psalm.xml                   # Psalm static analysis configuration
└── README.md                   # System Architecture and Operational Runbook (This document)
```

---

## ⚡ Getting Started & Quick Setup

### 1. Environment Setup

```bash
git clone https://github.com/pamilerinsimon03/DFMS.git
cd DFMS

# Copy environment template
cp env.example .env
```

Configure `.env` with your local database credentials:
```ini
DB_HOST=127.0.0.1
DB_NAME=dfms_local
DB_USER=root
DB_PASS=your_password
APP_ENV=development
STORAGE_ROOT=../dfms_private/secure_uploads
LOG_ROOT=../dfms_private/logs
STORAGE_SALT=your_random_64_char_hex_salt
SENDFILE_MODE=php
```

### 2. Database Initialization & Migration

Initialize the canonical schema and run all incremental migrations:
```bash
# 1. Load canonical base schema
mysql -u root -p dfms_local < dfms_database.sql

# 2. Run incremental migrations via the migration runner
php app/scripts/run_migrations.php
```

### 3. Dynamic Demonstration Credential Seeding

To ensure zero hardcoded default credentials, generate cryptographically random credentials ($\ge 16$ chars) for administrative roles and single-use activation URLs for students:
```bash
php app/scripts/seed_demo.php
```

Sample output:
```text
========================================================================
DFMS Demonstration & Administrative Credential Provisioning
========================================================================
Role               | Username   | Password (Generated)        | Scope
------------------------------------------------------------------------
Super Admin        | SUPER_ADMIN| k9#P$vX2!mR8*zL4            | Global System Access
Faculty Admin      | E0500627   | j7@Q#wT9!bN3*yK5            | Faculty of Science
Department Admin   | ADMIN_CSC  | w4$K!vM8*xP2#zR9            | Computer Science
```

---

## 🧪 Testing, Quality Gates & Benchmarks

### 1. Static Analysis
Verify static type safety and zero error status across all source files:
```bash
# PHPStan static analysis (Level 5)
php C:\tools\phpstan.phar analyse --memory-limit 1G --no-progress

# Psalm static analysis
php C:\tools\psalm.phar --show-info=false
```

### 2. Automated Test Suite (PHPUnit)
Execute the comprehensive test suite covering RBAC scoping, isolated storage, file delivery headers, upload queues, ClamAV socket streaming, Laplacian variance filtering, audit hash chaining, rate limiting, and NDPA compliance:
```bash
php C:\tools\phpunit.phar
```
*Result: 85 tests, 540 assertions, 0 errors, 0 failures.*

### 3. Empirical Evaluation Harnesses
Execute the benchmarking harnesses required by Section 8 of the thesis security design:
```bash
# 1. Blur Calibration Benchmark (Laplacian Variance)
php docs/evaluation/blur_calibration.php

# 2. ClamAV Streaming Latency & Throughput Benchmark
php docs/evaluation/clamav_latency.php

# 3. OCR Character Error Rate & Regex Extraction Benchmark
php docs/evaluation/ocr_accuracy.php

# 4. Two-Stage Authorization & Scoping Latency Benchmark
php docs/evaluation/auth_benchmark.php
```

For complete empirical measurement tables and statistical analysis, refer to **[`docs/evaluation/RESULTS.md`](docs/evaluation/RESULTS.md)**.

---

## 🚀 Production Deployment & Hardening Runbook

For production deployment on Linux (Ubuntu 22.04 / Debian 12), follow the detailed guides in [`docs/deploy/`](docs/deploy/):

1. **Storage Isolation & Filesystem Hardening:** Follow [`docs/deploy/STORAGE_SECURITY.md`](docs/deploy/STORAGE_SECURITY.md) to configure `/etc/fstab` mount options (`nosuid,nodev,noexec`), POSIX permission modes (`0750` dirs, `0640` files), and ClamAV UNIX domain sockets.
2. **Database Principle of Least Privilege:** Create segregated database users for runtime DML vs migration DDL.
3. **Web Server Acceleration & Deny Rules:**
   - **Nginx:** Use [`docs/deploy/nginx.sample.conf`](docs/deploy/nginx.sample.conf) with internal `X-Accel-Redirect` blocks.
   - **Apache:** Use [`docs/deploy/apache.sample.conf`](docs/deploy/apache.sample.conf) and [`docs/deploy/htaccess.sample`](docs/deploy/htaccess.sample) with `mod_xsendfile`.
4. **Systemd Daemon Workers:** Install service units from [`docs/deploy/systemd/`](docs/deploy/systemd/):
   ```bash
   sudo cp docs/deploy/systemd/*.service /etc/systemd/system/
   sudo cp docs/deploy/systemd/*.timer /etc/systemd/system/
   sudo systemctl daemon-reload
   sudo systemctl enable --now dfms-queue-worker.service dfms-email-worker.service dfms-retention.timer
   ```
5. **Scheduled Maintenance:** Install crontab entries from [`docs/deploy/cron.sample`](docs/deploy/cron.sample).

---

## 📄 License & Intellectual Property

This software is developed for the University of Ibadan as an academic digital file management and document verification system. Released under the [MIT License](LICENSE).
