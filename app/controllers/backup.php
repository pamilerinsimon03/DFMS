<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/SecurityUtils.php';

// Disable compression/buffering issues during large file compile
while (ob_get_level()) {
    ob_end_clean();
}

// 1. Rigorous session and role enforcement
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit("Unauthorized Access");
}

$user = User::getById($_SESSION['user_id']);
if (!$user || $user['matric_number'] !== 'SUPER_ADMIN') {
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied: Super Admin only.");
}

// Helper to compile a self-contained SQL dump dynamically
function getDatabaseDump() {
    global $conn;
    $output = "-- ============================================\n";
    $output .= "-- DFMS System Hot Backup Database Dump\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- ============================================\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }
    
    foreach ($tables as $table) {
        // Create table structure
        $resStructure = $conn->query("SHOW CREATE TABLE `$table`");
        if ($resStructure) {
            $rowStructure = $resStructure->fetch_row();
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $rowStructure[1] . ";\n\n";
        }
        
        // Dump rows
        $resRows = $conn->query("SELECT * FROM `$table`");
        if ($resRows) {
            $numFields = $resRows->field_count;
            while ($row = $resRows->fetch_row()) {
                $output .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    if (isset($row[$j])) {
                        $val = $conn->real_escape_string($row[$j]);
                        $output .= '"' . $val . '"';
                    } else {
                        $output .= 'NULL';
                    }
                    if ($j < ($numFields - 1)) {
                        $output .= ',';
                    }
                }
                $output .= ");\n";
            }
            $output .= "\n\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $output;
}

// 2. Initialize ZIP Archive
$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'dfms_bk_') . '.zip';

if ($zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("Could not initialize ZIP package creation.");
}

// 3. Compile and add database dump to ZIP
$dbSql = getDatabaseDump();
$zip->addFromString('database_schema_and_records.sql', $dbSql);

// 4. Archive uploaded documents folder
$uploadsDir = __DIR__ . '/../../secure_uploads';
if (is_dir($uploadsDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadsDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = 'secure_uploads/' . substr($filePath, strlen($uploadsDir) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$zip->close();

if ($zip->status !== ZIPARCHIVE::ER_OK) {
    header("HTTP/1.1 500 Internal Server Error");
    exit("ZIP compilation failed.");
}

// 5. Audit Logging - industry-standard audit logging
SecurityUtils::logSecurityEvent('system_backup_generated', [
    'user_id' => $_SESSION['user_id'],
    'file_size' => filesize($tmpFile),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
]);

// Update settings with last backup date
$settings_file = __DIR__ . '/../config/settings.json';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true) ?? [];
    $settings['last_backup_date'] = date('F j, Y, g:i a');
    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
}

// 6. Stream file to browser
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="dfms_backup_' . date('Y_m_d_His') . '.zip"');
header('Content-Length: ' . filesize($tmpFile));
header('Pragma: public');
header('Cache-Control: must-revalidate');
header('Expires: 0');

flush();
readfile($tmpFile);

// 7. Instant storage cleanup of temp compilation archive
@unlink($tmpFile);
exit();
?>
