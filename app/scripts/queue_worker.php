<?php
// CLI Worker Process for DFMS Document Quality & OCR Async Processing
if (php_sapi_name() !== 'cli' && empty($_GET['run_once'])) {
    die("Worker must be run from CLI context or with run_once parameter.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/QualityCheckService.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

define('MIN_OCR_CONFIDENCE', 60);
define('MIN_BLURRINESS', 80);

echo "[" . date('Y-m-d H:i:s') . "] DFMS Queue Worker started.\n";

$singleRun = !empty($_GET['run_once']) || (isset($argv[1]) && $argv[1] === '--once');

while (true) {
    global $conn;

    if (!$conn) {
        echo "Database connection unavailable. Retrying in 5 seconds...\n";
        sleep(5);
        continue;
    }

    // Lock and retrieve next pending job
    $conn->begin_transaction();
    $sql = "SELECT q.*, d.document_name, d.user_id FROM document_processing_queue q 
            JOIN documents d ON q.document_id = d.id 
            WHERE q.status = 'pending' ORDER BY q.id ASC LIMIT 1 FOR UPDATE";
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

    // Mark job as processing
    $jobId = (int)$job['id'];
    $docId = (int)$job['document_id'];
    $userId = (int)$job['user_id'];

    $conn->query("UPDATE document_processing_queue SET status = 'processing', attempts = attempts + 1 WHERE id = {$jobId}");
    $conn->commit();

    echo "Processing Job #{$jobId} for Document #{$docId}...\n";

    $relativeFilePath = $job['file_path'];
    $fullPath = __DIR__ . '/../../' . ltrim($relativeFilePath, '/');
    $debugMessages = [];
    $passed = true;
    $failureReason = '';

    try {
        if (!file_exists($fullPath)) {
            throw new Exception("Uploaded file missing on disk: {$fullPath}");
        }

        // Image Blurriness Check
        if (QualityCheckService::isImage($fullPath)) {
            $blurriness = QualityCheckService::getBlurriness($fullPath, $debugMessages);
            if ($blurriness < 0) {
                $passed = false;
                $failureReason = "Could not analyze image quality.";
            } elseif ($blurriness < MIN_BLURRINESS) {
                $passed = false;
                $failureReason = "Image too blurry (Score: " . round($blurriness, 2) . "/80). Please upload a clearer image.";
            }
        }

        // OCR Confidence Check
        if ($passed) {
            $ocrConf = QualityCheckService::getOcrConfidence($fullPath, $debugMessages);
            if ($ocrConf < MIN_OCR_CONFIDENCE) {
                $passed = false;
                $msg = ($ocrConf <= 0) ? "Text not legible (No text detected)." : "Text not legible (Confidence: " . round($ocrConf) . "%).";
                $failureReason = "Upload failed: {$msg}";
            }
        }

        // Update Document & Queue status atomically
        $conn->begin_transaction();

        if ($passed) {
            $stmt = $conn->prepare("UPDATE documents SET status = 'Pending Approval' WHERE id = ?");
            $stmt->bind_param("i", $docId);
            $stmt->execute();

            Notification::create($userId, 'Document Quality Verified', "Your document '{$job['document_name']}' passed quality checks and is pending officer approval.");
            echo "Job #{$jobId} PASSED. Document #{$docId} marked Pending Approval.\n";
        } else {
            $stmt = $conn->prepare("UPDATE documents SET status = 'Rejected', rejection_reason = ? WHERE id = ?");
            $stmt->bind_param("si", $failureReason, $docId);
            $stmt->execute();

            Notification::create($userId, 'Quality Check Failed', "Your document '{$job['document_name']}' failed quality check: {$failureReason}");
            echo "Job #{$jobId} FAILED: {$failureReason}\n";
        }

        $logText = implode(' | ', $debugMessages);
        $doneStmt = $conn->prepare("UPDATE document_processing_queue SET status = 'completed', error_log = ? WHERE id = ?");
        $doneStmt->bind_param("si", $logText, $jobId);
        $doneStmt->execute();

        $conn->commit();

    } catch (Exception $e) {
        $conn->begin_transaction();
        $errLog = $e->getMessage();
        $failStmt = $conn->prepare("UPDATE document_processing_queue SET status = 'failed', error_log = ? WHERE id = ?");
        $failStmt->bind_param("si", $errLog, $jobId);
        $failStmt->execute();

        // Revert document to pending check or flag error
        $docStmt = $conn->prepare("UPDATE documents SET status = 'Pending Quality Check' WHERE id = ?");
        $docStmt->bind_param("i", $docId);
        $docStmt->execute();

        $conn->commit();
        echo "Job #{$jobId} Error: {$errLog}\n";
    }

    if ($singleRun) {
        break;
    }
}
