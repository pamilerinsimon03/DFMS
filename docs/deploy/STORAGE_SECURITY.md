# DFMS Storage Isolation, Hardening & Principle of Least Privilege Guide

**University of Ibadan Digital File Management System (DFMS)**  
**Security Architecture Specification (Workstreams W1, W3, W11)**

---

## 1. Storage Isolation Outside the Web Root

In accordance with the security design stated in Chapters One and Two of the thesis, all document uploads and system log repositories must be isolated completely outside the web server DocumentRoot.

### 1.1 Directory Structure Layout

```
/var/www/
├── dfms/                               # Web DocumentRoot (Application Code)
│   ├── app/
│   ├── public/
│   │   └── index.php                   # Front Controller
│   └── ...
└── dfms_private/                       # ISOLATED STORAGE (Never accessible via HTTP)
    ├── secure_uploads/                 # STORAGE_ROOT (0750)
    │   ├── _staging/                   # Staging buffer (0700)
    │   └── 2024_2025/                  # Session / Dept / Level sharded store (0750)
    │       └── CSC/
    │           └── 100/
    │               └── a3/             # 2-Hex shard directory
    │                   └── a3f8...pdf  # Salted HMAC-SHA256 file (0640)
    └── logs/                           # LOG_ROOT (0750)
        ├── audit_chain.log
        └── security_events.log
```

### 1.2 Fail-Safe Boot Guard

At initialization (`app/config/config.php`), DFMS computes the canonical realpaths of `DOCUMENT_ROOT` and `STORAGE_ROOT`. If `STORAGE_ROOT` is detected to resolve inside or equal to `DOCUMENT_ROOT`, the application refuses to process requests:
- In `production`: Returns HTTP 500 and halts immediately.
- In `development`: Emits a loud audit alert and warning banner.

---

## 2. POSIX Mount Options & Filesystem Hardening

When deploying to dedicated physical volumes or virtual block devices (e.g. AWS EBS, GCP Persistent Disk, or local LVM volumes), mount the storage partition with defensive filesystem flags.

### 2.1 Recommended `/etc/fstab` Configuration

```fstab
# Dedicated mount for DFMS isolated private storage
UUID=e74a81b9-3b62-4217-91a3-83f124c65e89  /var/www/dfms_private  ext4  defaults,nosuid,nodev,noexec,usrquota  0  2
```

### 2.2 Security Justification for Mount Flags

| Mount Flag | Defensive Value |
| :--- | :--- |
| `noexec` | Blocks binary execution directly from storage. Even if an attacker stages an executable or script, the kernel rejects execution with `EACCES` (Permission denied). |
| `nosuid` | Prevents SetUID / SetGID escalation binaries from executing with elevated privileges. |
| `nodev` | Prevents creation or interpretation of character/block special device nodes inside upload storage. |

### 2.3 Directory and File Permissions

- **Parent Storage Directories:** Mode `0750` (`drwxr-x---`) owned by `www-data:www-data`.
- **Staging Buffer (`_staging/`):** Mode `0700` (`drwx------`) owned by `www-data:www-data`.
- **Stored Files:** Mode `0640` (`-rw-r-----`) owned by `www-data:www-data`.
- **Enforcement:** Enforced at file write time via `StoragePathResolver::secureFilePermissions()`.

```bash
sudo chown -R www-data:www-data /var/www/dfms_private
sudo find /var/www/dfms_private -type d -exec chmod 0750 {} +
sudo find /var/www/dfms_private/secure_uploads/_staging -type d -exec chmod 0700 {} +
sudo find /var/www/dfms_private -type f -exec chmod 0640 {} +
```

---

## 3. ClamAV Daemon Socket Configuration

DFMS scans all uploaded documents asynchronously using the `nINSTREAM` protocol over a UNIX domain socket. This eliminates disk temp files and TOCTOU races.

### 3.1 Install and Configure ClamAV

```bash
sudo apt-get update
sudo apt-get install -y clamav clamav-daemon
```

### 3.2 Configure `/etc/clamav/clamd.conf`

Ensure the local socket is configured:
```ini
LocalSocket /var/run/clamav/clamd.ctl
FixStaleSocket true
LocalSocketGroup clamav
LocalSocketMode 660
MaxStreamLength 25M
MaxFileSize 25M
StreamMaxLength 25M
```

### 3.3 Web Server Group Membership

Allow the web server user (`www-data`) to communicate over the ClamAV domain socket:
```bash
sudo usermod -aG clamav www-data
sudo systemctl restart clamav-daemon
```

Verify socket permissions:
```bash
ls -la /var/run/clamav/clamd.ctl
# Expected: srw-rw---- 1 clamav clamav 0 /var/run/clamav/clamd.ctl
```

---

## 4. Database Principle of Least Privilege (PoLP)

Never execute the runtime application using the database `root` or an administrative user with `SUPER` or `GRANT` privileges. Segregate roles into:
1. **Runtime Application User (`dfms_app`):** Granted only Data Manipulation Language (DML) rights.
2. **Schema Migration User (`dfms_migrator`):** Used strictly for executing `app/scripts/run_migrations.php`.

### 4.1 SQL User Provisioning Script

```sql
-- 1. Create dedicated database
CREATE DATABASE IF NOT EXISTS `dfms_production` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Runtime Application User (DML Only)
CREATE USER IF NOT EXISTS 'dfms_app'@'127.0.0.1' IDENTIFIED BY 'Strong_Random_App_Secret_#2026';
GRANT SELECT, INSERT, UPDATE, DELETE ON `dfms_production`.* TO 'dfms_app'@'127.0.0.1';
-- Specifically DENY destructive DDL commands
REVOKE DROP, ALTER, CREATE, GRANT OPTION, SUPER ON *.* FROM 'dfms_app'@'127.0.0.1';

-- 3. Migration Runner User (DDL + DML)
CREATE USER IF NOT EXISTS 'dfms_migrator'@'127.0.0.1' IDENTIFIED BY 'Strong_Random_Migrate_Secret_#2026';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, TRIGGER, REFERENCES ON `dfms_production`.* TO 'dfms_migrator'@'127.0.0.1';

-- Apply privilege tables
FLUSH PRIVILEGES;
```

### 4.2 Append-Only Audit Log Trigger Defense

As established in Workstream W6 (`2026_10_09_000003_tamper_evident_audit_log.sql`), the database contains triggers that abort any `UPDATE` or `DELETE` attempt on `audit_logs`:
```sql
SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Tamper prevention: audit_logs is append-only.';
```
Even if a SQL injection vulnerability were introduced in application code or the application user compromised, records in `audit_logs` cannot be rewritten or erased.

---

## 5. Web Server Configuration Checklist

- [x] Web root points to repository directory or `public/` front controller.
- [x] All `.env`, `*.log`, `*.sql`, `app/logs/`, `app/config/`, `docs/`, `tests/` blocked with HTTP 403.
- [x] Direct access to `secure_uploads/` returns HTTP 403 on both Apache and Nginx.
- [x] PHP execution disabled inside `secure_uploads/` and `_staging/`.
- [x] Kernel file acceleration configured (`X-Sendfile` on Apache, `X-Accel-Redirect` on Nginx).
- [x] Background daemons managed via systemd unit files with automatic restart.
