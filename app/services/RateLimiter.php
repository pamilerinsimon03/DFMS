<?php
/**
 * DFMS Dual-Engine Sliding Window Rate Limiter
 * 
 * Supports Redis sliding window with transparent MySQL database fallback.
 * Tiers:
 *  - Authentication endpoints (login.php, reset_password.php, activate.php): 5 requests / min
 *  - General endpoints: 60 requests / min
 * Identifiers: per IP and per user.
 */

class RateLimiter
{
    public const AUTH_LIMIT = 5;
    public const GENERAL_LIMIT = 60;
    public const WINDOW_SECONDS = 60;

    /** @var Redis|null */
    private static $redis = null;
    private static bool $redisAttempted = false;

    /**
     * @return Redis|null
     */
    private static function getRedis()
    {
        if (self::$redisAttempted) {
            return self::$redis;
        }

        self::$redisAttempted = true;
        $redisHost = getenv('REDIS_HOST');
        if (!empty($redisHost) && class_exists('Redis')) {
            try {
                $r = new Redis();
                $port = (int)(getenv('REDIS_PORT') ?: 6379);
                $timeout = 0.5;
                if (@$r->connect($redisHost, $port, $timeout)) {
                    self::$redis = $r;
                }
            } catch (Throwable $e) {
                self::$redis = null;
            }
        }

        return self::$redis;
    }

    /**
     * Check if a hit is allowed under the sliding window
     *
     * @param string $key Identifier key (e.g. "ip:1.2.3.4", "user:U123456")
     * @param int $limit Maximum allowed requests in window
     * @param int $window Window duration in seconds (default 60)
     * @param mysqli|null $dbConn
     * @return bool True if allowed, false if limit exceeded
     */
    public static function checkHit(string $key, int $limit, int $window = self::WINDOW_SECONDS, ?mysqli $dbConn = null): bool
    {
        // 1. Try Redis
        $redis = self::getRedis();
        if ($redis) {
            try {
                $redisKey = 'dfms:ratelimit:' . hash('sha256', $key);
                $now = microtime(true);
                $cutoff = $now - $window;

                $redis->multi();
                $redis->zRemRangeByScore($redisKey, 0, (string)$cutoff);
                $redis->zCard($redisKey);
                $redis->zAdd($redisKey, $now, (string)$now . ':' . bin2hex(random_bytes(4)));
                $redis->expire($redisKey, $window + 10);
                $execRes = $redis->exec();

                if (is_array($execRes)) {
                    $count = (int)($execRes[1] ?? 0);
                    if ($count >= $limit) {
                        return false;
                    }
                    return true;
                }
            } catch (Throwable $e) {
                // Redis error -> fall through to database fallback
            }
        }

        // 2. Database Fallback (rate_limits table)
        global $conn;
        $db = $dbConn ?? $conn;

        if (!$db) {
            return true; // If no DB or offline, allow
        }

        $keyHash = hash('sha256', $key);
        $now = time();
        $cutoff = $now - $window;

        try {
            // Prune expired hits for this key
            $cleanStmt = $db->prepare("DELETE FROM rate_limits WHERE key_hash = ? AND `timestamp` < ?");
            if ($cleanStmt) {
                $cleanStmt->bind_param("si", $keyHash, $cutoff);
                $cleanStmt->execute();
                $cleanStmt->close();
            }

            // Count hits within sliding window
            $countStmt = $db->prepare("SELECT COUNT(*) AS hit_count FROM rate_limits WHERE key_hash = ? AND `timestamp` >= ?");
            if ($countStmt) {
                $countStmt->bind_param("si", $keyHash, $cutoff);
                $countStmt->execute();
                $res = $countStmt->get_result()->fetch_assoc();
                $countStmt->close();

                $currentHits = (int)($res['hit_count'] ?? 0);
                if ($currentHits >= $limit) {
                    return false;
                }
            }

            // Record this hit
            $insertStmt = $db->prepare("INSERT INTO rate_limits (key_hash, `timestamp`) VALUES (?, ?)");
            if ($insertStmt) {
                $insertStmt->bind_param("si", $keyHash, $now);
                $insertStmt->execute();
                $insertStmt->close();
            }

            return true;
        } catch (Throwable $e) {
            return true;
        }
    }

    /**
     * Enforce rate limiting for current HTTP request
     *
     * @param mysqli|null $dbConn
     */
    public static function enforce(?mysqli $dbConn = null): void
    {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
        $isAuthEndpoint = in_array($script, ['login.php', 'reset_password.php', 'activate.php'], true);

        $limit = $isAuthEndpoint ? self::AUTH_LIMIT : self::GENERAL_LIMIT;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Enforce per-IP limit
        if (!self::checkHit("ip:{$ip}", $limit, self::WINDOW_SECONDS, $dbConn)) {
            self::emitExceededResponse($ip, null, $limit);
        }

        // 2. Enforce per-user limit if user is identified
        $userIdentifier = null;
        if (!empty($_SESSION['user_id'])) {
            $userIdentifier = 'uid:' . $_SESSION['user_id'];
        } elseif (!empty($_POST['matric_number'])) {
            $userIdentifier = 'matric:' . trim((string)$_POST['matric_number']);
        } elseif (!empty($_POST['email'])) {
            $userIdentifier = 'email:' . trim((string)$_POST['email']);
        }

        if ($userIdentifier !== null) {
            if (!self::checkHit("user:{$userIdentifier}", $limit, self::WINDOW_SECONDS, $dbConn)) {
                self::emitExceededResponse($ip, $userIdentifier, $limit);
            }
        }
    }

    /**
     * Terminate request with HTTP 429 Retry-After response
     */
    public static function emitExceededResponse(string $ip, ?string $userIdentifier, int $limit): void
    {
        require_once __DIR__ . '/../utils/SecurityUtils.php';
        SecurityUtils::logSecurityEvent('rate_limit_exceeded', [
            'ip'              => $ip,
            'user_identifier' => $userIdentifier,
            'tier_limit'      => $limit,
            'route'           => $_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? '')
        ], null, $userIdentifier, null, 'blocked');

        http_response_code(429);
        header('Retry-After: ' . self::WINDOW_SECONDS);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Too many requests. Please retry in 60 seconds.'
            ]);
            exit();
        } else {
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>429 Too Many Requests</title>
                <style>
                    body { font-family: sans-serif; background: #0b0f19; color: #fff; text-align: center; padding-top: 15vh; }
                    .box { display: inline-block; background: rgba(255,255,255,0.05); padding: 40px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); }
                    h1 { color: #f87171; font-size: 36px; margin: 0 0 10px; }
                    p { color: #cbd5e1; font-size: 16px; margin: 10px 0; }
                </style>
            </head>
            <body>
                <div class="box">
                    <h1>429 - Rate Limit Exceeded</h1>
                    <p>Too many requests. Please retry in 60 seconds.</p>
                </div>
            </body>
            </html>
            <?php
            exit();
        }
    }
}
