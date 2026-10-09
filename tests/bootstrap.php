<?php

// Define environment variables if not present
if (!isset($_SERVER['DB_HOST'])) $_SERVER['DB_HOST'] = 'localhost';
if (!isset($_SERVER['DB_USER'])) $_SERVER['DB_USER'] = 'root';
if (!isset($_SERVER['DB_PASS'])) $_SERVER['DB_PASS'] = '';
if (!isset($_SERVER['DB_NAME'])) $_SERVER['DB_NAME'] = 'dfms_test';

// Use specific test database credentials
$db_host = $_SERVER['DB_HOST'];
$db_user = $_SERVER['DB_USER'];
$db_pass = $_SERVER['DB_PASS'];
$db_name = $_SERVER['DB_NAME']; // Force test database

// Connect to MySQL server (without selecting DB yet to ensure it exists)
define('TEST_MODE', true);

// Connect to MySQL server (without selecting DB yet to ensure it exists)
// Wrap in try-catch for offline testing
try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
    $GLOBALS['conn'] = $conn;

    // Create test database if it doesn't exist
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name`");
    $conn->select_db($db_name);
} catch (Throwable $e) {
    echo "Warning: Could not connect to database. Integration tests will fail. Error: " . $e->getMessage() . "\n";
    $conn = null;
    $GLOBALS['conn'] = null;
}

// Function to reset database (can be called in setup)
function resetDatabase() {
    // Use GLOBALS to ensure access if variable scope is lost
    if (!isset($GLOBALS['conn']) || !$GLOBALS['conn'] instanceof mysqli) {
        $GLOBALS['conn'] = new mysqli($GLOBALS['db_host'] ?? 'localhost', $GLOBALS['db_user'] ?? 'root', $GLOBALS['db_pass'] ?? '', $GLOBALS['db_name'] ?? 'dfms_test');
    }
    $conn = $GLOBALS['conn'];
    
    if ($conn->connect_error || !$conn->ping()) {
        $conn->close();
        $GLOBALS['conn'] = new mysqli($GLOBALS['db_host'] ?? 'localhost', $GLOBALS['db_user'] ?? 'root', $GLOBALS['db_pass'] ?? '', $GLOBALS['db_name'] ?? 'dfms_test');
        $conn = $GLOBALS['conn'];
    }
    
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all tables
    $result = $conn->query("SHOW TABLES");
    if ($result === false) { fwrite(STDERR, "Show tables failed: " . $conn->error . "\n"); return; }
    while ($row = $result->fetch_array()) {
        $conn->query("DROP TABLE IF EXISTS `" . $row[0] . "`");
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Re-import schema
    // Assuming dfms_database.sql is in the root
    // Re-import schema
    // Re-import schema using MySQL CLI for robustness (handles DELIMITERs etc.)
    $sql_file = __DIR__ . '/../dfms_database.sql';
    if (file_exists($sql_file)) {
        global $db_host, $db_user, $db_pass, $db_name;
        // Fallback if globals not set (e.g. strict scope)
        $h = $GLOBALS['db_host'] ?? 'localhost';
        $u = $GLOBALS['db_user'] ?? 'root';
        $p = $GLOBALS['db_pass'] ?? '';
        $d = $GLOBALS['db_name'] ?? 'dfms_test';

        $passwordArg = empty($p) ? '' : "-p\"$p\"";
        // Use full path to sql file just in case
        $sql_file = realpath($sql_file);
        
        $cmd = "mysql --max_allowed_packet=64M -h \"$h\" -u \"$u\" $passwordArg \"$d\" < \"$sql_file\"";
        
        // Capture output for debug if needed
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            echo "Warning: Database import failed with code $returnVar. Output: " . implode("\n", $output) . "\n";
            echo "Command was: $cmd\n";
        }
        
        // Reconnect to ensure fresh state
        $conn->close();
        $GLOBALS['conn'] = new mysqli($h, $u, $p, $d);
        if ($GLOBALS['conn']->connect_error) {
             die("Reconnection failed: " . $GLOBALS['conn']->connect_error);
        }
    }
}

// Initial reset
resetDatabase();

// Define constants expected by the app
if (!defined('DB_HOST')) define('DB_HOST', $db_host);
if (!defined('DB_USER')) define('DB_USER', $db_user);
if (!defined('DB_PASS')) define('DB_PASS', $db_pass);
if (!defined('DB_NAME')) define('DB_NAME', $db_name);

echo "Test environment bootstrapping complete. Connected to $db_name.\n";
