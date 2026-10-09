<?php
/**
 * DFMS Storage Migration Script
 * 
 * Migrates existing legacy documents to the isolated, HMAC-addressed,
 * 2-hex sharded storage layout: {session}/{department}/{level}/{prefix}/{hash}.{ext}
 * 
 * Usage:
 *   php app/scripts/migrate_storage.php          # Execute migration
 *   php app/scripts/migrate_storage.php --dry-run# Preview migration
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/StoragePathResolver.php';
require_once __DIR__ . '/../models/User.php';

global $conn;

if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed.\n");
    exit(1);
}

$isDryRun = in_array('--dry-run', $argv, true);
$storageRoot = StoragePathResolver::getStorageRoot();
$logRoot = StoragePathResolver::getLogRoot();

$migrationLog = $logRoot . '/storage_migration.log';
if (!is_dir($logRoot)) {
    @mkdir($logRoot, 0750, true);
    @chmod($logRoot, 0750);
}

function logMigration(string $logFile, string $message): void {
    $line = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $message . "\n";
}

logMigration($migrationLog, "Starting DFMS Storage Migration (" . ($isDryRun ? "DRY-RUN" : "LIVE") . ")...");
logMigration($migrationLog, "Target STORAGE_ROOT: {$storageRoot}");

// Query all documents
$query = "SELECT d.id, d.user_id, d.document_name, d.file_name, d.file_path, d.file_hash, 
                 d.department_id, d.level, u.session, dept.code as dept_code
          FROM documents d
          LEFT JOIN users u ON d.user_id = u.id
          LEFT JOIN departments dept ON d.department_id = dept.id";

$result = $conn->query($query);
if (!$result) {
    fwrite(STDERR, "Query failed: " . $conn->error . "\n");
    exit(1);
}

$total = $result->num_rows;
$migrated = 0;
$skipped = 0;
$missing = 0;

$repoRoot = realpath(__DIR__ . '/../..');

while ($doc = $result->fetch_assoc()) {
    $docId = (int)$doc['id'];
    $currentPath = $doc['file_path'];

    // Find physical file on disk
    $candidates = [
        $storageRoot . '/' . ltrim($currentPath, '/'),
        $repoRoot . '/' . ltrim($currentPath, '/'),
        $repoRoot . '/secure_uploads/' . basename($currentPath)
    ];

    $sourceFile = null;
    foreach ($candidates as $cand) {
        if (file_exists($cand) && is_file($cand)) {
            $sourceFile = realpath($cand);
            break;
        }
    }

    if (!$sourceFile) {
        logMigration($migrationLog, "[WARNING] Document ID #{$docId} file not found on disk. Recorded path: '{$currentPath}'");
        $missing++;
        continue;
    }

    $content = file_get_contents($sourceFile);
    if ($content === false) {
        logMigration($migrationLog, "[ERROR] Could not read file for Document ID #{$docId}: {$sourceFile}");
        $missing++;
        continue;
    }

    $plainHash = hash('sha256', $content);
    $hmacHash  = StoragePathResolver::computeHmac($content);

    // Determine extension via magic bytes
    $ext = StoragePathResolver::determineExtension($sourceFile);
    if (!$ext) {
        $ext = strtolower(pathinfo($sourceFile, PATHINFO_EXTENSION)) ?: 'pdf';
    }

    $session  = $doc['session'] ?: 'default_session';
    $deptCode = $doc['dept_code'] ?: 'GENERAL';
    $level    = $doc['level'] ?: '100';

    $target = StoragePathResolver::resolveStoragePath($session, $deptCode, $level, $hmacHash, $ext);

    // Check if already in the new layout
    if ($currentPath === $target['relative'] && $sourceFile === realpath($target['absolute'])) {
        logMigration($migrationLog, "[SKIP] Document ID #{$docId} already in canonical sharded location: {$target['relative']}");
        $skipped++;
        continue;
    }

    if ($isDryRun) {
        logMigration($migrationLog, "[DRY-RUN] Doc #{$docId}: Would move '{$sourceFile}' -> '{$target['absolute']}'");
        logMigration($migrationLog, "[DRY-RUN] Doc #{$docId}: Would update DB file_path='{$target['relative']}', file_hash='{$plainHash}'");
        $migrated++;
        continue;
    }

    // Execute move
    if (!copy($sourceFile, $target['absolute'])) {
        logMigration($migrationLog, "[ERROR] Failed to copy Document ID #{$docId} to destination: {$target['absolute']}");
        continue;
    }

    StoragePathResolver::secureFilePermissions($target['absolute']);

    // Verify hash integrity of destination file
    $destHash = hash_file('sha256', $target['absolute']);
    if ($destHash !== $plainHash) {
        logMigration($migrationLog, "[CRITICAL ERROR] Hash mismatch on copied file for Doc #{$docId}! Aborting move.");
        @unlink($target['absolute']);
        continue;
    }

    // Update database record
    $stmt = $conn->prepare("UPDATE documents SET file_path = ?, file_hash = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $target['relative'], $plainHash, $docId);
        $stmt->execute();
        $stmt->close();
    }

    // Remove source file if it was copied from an old path outside the target
    if ($sourceFile !== realpath($target['absolute'])) {
        @unlink($sourceFile);
    }

    logMigration($migrationLog, "[MIGRATED] Doc #{$docId}: '{$currentPath}' -> '{$target['relative']}' [SHA: " . substr($plainHash, 0, 8) . "...]");
    $migrated++;
}

logMigration($migrationLog, "Storage Migration Completed. Total: {$total}, Migrated: {$migrated}, Skipped: {$skipped}, Missing: {$missing}.");
