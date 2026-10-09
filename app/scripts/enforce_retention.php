<?php
/**
 * DFMS Automated Document Retention Policy Enforcer
 * 
 * Implements NDPA 2023 s.24(1)(f) and GAID 2025 s.7 data minimisation and retention schedules.
 * Non-core documents whose retention schedule has expired are purged.
 * Core academic documents are permanently preserved and strictly skipped.
 * 
 * Usage:
 *   php app/scripts/enforce_retention.php           # Execute retention purge
 *   php app/scripts/enforce_retention.php --dry-run # Preview purge actions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/StoragePathResolver.php';
require_once __DIR__ . '/../services/AuditService.php';

global $conn;

if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Database connection unavailable\n");
    exit(1);
}

$isDryRun = in_array('--dry-run', $argv, true);
$isVerbose = in_array('--verbose', $argv, true);

echo "========================================================\n";
echo " DFMS Data Retention Policy Enforcer\n";
echo " Standard: NDPA 2023 s.24(1)(f) & GAID 2025 s.7\n";
if ($isDryRun) {
    echo " MODE: [DRY-RUN PREVIEW]\n";
}
echo "========================================================\n";

// Query documents that match retention rules
$sql = "
    SELECT 
        d.id,
        d.public_id,
        d.user_id,
        d.document_name,
        d.file_path,
        d.upload_date,
        d.status,
        r.retention_days,
        r.is_core_academic,
        DATEDIFF(NOW(), d.upload_date) as age_in_days
    FROM documents d
    JOIN retention_rules r ON (
        d.document_name = r.document_type 
        OR d.document_name LIKE CONCAT('%', r.document_type, '%')
        OR r.document_type LIKE CONCAT('%', d.document_name, '%')
    )
    WHERE d.status != 'Purged'
      AND r.is_core_academic = 0
      AND r.retention_days IS NOT NULL
      AND DATEDIFF(NOW(), d.upload_date) > r.retention_days
    ORDER BY d.id ASC
";

$res = $conn->query($sql);
if (!$res) {
    fwrite(STDERR, "Query error: " . $conn->error . "\n");
    exit(1);
}

$expiredDocs = $res->fetch_all(MYSQLI_ASSOC);
$totalExpired = count($expiredDocs);

echo "Discovered {$totalExpired} expired non-core document(s) scheduled for purging.\n";

$purgedCount = 0;
$fileDeletedCount = 0;

foreach ($expiredDocs as $doc) {
    $docId = (int)$doc['id'];
    $docName = $doc['document_name'];
    $age = (int)$doc['age_in_days'];
    $limit = (int)$doc['retention_days'];
    $filePath = $doc['file_path'];

    if ($isDryRun) {
        echo " [DRY-RUN] Would purge Doc #{$docId} ('{$docName}') - Age: {$age}d (Retention: {$limit}d)\n";
        $purgedCount++;
        continue;
    }

    // 1. Physically delete file outside webroot if present
    $physicalDeleted = false;
    if (!empty($filePath)) {
        try {
            $physicalPath = StoragePathResolver::resolvePhysicalPath($filePath);
            if (file_exists($physicalPath) && is_file($physicalPath)) {
                @unlink($physicalPath);
                $physicalDeleted = true;
                $fileDeletedCount++;
            }
        } catch (Throwable $e) {
            // Path traversal or resolver exception - log and continue
        }
    }

    // 2. Update document record to Purged status
    $updateStmt = $conn->prepare("
        UPDATE documents 
        SET file_path = NULL, 
            status = 'Purged', 
            purged_at = NOW(), 
            purge_reason = 'retention_policy' 
        WHERE id = ?
    ");

    if ($updateStmt) {
        $updateStmt->bind_param("i", $docId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    // 3. Log cryptographic audit entry
    AuditService::log(
        'document_retention_purged',
        [
            'document_id'    => $docId,
            'public_id'      => $doc['public_id'],
            'user_id'        => $doc['user_id'],
            'document_name'  => $docName,
            'retention_days' => $limit,
            'age_days'       => $age,
            'file_unlinked'  => $physicalDeleted,
            'statutory_ref'  => 'NDPA 2023 s.24(1)(f)'
        ],
        'system',
        'doc:' . $doc['public_id'],
        'cli/enforce_retention.php',
        'success'
    );

    $purgedCount++;
    if ($isVerbose) {
        echo " Purged Doc #{$docId} ('{$docName}') [file unlinked: " . ($physicalDeleted ? 'yes' : 'no') . "]\n";
    }
}

echo "========================================================\n";
echo "Retention run completed.\n";
echo "Total records purged: {$purgedCount}\n";
echo "Physical files unlinked: {$fileDeletedCount}\n";
echo "========================================================\n";

exit(0);
