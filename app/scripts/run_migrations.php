<?php
/**
 * DFMS Canonical Migration Runner
 * 
 * Supports forward migrations with idempotency and schema tracking via `schema_migrations`.
 * Usage:
 *   php app/scripts/run_migrations.php          # Run pending migrations
 *   php app/scripts/run_migrations.php --status # View migration status
 *   php app/scripts/run_migrations.php --dry-run# Preview pending migrations
 */

require_once __DIR__ . '/../config/config.php';

global $conn;

if (!$conn || $conn->connect_error) {
    fwrite(STDERR, "Database connection failed: " . ($conn ? $conn->connect_error : "No connection") . "\n");
    exit(1);
}

// 1. Ensure schema_migrations table exists
$createMigrationsTableSql = "
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `version` VARCHAR(255) NOT NULL,
  `applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (!$conn->query($createMigrationsTableSql)) {
    fwrite(STDERR, "Failed to initialize schema_migrations table: " . $conn->error . "\n");
    exit(1);
}

// 2. Fetch applied migrations
$appliedMigrations = [];
$res = $conn->query("SELECT `version` FROM `schema_migrations` ORDER BY `version` ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $appliedMigrations[] = $row['version'];
    }
}

// 3. Scan migrations directory
$migrationsDir = realpath(__DIR__ . '/../../migrations');
if (!$migrationsDir || !is_dir($migrationsDir)) {
    echo "No migrations directory found.\n";
    exit(0);
}

$files = scandir($migrationsDir);
$migrationFiles = [];
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    if (pathinfo($file, PATHINFO_EXTENSION) === 'sql' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $migrationFiles[] = $file;
    }
}
sort($migrationFiles);

// CLI Flags
$isStatus = in_array('--status', $argv, true);
$isDryRun = in_array('--dry-run', $argv, true);

if ($isStatus) {
    echo "========================================================\n";
    echo " DFMS Database Migration Status\n";
    echo "========================================================\n";
    if (empty($migrationFiles)) {
        echo "No migration files discovered in {$migrationsDir}\n";
    }
    foreach ($migrationFiles as $mf) {
        $status = in_array($mf, $appliedMigrations, true) ? "[APPLIED]" : "[PENDING]";
        printf("%-12s %s\n", $status, $mf);
    }
    echo "========================================================\n";
    exit(0);
}

$pendingMigrations = array_values(array_diff($migrationFiles, $appliedMigrations));

if (empty($pendingMigrations)) {
    echo "Database is already up to date. No pending migrations.\n";
    exit(0);
}

echo "Found " . count($pendingMigrations) . " pending migration(s):\n";
foreach ($pendingMigrations as $pm) {
    echo " - {$pm}\n";
}

if ($isDryRun) {
    echo "\n[Dry-Run Mode] No changes were executed.\n";
    exit(0);
}

echo "\nApplying pending migrations...\n";

/**
 * Execute a .sql file supporting DELIMITER syntax
 */
function executeSqlMigration(mysqli $conn, string $filepath): void {
    $content = file_get_contents($filepath);
    if ($content === false) {
        throw new Exception("Unable to read file: " . $filepath);
    }
    
    $delimiter = ';';
    $lines = explode("\n", $content);
    $currentQuery = '';

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
            $delimiter = trim($matches[1]);
            continue;
        }

        // Skip line comments when not in a multi-line query
        if ($currentQuery === '' && (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#'))) {
            continue;
        }

        $currentQuery .= $line . "\n";

        // Check if line ends with the active delimiter
        if (str_ends_with(rtrim($line), $delimiter)) {
            $queryToExecute = trim(substr(rtrim($currentQuery), 0, -strlen($delimiter)));
            if ($queryToExecute !== '') {
                if (!$conn->query($queryToExecute)) {
                    throw new Exception("SQL error in " . basename($filepath) . ": " . $conn->error . "\nFailed query:\n" . $queryToExecute);
                }
                // Clear any stored results if multi-statement
                while ($conn->more_results() && $conn->next_result()) {
                    $extraRes = $conn->use_result();
                    if ($extraRes instanceof mysqli_result) {
                        $extraRes->free();
                    }
                }
            }
            $currentQuery = '';
        }
    }

    if (trim($currentQuery) !== '') {
        $queryToExecute = trim($currentQuery);
        if (!$conn->query($queryToExecute)) {
            throw new Exception("SQL error in " . basename($filepath) . ": " . $conn->error . "\nFailed query:\n" . $queryToExecute);
        }
    }
}

foreach ($pendingMigrations as $migration) {
    $filePath = $migrationsDir . '/' . $migration;
    echo "Running migration: {$migration}... ";

    $ext = pathinfo($migration, PATHINFO_EXTENSION);
    try {
        if ($ext === 'sql') {
            executeSqlMigration($conn, $filePath);
        } elseif ($ext === 'php') {
            require $filePath;
        }

        // Record migration in schema_migrations
        $stmt = $conn->prepare("INSERT INTO `schema_migrations` (`version`) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $migration);
            $stmt->execute();
            $stmt->close();
        }

        echo "SUCCESS\n";
    } catch (Throwable $e) {
        echo "FAILED\n";
        fwrite(STDERR, "Migration {$migration} failed with error: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "\nAll migrations applied successfully.\n";
