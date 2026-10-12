# Proposed Thesis & Documentation Amendments (DFMS)

This document tracks differences between the current thesis text (Chapters One and Two) and the secure, production-grade implementation of the Digital File Management System (DFMS). In accordance with the project ground rules, thesis wording is not modified in-place; instead, this document records the proposed revisions and architectural decisions for owner review and thesis defense updates.

---

## 1. D1: Student Credential Issuance & Activation Tokens
- **Current Thesis Wording:** Chapter One (Scope and Limitations) states that default student initial passwords are automatically generated based on student surnames (e.g., `surname12345`).
- **Observed Vulnerability:** Generating passwords from publicly known surnames introduces severe credential predictability, account takeover risks, and violates modern authentication hygiene standards.
- **Implemented Design:** Replaced surname password generation with cryptographically secure, time-limited activation tokens sent to verified institutional email addresses (`public/activate.php`). Students set their own strong passwords upon initial activation.
- **Proposed Thesis Amendment:**
  > *"Rather than generating predictable initial credentials from candidate surnames, initial access is governed by cryptographically random single-use activation tokens transmitted to the student's registered email address. Account activation requires the student to establish a strong individual authentication credential and record dynamic privacy consent."*

---

## 2. D2: Database Abstraction Layer (mysqli vs PDO)
- **Current Thesis Wording:** Chapter Two and Chapter Three reference "parameterised PDO queries" for database operations.
- **Observed Architecture:** The DFMS codebase is built on PHP 8.x using the `mysqli` extension with parameterized prepared statements (`$stmt->prepare()`, `$stmt->bind_param()`, `$stmt->execute()`) and `mysqlnd` result buffers.
- **Implemented Design:** Preserved the robust `mysqli` prepared-statement implementation without framework rewrites or PDO migration, ensuring consistent defense against SQL injection.
- **Proposed Thesis Amendment:**
  > *"All database interactions are executed using parameterised prepared statements via PHP's native `mysqli` extension with MySQL Native Driver (`mysqlnd`) support, strictly disallowing string concatenation in SQL query formation."*

---

## 3. D3: File Delivery Content-Disposition & Inline Preview
- **Current Thesis Wording:** Mentions strict `Content-Disposition: attachment` for all file deliveries.
- **Observed Architecture:** The administrative reviewer interface (`verification_detail.php`) utilizes embedded `<iframe>` elements to display submitted documents for manual inspection, and student dashboards display thumbnails via `<img>`. Enforcing attachment on all deliveries breaks the in-browser verification workflow.
- **Implemented Design:** `FileDeliveryService` serves documents with `Content-Disposition: inline` strictly for verified document types (`application/pdf`, `image/jpeg`, `image/png`) when rendered inside authenticated preview contexts, accompanied by a restrictive Content-Security-Policy (`default-src 'none'; frame-ancestors 'self'`). All direct download endpoints enforce `Content-Disposition: attachment`.
- **Proposed Thesis Amendment:**
  > *"File delivery enforces `Content-Disposition: attachment` across direct download endpoints, while providing strictly scoped `inline` delivery for verified MIME formats (PDF, JPEG, PNG) within an isolated, sandboxed viewer protected by restrictive Content Security Policy headers."*

---

## 4. D4: Audit Log Retention (Archival vs Deletion)
- **Current Thesis Wording:** References automated deletion or cleanup of audit logs older than one year (`CleanupOldAuditLogs`).
- **Observed Security Conflict:** Tamper-evident hash chaining ($H_i = \text{SHA-256}(H_{i-1} \parallel E_i)$) is broken if arbitrary historical log entries are deleted from the table, causing false-positive verification errors.
- **Implemented Design:** Audit logs are append-only. Triggers prevent `UPDATE` and `DELETE` on `audit_logs`. The maintenance job archives expired entries to a dedicated `audit_logs_archive` table accompanied by a cryptographic chain checkpoint, ensuring the chain remains verifiably intact.
- **Proposed Thesis Amendment:**
  > *"To preserve the mathematical integrity of the cryptographic hash chain, audit logs are strictly append-only. Maintenance routines archive historical entries into a cryptographically sealed archive table rather than deleting records, maintaining full evidentiary auditability."*

---

## 5. D5: Academic Record Retention vs NDPA Data Erasure
- **Current Thesis & Policy Wording:** Privacy policy previously stated records are retained for five years after graduation, while the thesis states academic transcripts and degree certificates must be retained permanently.
- **Implemented Design:** Implemented distinct retention rules in `retention_rules`. Core academic records (transcripts, birth certificates, admission letters, examination result slips) are flagged as `core = 1` and excluded from automated erasure. Auxiliary or temporary clearance documents (`core = 0`) are purged after their retention window upon schedule execution.
- **Proposed Thesis Amendment:**
  > *"In accordance with Section 24(1) of the NDPA 2023, data retention is partitioned by record criticality: non-core clearance attachments are subject to configurable retention periods and automated zeroization, whereas core academic records are permanently preserved to uphold institutional academic registry continuity."*

---

## 6. D6: Distributed vs Local Rate Limiting
- **Current Thesis Wording:** States rate limiting is powered by Redis.
- **Implemented Design:** A multi-tier rate limiting architecture: sliding window rate limiter backed by Redis where configured and available, with an automatic, seamless fallback to the relational database `rate_limits` table if Redis is offline or not installed.
- **Proposed Thesis Amendment:**
  > *"Rate limiting operates on a dual-engine architecture: an in-memory sliding window limiter backed by Redis for high-throughput environments, falling back gracefully to a relational transaction table when Redis services are unavailable."*

---

## 7. D7: Asynchronous Daemon Antivirus Inspection
- **Current Thesis Wording:** States ClamAV scanning is asynchronous.
- **Implemented Design:** The upload controller buffers incoming files into a private staging directory (`STORAGE_ROOT/_staging`). A persistent queue worker connects to the ClamAV daemon (`clamd`) via UNIX domain socket or TCP, streaming data using chunked `nINSTREAM` protocol. Only clean files proceed through the blur and OCR stages to permanent storage.
- **Proposed Thesis Amendment:**
  > *"Upload inspection decouples ingestion from verification: incoming files are buffered in an isolated, non-executable staging directory and queued for asynchronous inspection by a background daemon worker executing memory-efficient chunked `nINSTREAM` socket communication with `clamd`."*
