# University of Ibadan Digital File Management System (DFMS)

[![Build Status](https://img.shields.io/github/actions/workflow/status/pamilerinsimon03/DFMS/ci.yml?branch=main)](https://github.com/pamilerinsimon03/DFMS/actions)
[![Coverage Status](https://img.shields.io/coveralls/github/pamilerinsimon03/DFMS?branch=main)](https://coveralls.io/github/pamilerinsimon03/DFMS?branch=main)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## Table of Contents

- [Introduction](#introduction)
- [Prerequisites & Installation](#prerequisites--installation)
- [Database Setup & Hosting](#database-setup--hosting)
  - [Schema Design](#schema-design)
  - [Local Initialization](#local-initialization)
  - [Configuration](#configuration)
  - [Remote Hosting](#remote-hosting)
  - [Connection Verification](#connection-verification)
- [Quick Start / Usage](#quick-start--usage)
  - [Configuration](#application-configuration)
  - [Features & Examples](#features--examples)
  - [API Reference](#api-reference)
- [Architecture & Design](#architecture--design)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [Roadmap](#roadmap)
- [License](#license)
- [Contact & Support](#contact--support)

## Introduction

DFMS is a web portal that allows students, lecturers, and administrators at the University of Ibadan to securely upload, verify, and manage academic documents. Core features include:

- Role-based access control (Admin, Lecturer, Student)
- Automated blur detection and OCR validation of uploads
- Document approval workflow
- Searchable archive of historical files

Intended for academic institutions seeking a lightweight document management solution.

## Prerequisites & Installation

### OS & Runtime Requirements

| Component                        | Version          |
| -------------------------------- | ---------------- |
| PHP                              | ≥ 8.0            |
| MySQL                            | ≥ 5.7            |
| Web Server                       | Apache/Nginx     |
| GD, Imagick, Fileinfo extensions | Bundled with PHP |
| Tesseract OCR                    | Latest stable    |
| Ghostscript                      | Latest stable    |

### Install Dependencies

```bash
# Clone repository
git clone https://github.com/pamilerinsimon03/DFMS.git
cd DFMS

# Import database schema
mysql -u <user> -p < dfms.sql

# Enable PHP extensions (Windows)
# edit php.ini:
# extension=gd
# extension=imagick
# extension=fileinfo

# Install ImageMagick & Ghostscript, ensure binaries in PATH

# Restart web server
e.g. sudo systemctl restart apache2
```

### Verify Setup

1. Navigate to http://localhost/DFMS/public
2. Login with demo credentials (see below)
3. Ensure dashboard loads without error.

## Database Setup & Hosting

All `.sql` scripts are in the project root (`dfms.sql`).

### Schema Design

| Table               | Columns (Type)                            | Keys & Relations |
| ------------------- | ----------------------------------------- | ---------------- |
| **departments**     | id (INT PK), name (VARCHAR)               |                  |
| **users**           | id (INT PK), matric_number (VARCHAR), ... | `is_admin` flag  |
| **documents**       | id (INT PK), user_id (INT FK), ...        | FK → `users.id`  |
| **password_resets** | id (INT PK), user_id (INT FK), token, ... | FK → `users.id`  |

**ER Diagram** (ASCII)

```
   +-------------+      +-------------+
   |   users     |1    *|  documents  |
   +-------------+      +-------------+
   | id (PK)     |      | id (PK)     |
   | matric_no   |      | user_id (FK)|
   +-------------+      +-------------+
         |
         |1
         |*
   +------------------+
   | password_resets  |
   +------------------+
```

### Local Initialization

```bash
# Create DB and import schema+seed
mysql -u root -p -e "CREATE DATABASE dfms_local;"
mysql -u root -p dfms_local < dfms_database.sql
```

### Configuration

Environment variables (or edit `app/config/config.php`):
| Variable | Default | Description |
|---------------|----------------|----------------------------|
| DB_HOST | localhost | Database host |
| DB_PORT | 3306 | Database port |
| DB_NAME | dfms_local | Database name |
| DB_USER | root | DB user |
| DB_PASS | (empty) | DB password |
| APP_DEBUG | false | Enable debug mode |

### Remote Hosting

1. **Choose provider**: AWS RDS, DigitalOcean Managed DB, etc.
2. **Create instance**: set engine MySQL ≥5.7, allocate storage.
3. **Security**: open port 3306, restrict by IP or VPC.
4. **Users & Permissions**: create DB user with limited privileges.
5. **Import schema**:
   ```bash
   mysql -h <host> -P 3306 -u <user> -p DB_NAME < dfms.sql
   ```
6. **Enable SSL/TLS**: configure certificates, enforce encrypted connections.

### Connection Verification

```bash
mysql -h <host> -P 3306 -u <user> -p -e "SHOW TABLES;"
```

Or in PHP (PDO):

```php
$dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_NAME']}";
$pdo = new PDO($dsn, DB_USER, DB_PASS);
```

## Database Transfer & Migration

This section covers how to transfer your DFMS database and its contents from one system to another (laptop to laptop, or to cloud hosting).

### Prerequisites

- Source system: MySQL/MariaDB with DFMS database
- Target system: MySQL/MariaDB server (local or cloud)
- Access to command line on both systems
- Network access between systems (for remote transfers)

### Method 1: Using Database Dump (Recommended)

#### Step 1: Export from Source System

On your source laptop, create a complete database dump:

```bash
# Basic dump (structure + data)
mysqldump -u root -p dfms_local > dfms_database_latest.sql

# With additional options for better compatibility
mysqldump -u root -p --single-transaction --routines --triggers dfms_local > dfms_database_latest.sql

# Compressed dump (for large databases)
mysqldump -u root -p dfms_local | gzip > dfms_database_latest.sql.gz
```

#### Step 2: Transfer Files

Transfer these files to your target system:
- `dfms_database_latest.sql` (or `.sql.gz`)
- `public/uploads/` folder (contains all uploaded documents)

#### Step 3: Import to Target System

```bash
# Create database on target system
mysql -u root -p -e "CREATE DATABASE dfms_local;"

# Import the dump
mysql -u root -p dfms_local < dfms_database_latest.sql

# For compressed dumps
gunzip < dfms_database_latest.sql.gz | mysql -u root -p dfms_local
```

### Method 2: Using phpMyAdmin

#### Export from Source:
1. Open phpMyAdmin
2. Select `dfms_local` database
3. Click "Export" → "SQL" → "Go"
4. Download the `.sql` file

#### Import to Target:
1. Create database on target system
2. Use phpMyAdmin's import function
3. Select the downloaded `.sql` file
4. Click "Go" to import

### Method 3: Direct Transfer (Cloud Hosting)

For cloud databases (AWS RDS, DigitalOcean, etc.):

```bash
# Get connection details from your cloud provider
# Example for AWS RDS:
mysql -h your-rds-endpoint.region.rds.amazonaws.com \
      -P 3306 \
      -u your_username \
      -p your_database < dfms_database_latest.sql
```

### Method 4: Automated Transfer Script

Create a transfer script for repeated migrations:

```php
<?php
// transfer_database.php
$source_db = 'dfms_local';
$target_host = 'your_target_host';
$target_user = 'your_target_user';
$target_pass = 'your_target_password';
$target_db = 'dfms_local';

// Create timestamped dump
$timestamp = date('Y-m-d_H-i-s');
$dump_file = "dfms_transfer_{$timestamp}.sql";

// Export database
$command = "mysqldump -u root -p {$source_db} > {$dump_file}";
system($command);

echo "Database exported to {$dump_file}\n";
echo "Transfer this file to your target system and import it.\n";
echo "Target connection: {$target_host}:3306/{$target_db}\n";
?>
```

### File Transfer Requirements

**Essential Files to Transfer:**
- Database dump (`.sql` file)
- `public/uploads/` folder (all uploaded documents)
- `app/config/config.php` (update with new database credentials)

**Optional Files:**
- `app/logs/` (if you want to preserve logs)
- `migration.sql` (for reference)

### Configuration Updates

After importing the database, update your target system's configuration:

```php
// app/config/config.php
if (php_sapi_name() === 'cli' || (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] === 'localhost')) {
    // Local machine
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'dfms_local';
} else {
    // Cloud/Remote server
    $db_host = 'your_cloud_host.com';
    $db_user = 'your_cloud_user';
    $db_pass = 'your_cloud_password';
    $db_name = 'dfms_cloud';
}
```

### Verification Checklist

After transfer, verify:

- [ ] Database connection successful
- [ ] All tables imported correctly
- [ ] Uploaded files accessible
- [ ] User authentication working
- [ ] Document upload/verification working
- [ ] Admin panel accessible

### Troubleshooting Common Issues

**Connection Errors:**
```bash
# Check if MySQL is running
sudo systemctl status mysql

# Verify database exists
mysql -u root -p -e "SHOW DATABASES;"

# Check user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'username'@'host';"
```

**Import Errors:**
```bash
# Check MySQL error log
sudo tail -f /var/log/mysql/error.log

# Verify dump file integrity
head -n 20 dfms_database_latest.sql
```

**File Permission Issues:**
```bash
# Set proper permissions for uploads folder
chmod -R 755 public/uploads/
chown -R www-data:www-data public/uploads/
```

### Performance Considerations

- **Large Databases**: Use compressed dumps and consider splitting large tables
- **Network Transfer**: Use `rsync` or `scp` for large file transfers
- **Cloud Uploads**: Consider using cloud storage services (AWS S3, etc.) for document storage
- **Incremental Updates**: For frequent transfers, consider using MySQL replication or incremental dumps

## Quick Start / Usage

### Application Configuration

Place a `.env` file at project root:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_NAME=dfms_local
DB_USER=root
DB_PASS=
APP_DEBUG=true
```

### Features & Examples

- **Upload Documents**:

  ```js
  fetch("/upload", {
    method: "POST",
    body: formData,
  }).then((res) => res.json());
  ```

  ![Upload Workflow](docs/images/upload.png)

- **Verify Documents** (Admin panel):
  See `app/controllers/AdminController.php`.

### API Reference

| Method | Endpoint      | Params                     | Response                   |
| ------ | ------------- | -------------------------- | -------------------------- |
| GET    | `/documents`  | `?status=Pending Approval` | JSON list of documents     |
| POST   | `/documents`  | `file`                     | `{ success: true, id: 5 }` |
| POST   | `/auth/login` | `user`, `pass`             | `{ token: '...' }`         |

## Architecture & Design

**Component Diagram**:

```
[Browser] --> [public/index.php] --> [Router]
  --> [Controllers] --> [Models] --> [MySQL]
```

Key modules: `app/controllers`, `app/models`, `app/views`, `app/config`.

## Testing

No built-in tests; recommend PHPUnit.

```bash
composer require --dev phpunit/phpunit
./vendor/bin/phpunit --config phpunit.xml
```

Coverage report via Xdebug + PHPUnit.

## Deployment

**CI/CD (GitHub Actions)**:

```yaml
name: CI
on: [push]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with: php-version: 8.0
      - run: composer install
      - run: ./vendor/bin/phpunit
```

**Production**: sync `public/`, run migrations, clear cache.

## Contributing

- Follow PSR-12 coding style.
- Branch naming: `feature/...`, `bugfix/...`.
- Open issues and submit PRs against `develop` branch.

## Roadmap

- Role-based access enhancements
- Email notifications on upload
- Document versioning
- Multi-department support
