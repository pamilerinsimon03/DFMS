<?php
/**
 * DFMS Tamper-Evident Audit Logging Service
 * 
 * Implements cryptographic hash chaining for audit logs:
 *   H_i = SHA-256( H_{i-1} || canonical_json(E_i) )
 * 
 * - Genesis hash H_0 = 64 zeros.
 * - Canonical JSON enforces deterministic key sorting and unescaped Unicode/slashes.
 * - Database triggers block UPDATE and DELETE operations at the SQL layer.
 */

class AuditService
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Recursively sort associative array keys alphabetically
     *
     * @param array $arr
     * @return array
     */
    public static function canonicalizeArray(array $arr): array
    {
        ksort($arr);
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = self::canonicalizeArray($v);
            }
        }
        return $arr;
    }

    /**
     * Build canonical JSON representation of an audit event
     *
     * @param array $data Raw event fields
     * @return string Canonical deterministic JSON string
     */
    public static function buildCanonicalJson(array $data): string
    {
        $rawDetails = $data['details'] ?? $data['event_description'] ?? '';
        if (is_array($rawDetails)) {
            $sortedDetails = self::canonicalizeArray($rawDetails);
            $detailsStr = json_encode($sortedDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $detailsStr = (string)$rawDetails;
        }

        $payload = [
            'action'     => (string)($data['action'] ?? $data['event_type'] ?? ''),
            'details'    => $detailsStr,
            'ip_address' => (string)($data['ip_address'] ?? ''),
            'outcome'    => (string)($data['outcome'] ?? 'success'),
            'role'       => isset($data['role']) && $data['role'] !== null && $data['role'] !== '' ? (string)$data['role'] : null,
            'route'      => isset($data['route']) && $data['route'] !== null && $data['route'] !== '' ? (string)$data['route'] : null,
            'target'     => isset($data['target']) && $data['target'] !== null && $data['target'] !== '' ? (string)$data['target'] : null,
            'timestamp'  => (string)($data['timestamp'] ?? $data['created_at'] ?? ''),
            'user_id'    => isset($data['user_id']) && $data['user_id'] !== null && $data['user_id'] !== '' ? (int)$data['user_id'] : null,
        ];

        ksort($payload);
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Compute entry hash H_i = SHA-256( H_{i-1} || canonical_json(E_i) )
     *
     * @param string $prevHash 64-char hex hash
     * @param string $canonicalJson Deterministic JSON string
     * @return string 64-char hex SHA-256
     */
    public static function computeEntryHash(string $prevHash, string $canonicalJson): string
    {
        return hash('sha256', $prevHash . $canonicalJson);
    }

    /**
     * Append an audit event to the cryptographic ledger
     *
     * @param string $action Event action / event_type
     * @param array|string $details Additional context or payload
     * @param string|null $role Actor role
     * @param string|null $target Target resource identifier
     * @param string|null $route HTTP route or script
     * @param string $outcome 'success', 'failure', 'error', etc.
     * @param int|null $userId User ID
     * @param string|null $ip Remote IP address
     * @param string|null $ua User agent string
     * @param mysqli|null $dbConn Optional custom db connection
     * @return array Result of logging operation including id, prev_hash, entry_hash
     */
    public static function log(
        string $action,
        $details = [],
        ?string $role = null,
        ?string $target = null,
        ?string $route = null,
        string $outcome = 'success',
        ?int $userId = null,
        ?string $ip = null,
        ?string $ua = null,
        ?mysqli $dbConn = null
    ): array {
        global $conn;
        $db = $dbConn ?? $conn;

        // Context defaults from globals / session
        $resolvedUserId = $userId ?? ($_SESSION['user_id'] ?? null);
        $resolvedRole = $role ?? ($_SESSION['role'] ?? null);
        $resolvedIp = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $resolvedUa = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System');
        $resolvedRoute = $route ?? ($_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? null));

        // Infer fields from $details array if not explicitly passed
        if (is_array($details)) {
            if ($resolvedRole === null && isset($details['role'])) {
                $resolvedRole = (string)$details['role'];
            }
            if ($target === null) {
                if (isset($details['target'])) $target = (string)$details['target'];
                elseif (isset($details['doc_id'])) $target = 'doc:' . $details['doc_id'];
                elseif (isset($details['public_id'])) $target = 'doc:' . $details['public_id'];
                elseif (isset($details['matric'])) $target = 'user:' . $details['matric'];
                elseif (isset($details['matric_number'])) $target = 'user:' . $details['matric_number'];
                elseif (isset($details['field'])) $target = 'field:' . $details['field'];
            }
            if ($route === null && isset($details['route'])) {
                $resolvedRoute = (string)$details['route'];
            }
            if ($outcome === 'success' && isset($details['outcome'])) {
                $outcome = (string)$details['outcome'];
            }
        }

        $nowTimestamp = date('Y-m-d H:i:s');

        if (!$db) {
            // Offline fallback: write to log file without DB
            return [
                'success'    => false,
                'id'         => null,
                'prev_hash'  => self::GENESIS_HASH,
                'entry_hash' => null,
                'error'      => 'Database connection unavailable'
            ];
        }

        try {
            $db->begin_transaction();

            // Fetch the last entry hash with exclusive lock
            $prevHash = self::GENESIS_HASH;
            $res = $db->query("SELECT id, entry_hash FROM audit_logs ORDER BY id DESC LIMIT 1 FOR UPDATE");
            if ($res && $row = $res->fetch_assoc()) {
                if (!empty($row['entry_hash'])) {
                    $prevHash = $row['entry_hash'];
                }
            }

            // Build canonical JSON
            $canonicalData = [
                'action'     => $action,
                'details'    => $details,
                'ip_address' => $resolvedIp,
                'outcome'    => $outcome,
                'role'       => $resolvedRole,
                'route'      => $resolvedRoute,
                'target'     => $target,
                'timestamp'  => $nowTimestamp,
                'user_id'    => $resolvedUserId,
            ];

            $canonicalJson = self::buildCanonicalJson($canonicalData);
            $entryHash = self::computeEntryHash($prevHash, $canonicalJson);

            // Serialize details string for storage
            $detailsStored = is_array($details)
                ? json_encode(self::canonicalizeArray($details), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                : (string)$details;

            $stmt = $db->prepare("
                INSERT INTO audit_logs 
                    (user_id, role, action, event_type, details, event_description, target, route, outcome, ip_address, user_agent, prev_hash, entry_hash, `timestamp`, created_at)
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $db->error);
            }

            $stmt->bind_param(
                "issssssssssssss",
                $resolvedUserId,
                $resolvedRole,
                $action,
                $action,
                $detailsStored,
                $detailsStored,
                $target,
                $resolvedRoute,
                $outcome,
                $resolvedIp,
                $resolvedUa,
                $prevHash,
                $entryHash,
                $nowTimestamp,
                $nowTimestamp
            );

            $stmt->execute();
            $insertId = $stmt->insert_id;
            $stmt->close();

            $db->commit();

            return [
                'success'    => true,
                'id'         => $insertId,
                'prev_hash'  => $prevHash,
                'entry_hash' => $entryHash,
                'canonical'  => $canonicalJson
            ];

        } catch (Throwable $e) {
            $db->rollback();
            return [
                'success'    => false,
                'id'         => null,
                'prev_hash'  => null,
                'entry_hash' => null,
                'error'      => $e->getMessage()
            ];
        }
    }

    /**
     * Sequentially verify the integrity of the audit hash chain
     *
     * @param mysqli|null $dbConn
     * @return array Verification report
     */
    public static function verifyChain(?mysqli $dbConn = null): array
    {
        global $conn;
        $db = $dbConn ?? $conn;

        if (!$db) {
            return [
                'valid' => false,
                'error' => 'No database connection available'
            ];
        }

        $res = $db->query("SELECT * FROM audit_logs ORDER BY id ASC");
        if (!$res) {
            return [
                'valid' => false,
                'error' => 'Query error: ' . $db->error
            ];
        }

        $expectedPrev = self::GENESIS_HASH;
        $count = 0;

        while ($row = $res->fetch_assoc()) {
            $count++;
            $currentId = (int)$row['id'];
            $storedPrev = (string)($row['prev_hash'] ?? '');
            $storedEntry = (string)($row['entry_hash'] ?? '');
            $timestamp = (string)($row['timestamp'] ?? $row['created_at'] ?? '');

            // 1. Verify previous hash chaining
            if ($storedPrev !== $expectedPrev) {
                return [
                    'valid'         => false,
                    'broken_id'     => $currentId,
                    'timestamp'     => $timestamp,
                    'reason'        => 'Previous hash linkage broken (prev_hash mismatch)',
                    'expected_hash' => $expectedPrev,
                    'actual_hash'   => $storedPrev,
                    'checked_count' => $count
                ];
            }

            // 2. Reconstruct canonical JSON
            $canonicalData = [
                'action'     => (string)($row['action'] ?? $row['event_type'] ?? ''),
                'details'    => (string)($row['details'] ?? $row['event_description'] ?? ''),
                'ip_address' => (string)($row['ip_address'] ?? ''),
                'outcome'    => (string)($row['outcome'] ?? 'success'),
                'role'       => isset($row['role']) && $row['role'] !== null && $row['role'] !== '' ? (string)$row['role'] : null,
                'route'      => isset($row['route']) && $row['route'] !== null && $row['route'] !== '' ? (string)$row['route'] : null,
                'target'     => isset($row['target']) && $row['target'] !== null && $row['target'] !== '' ? (string)$row['target'] : null,
                'timestamp'  => $timestamp,
                'user_id'    => isset($row['user_id']) && $row['user_id'] !== null && $row['user_id'] !== '' ? (int)$row['user_id'] : null,
            ];

            $canonicalJson = self::buildCanonicalJson($canonicalData);
            $recalculatedHash = self::computeEntryHash($storedPrev, $canonicalJson);

            // 3. Verify entry hash integrity
            if ($storedEntry !== $recalculatedHash) {
                return [
                    'valid'         => false,
                    'broken_id'     => $currentId,
                    'timestamp'     => $timestamp,
                    'reason'        => 'Entry hash payload mismatch (tampering detected)',
                    'expected_hash' => $recalculatedHash,
                    'actual_hash'   => $storedEntry,
                    'checked_count' => $count
                ];
            }

            $expectedPrev = $storedEntry;
        }

        return [
            'valid'         => true,
            'checked_count' => $count,
            'genesis_hash'  => self::GENESIS_HASH,
            'latest_hash'   => $expectedPrev
        ];
    }

    /**
     * Backfill legacy unchained entries in audit_logs
     *
     * @param mysqli|null $dbConn
     * @return int Number of rows backfilled
     */
    public static function backfillLegacyEntries(?mysqli $dbConn = null): int
    {
        global $conn;
        $db = $dbConn ?? $conn;

        if (!$db) return 0;

        $check = $db->query("SELECT id FROM audit_logs WHERE entry_hash IS NULL ORDER BY id ASC");
        if (!$check || $check->num_rows === 0) {
            return 0;
        }

        $db->query("SET @ALLOW_AUDIT_LOG_MUTATION = 1");

        $prevHash = self::GENESIS_HASH;
        $rows = $db->query("SELECT * FROM audit_logs ORDER BY id ASC");
        $backfilled = 0;

        while ($row = $rows->fetch_assoc()) {
            $currentId = (int)$row['id'];
            if (!empty($row['entry_hash'])) {
                $prevHash = $row['entry_hash'];
                continue;
            }

            $timestamp = (string)($row['timestamp'] ?? $row['created_at'] ?? date('Y-m-d H:i:s'));
            $canonicalData = [
                'action'     => (string)($row['action'] ?? $row['event_type'] ?? ''),
                'details'    => (string)($row['details'] ?? $row['event_description'] ?? ''),
                'ip_address' => (string)($row['ip_address'] ?? ''),
                'outcome'    => (string)($row['outcome'] ?? 'success'),
                'role'       => isset($row['role']) && $row['role'] !== null && $row['role'] !== '' ? (string)$row['role'] : null,
                'route'      => isset($row['route']) && $row['route'] !== null && $row['route'] !== '' ? (string)$row['route'] : null,
                'target'     => isset($row['target']) && $row['target'] !== null && $row['target'] !== '' ? (string)$row['target'] : null,
                'timestamp'  => $timestamp,
                'user_id'    => isset($row['user_id']) && $row['user_id'] !== null && $row['user_id'] !== '' ? (int)$row['user_id'] : null,
            ];

            $canonicalJson = self::buildCanonicalJson($canonicalData);
            $entryHash = self::computeEntryHash($prevHash, $canonicalJson);

            $action = (string)($row['action'] ?? $row['event_type'] ?? '');
            $details = (string)($row['details'] ?? $row['event_description'] ?? '');

            $stmt = $db->prepare("UPDATE audit_logs SET prev_hash = ?, entry_hash = ?, `timestamp` = ?, action = ?, details = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssi", $prevHash, $entryHash, $timestamp, $action, $details, $currentId);
                $stmt->execute();
                $stmt->close();
            }

            $prevHash = $entryHash;
            $backfilled++;
        }

        $db->query("SET @ALLOW_AUDIT_LOG_MUTATION = NULL");
        return $backfilled;
    }
}
