<?php
/**
 * DFMS Asynchronous Upload Queue Worker
 * 
 * Production CLI Worker processing queued documents through the 3-stage pipeline:
 * 1. ClamAV Antivirus Scanning (nINSTREAM chunked protocol over socket)
 * 2. Discrete Laplacian Variance Blur Test (reject strictly below threshold, default 100)
 * 3. Tesseract OCR & Field Extraction (cross-check against student record)
 * 
 * On failure: deletes staged file, sets status 'Rejected' with reason, notifies student.
 * On success: moves file to permanent content-addressed HMAC path, sets mode 0640,
 *             updates status to 'Pending Approval', and commits to ledger.
 */

if (php_sapi_name() !== 'cli' && empty($_GET['run_once'])) {
    die("Worker must be run from CLI context or with run_once parameter.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/ClamAVScannerService.php';
require_once __DIR__ . '/../services/QualityCheckService.php';
require_once __DIR__ . '/../utils/StoragePathResolver.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';

echo "[" . date('Y-m-d H:i:s') . "] DFMS Upload Pipeline Queue Worker started.\n";

$singleRun = !empty($_GET['run_once']) || (isset($argv[1]) && in_array($argv[1], ['--once', '-1'], true));
$maxAttempts = 3;

while (true) {
    global $conn;

    if (!$conn || $conn->connect_error) {
        echo "Database connection unavailable. Retrying in 5 seconds...\n";
        sleep(5);
        continue;
    }

    // Atomic fetch and lock next pending job
    $conn->begin_transaction();
    $sql = "SELECT q.*, d.document_name, d.user_id, d.level, d.semester, d.department_id,
                   u.full_name, u.matric_number, u.session, u.date_of_birth,
                   dept.code as dept_code
            FROM document_processing_queue q
            JOIN documents d ON q.document_id = d.id
            JOIN users u ON d.user_id = u.id
            LEFT JOIN departments dept ON d.department_id = dept.id
            WHERE q.status = 'pending' AND q.attempts < {$maxAttempts}
            ORDER BY q.id ASC
            LIMIT 1
            FOR UPDATE";

    $res = $conn->query($sql);
    $job = $res ? $res->fetch_assoc() : null;

    if (!$job) {
        $conn->commit();
        if ($singleRun) {
            echo "No pending jobs found. Exiting.\n";
            break;
        }
        sleep(2);
        continue;
    }

    $jobId      = (int)$job['id'];
    $docId      = (int)$job['document_id'];
    $userId     = (int)$job['user_id'];
    $attempts   = (int)$job['attempts'] + 1;
    $stagedRel  = $job['file_path'];

    // Mark job processing
    $conn->query("UPDATE document_processing_queue SET status = 'processing', attempts = {$attempts} WHERE id = {$jobId}");
    $conn->commit();

    echo "Processing Job #{$jobId} (Attempt {$attempts}/{$maxAttempts}) for Document #{$docId}...\n";

    $stagedFull = StoragePathResolver::resolveAbsolutePath($stagedRel);
    $debugMessages = [];

    try {
        if (!file_exists($stagedFull)) {
            throw new Exception("Staged file missing on disk: {$stagedFull}");
        }

        // =====================================================================
        // STAGE 1: ClamAV Antivirus Inspection (nINSTREAM)
        // =====================================================================
        echo "  [1/3] Running ClamAV scanner...\n";
        $clamResult = ClamAVScannerService::scanFile($stagedFull, $docId);

        if (!$clamResult['clean']) {
            if ($clamResult['status'] === 'pending') {
                // Daemon offline in production: keep job pending until daemon recovers
                $conn->query("UPDATE document_processing_queue SET status = 'pending', error_log = 'ClamAV daemon offline' WHERE id = {$jobId}");
                echo "  [ClamAV] Daemon offline. Job returned to pending.\n";
                if ($singleRun) break;
                continue;
            }

            // Malware detected!
            $virusName = $clamResult['virus'] ?? 'Malware signature detected';
            $failReason = "File rejected: Antivirus check detected malicious content ({$virusName}).";

            // Purge infected staged file immediately
            @unlink($stagedFull);

            Document::updateStatus($docId, 'reject', $failReason);
            $conn->query("UPDATE document_processing_queue SET status = 'completed', error_log = 'Rejected by ClamAV: " . addslashes($virusName) . "' WHERE id = {$jobId}");
            echo "  [ClamAV] FAILED: {$failReason}\n";
            if ($singleRun) break;
            continue;
        }
        echo "  [1/3] ClamAV check passed (clean).\n";

        // =====================================================================
        // STAGE 2: Discrete Laplacian Variance Blur Test (sigma_Lap^2 >= 100)
        // =====================================================================
        echo "  [2/3] Running Discrete Laplacian blur test...\n";
        $varianceThreshold = QualityCheckService::getBlurThreshold();
        $calculatedVariance = QualityCheckService::calculateLaplacianVariance($stagedFull, $debugMessages);

        if ($calculatedVariance < 0) {
            $failReason = "Unable to assess document visual quality. Ensure upload is a valid, uncorrupted image or PDF.";
            @unlink($stagedFull);
            Document::updateStatus($docId, 'reject', $failReason);
            $conn->query("UPDATE document_processing_queue SET status = 'completed', error_log = 'Blur assessment error' WHERE id = {$jobId}");
            echo "  [Blur] FAILED: {$failReason}\n";
            if ($singleRun) break;
            continue;
        }

        if ($calculatedVariance < $varianceThreshold) {
            $failReason = "Document image too blurry (Laplacian variance: " . round($calculatedVariance, 2) . " < {$varianceThreshold}). Please upload a sharper, legible scan.";
            @unlink($stagedFull);
            Document::updateStatus($docId, 'reject', $failReason);
            $conn->query("UPDATE document_processing_queue SET status = 'completed', error_log = 'Blur variance too low: " . round($calculatedVariance, 2) . "' WHERE id = {$jobId}");
            echo "  [Blur] FAILED: {$failReason}\n";
            if ($singleRun) break;
            continue;
        }
        echo "  [2/3] Blur check passed (Variance: " . round($calculatedVariance, 2) . " >= {$varianceThreshold}).\n";

        // =====================================================================
        // STAGE 3: Tesseract OCR & Field Extraction Cross-Check
        // =====================================================================
        echo "  [3/3] Running OCR extraction and student record cross-check...\n";
        $ocrData = QualityCheckService::runOcr($stagedFull, $debugMessages);
        $extractedFields = QualityCheckService::extractFields($ocrData['text'], $job['document_name']);
        $crossCheck = QualityCheckService::crossCheckWithStudent($extractedFields, $job);

        $ocrMatchStatus = $crossCheck['match_status']; // 'matched', 'mismatch', 'unreadable'
        $ocrExtractedJson = json_encode([
            'extracted_fields' => $extractedFields,
            'match_status'     => $ocrMatchStatus,
            'discrepancies'    => $crossCheck['discrepancies'],
            'explanation'      => $crossCheck['explanation'],
            'ocr_confidence'   => $ocrData['confidence']
        ], JSON_UNESCAPED_SLASHES);

        echo "  [3/3] OCR finished. Match Status: '{$ocrMatchStatus}'. Explanation: {$crossCheck['explanation']}\n";

        // =====================================================================
        // PROMOTION: Move Staged File to Permanent Content-Addressed Storage
        // =====================================================================
        $fileContent = file_get_contents($stagedFull);
        if ($fileContent === false) {
            throw new Exception("Could not read verified file for promotion.");
        }

        $hmacHash = StoragePathResolver::computeHmac($fileContent);
        $plainHash = hash('sha256', $fileContent);
        $ext = StoragePathResolver::determineExtension($stagedFull) ?: 'pdf';

        $sessionFolder = $job['session'] ?: '2025_2026';
        $deptCode      = $job['dept_code'] ?: 'GENERAL';
        $level         = $job['level'] ?: 100;

        $targetStorage = StoragePathResolver::resolveStoragePath($sessionFolder, $deptCode, $level, $hmacHash, $ext);
        $permanentPath = $targetStorage['absolute'];
        $relativePermanent = $targetStorage['relative'];

        // Atomic move from staging to permanent
        if (!rename($stagedFull, $permanentPath)) {
            // Fallback copy + unlink
            if (!copy($stagedFull, $permanentPath)) {
                throw new Exception("Failed to promote staged file to permanent storage: {$permanentPath}");
            }
            @unlink($stagedFull);
        }

        // Enforce 0640 permissions
        StoragePathResolver::secureFilePermissions($permanentPath);

        // Update Document row
        $conn->begin_transaction();
        $updDoc = $conn->prepare("UPDATE documents SET 
            file_path = ?,
            file_hash = ?,
            status = 'Pending Approval',
            blur_variance = ?,
            ocr_match_status = ?,
            ocr_extracted = ?,
            ocr_confidence = ?
            WHERE id = ?");

        $varianceDec = round($calculatedVariance, 2);
        $confDec = round($ocrData['confidence'], 2);

        $updDoc->bind_param("ssdsdsi", 
            $relativePermanent,
            $plainHash,
            $varianceDec,
            $ocrMatchStatus,
            $ocrExtractedJson,
            $confDec,
            $docId
        );
        $updDoc->execute();
        $updDoc->close();

        // Mark queue job completed
        $logSummary = "ClamAV: OK | Blur: {$varianceDec} | OCR: {$ocrMatchStatus}";
        $updJob = $conn->prepare("UPDATE document_processing_queue SET status = 'completed', error_log = ? WHERE id = ?");
        $updJob->bind_param("si", $logSummary, $jobId);
        $updJob->execute();
        $updJob->close();

        $conn->commit();

        // Dispatch notifications
        Notification::create(
            $userId,
            'Quality checks passed',
            "Your document '{$job['document_name']}' passed automated quality and security checks. It is now awaiting officer verification."
        );

        if ($ocrMatchStatus === 'mismatch') {
            Notification::create(
                $userId,
                'Document flagged for review',
                "Your document '{$job['document_name']}' has been forwarded for manual review due to a data discrepancy: {$crossCheck['explanation']}"
            );
        }

        echo "Job #{$jobId} COMPLETED SUCCESSFULLY. Document #{$docId} promoted to permanent storage.\n";

    } catch (Throwable $e) {
        $conn->begin_transaction();
        $errMsg = $e->getMessage();
        echo "Job #{$jobId} EXCEPTION: {$errMsg}\n";

        if ($attempts >= $maxAttempts) {
            // Max attempts reached: fail permanently
            SecurityUtils::logSecurityEvent('queue_job_max_attempts_exceeded', [
                'job_id'   => $jobId,
                'doc_id'   => $docId,
                'attempts' => $attempts,
                'error'    => $errMsg
            ]);

            $conn->query("UPDATE document_processing_queue SET status = 'failed', error_log = 'Failed after {$attempts} attempts: " . addslashes($errMsg) . "' WHERE id = {$jobId}");
            
            // Mark document rejected due to processing failure
            @unlink($stagedFull);
            $conn->query("UPDATE documents SET status = 'Rejected', rejection_reason = 'System processing error after multiple attempts. Please re-upload.' WHERE id = {$docId}");

            Notification::create(
                $userId,
                'Upload processing failed',
                "We were unable to process your document '{$job['document_name']}' after multiple attempts. Please re-upload a clear file."
            );
        } else {
            // Retry later
            $conn->query("UPDATE document_processing_queue SET status = 'pending', error_log = 'Attempt {$attempts} failed: " . addslashes($errMsg) . "' WHERE id = {$jobId}");
        }

        $conn->commit();
    }

    if ($singleRun) {
        break;
    }
}
