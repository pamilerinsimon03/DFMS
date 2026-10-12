<?php
/**
 * DFMS Asynchronous Email Queue Daemon / Cron Processor
 * Run periodically via cron or continuously via CLI supervisor daemon.
 */

if (php_sapi_name() !== 'cli') {
    die("Error: This script can only be run from the command line.\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/MailService.php';

global $conn;
if (!$conn) {
    die("Database connection error.\n");
}

$stmt = $conn->prepare("SELECT id, recipient, subject, message FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY id ASC LIMIT 50");
if (!$stmt) {
    die("Failed to prepare statement: " . $conn->error . "\n");
}

$stmt->execute();
$queueItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($queueItems)) {
    echo "No pending email queue items found.\n";
    exit(0);
}

echo "Processing " . count($queueItems) . " queued email(s)...\n";

foreach ($queueItems as $item) {
    $id = $item['id'];
    $to = $item['recipient'];
    $subject = $item['subject'];
    $message = $item['message'];

    $conn->query("UPDATE email_queue SET attempts = attempts + 1 WHERE id = {$id}");

    $success = MailService::send($to, $subject, $message);

    if ($success) {
        $conn->query("UPDATE email_queue SET status = 'sent' WHERE id = {$id}");
        echo "[SUCCESS] Sent email ID {$id} to {$to}\n";
    } else {
        $conn->query("UPDATE email_queue SET status = 'failed' WHERE id = {$id}");
        echo "[FAILED] Failed to send email ID {$id} to {$to}\n";
    }
}

echo "Email queue processing complete.\n";
