<?php
/**
 * DFMS Cryptographic Audit Log Chain Verifier
 * 
 * Verifies that the audit log chain satisfies:
 *   H_i = SHA-256( H_{i-1} || canonical_json(E_i) )
 * 
 * Usage:
 *   php app/scripts/verify_audit_chain.php
 *   php app/scripts/verify_audit_chain.php --json
 * 
 * Exit codes:
 *   0: Valid chain (or empty chain)
 *   1: Broken chain or tampering detected
 *   2: Runtime or database connection error
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/AuditService.php';

global $conn;

if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed: " . ($conn ? $conn->connect_error : "No connection") . "\n");
    exit(2);
}

$isJson = in_array('--json', $argv, true);

$report = AuditService::verifyChain($conn);

if ($isJson) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit($report['valid'] ? 0 : 1);
}

if ($report['valid']) {
    echo "========================================================\n";
    echo " DFMS Audit Log Chain Verification: VALID [OK]\n";
    echo "========================================================\n";
    echo "Total entries verified: " . ($report['checked_count'] ?? 0) . "\n";
    echo "Genesis Hash:           " . AuditService::GENESIS_HASH . "\n";
    echo "Latest Entry Hash:      " . ($report['latest_hash'] ?? 'N/A') . "\n";
    echo "Status:                 All cryptographic hashes and linkage verified.\n";
    echo "========================================================\n";
    exit(0);
} else {
    fwrite(STDERR, "========================================================\n");
    fwrite(STDERR, " DFMS Audit Log Chain Verification: FAILED [TAMPER DETECTED]\n");
    fwrite(STDERR, "========================================================\n");
    fwrite(STDERR, "Broken Entry ID: " . ($report['broken_id'] ?? 'unknown') . "\n");
    fwrite(STDERR, "Timestamp:       " . ($report['timestamp'] ?? 'unknown') . "\n");
    fwrite(STDERR, "Failure Reason:  " . ($report['reason'] ?? 'Unknown error') . "\n");
    fwrite(STDERR, "Expected Hash:   " . ($report['expected_hash'] ?? 'N/A') . "\n");
    fwrite(STDERR, "Actual Hash:     " . ($report['actual_hash'] ?? 'N/A') . "\n");
    fwrite(STDERR, "Entries Checked: " . ($report['checked_count'] ?? 0) . "\n");
    fwrite(STDERR, "========================================================\n");
    exit(1);
}
