<?php
/**
 * DFMS Two-Stage Authorization & AccessScope Benchmark
 * 
 * Measures execution overhead of:
 * 1. Stage 1: Route-level RBAC permission matrix checks (AccessControlMiddleware::can)
 * 2. Stage 2a: Query-level SQL predicate synthesis (AccessScope::buildDocumentScopeFilter & buildUserScopeFilter)
 * 3. Stage 2b: Object-level in-memory scoping validation (AccessScope::isDocumentInScope)
 * 4. Full End-to-End Two-Stage Authorization Pipeline
 * 
 * Benchmarked over 1,000 iterations per role:
 * - super_admin (Global scope)
 * - faculty_admin (Faculty scope)
 * - department_admin (Department scope)
 * - student (Self/Owner scope)
 * 
 * Calculates mean, p50, p95, p99 latency in microseconds and throughput in operations/second.
 */

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/utils/AccessControlMiddleware.php';
require_once __DIR__ . '/../../app/utils/AccessScope.php';

class AuthBenchmark
{
    private const ITERATIONS = 1000;

    /**
     * Define mock user profiles representing each tier of the RBAC hierarchy
     */
    private function getProfiles(): array
    {
        return [
            'super_admin' => [
                'id'            => 1,
                'role'          => 'super_admin',
                'faculty_id'    => null,
                'department_id' => null,
                'user_id'       => 1,
                'student_id'    => null,
                'public_id'     => '11111111-1111-4111-8111-111111111111'
            ],
            'faculty_admin' => [
                'id'            => 2,
                'role'          => 'faculty_admin',
                'faculty_id'    => 1,
                'department_id' => null,
                'user_id'       => 2,
                'student_id'    => null,
                'public_id'     => '22222222-2222-4222-8222-222222222222'
            ],
            'department_admin' => [
                'id'            => 3,
                'role'          => 'department_admin',
                'faculty_id'    => 1,
                'department_id' => 1,
                'user_id'       => 3,
                'student_id'    => null,
                'public_id'     => '33333333-3333-4333-8333-333333333333'
            ],
            'student' => [
                'id'            => 10,
                'role'          => 'student',
                'faculty_id'    => 1,
                'department_id' => 1,
                'user_id'       => 10,
                'student_id'    => 10,
                'public_id'     => '44444444-4444-4444-8444-444444444444'
            ]
        ];
    }

    /**
     * Target document object for in-scope evaluation
     */
    private function getTargetDocument(): array
    {
        return [
            'id'            => 501,
            'student_id'    => 10,
            'user_id'       => 10,
            'department_id' => 1,
            'faculty_id'    => 1,
            'session'       => '2024/2025',
            'level'         => 100,
            'public_id'     => '55555555-5555-4555-8555-555555555555'
        ];
    }

    /**
     * Compute statistical percentiles from array of microsecond timings
     */
    private function computeStats(array $timingsUs): array
    {
        sort($timingsUs);
        $count = count($timingsUs);
        $totalUs = array_sum($timingsUs);

        $mean = $totalUs / $count;
        $p50 = $timingsUs[(int)floor($count * 0.50)];
        $p95 = $timingsUs[(int)floor($count * 0.95)];
        $p99 = $timingsUs[(int)floor($count * 0.99)];
        $min = $timingsUs[0];
        $max = $timingsUs[$count - 1];

        // Throughput: ops per second = (count / (totalUs / 1e6))
        $throughput = ($totalUs > 0) ? ($count / ($totalUs / 1_000_000)) : 0.0;

        return [
            'iterations' => $count,
            'mean_us'    => round($mean, 3),
            'p50_us'     => round($p50, 3),
            'p95_us'     => round($p95, 3),
            'p99_us'     => round($p99, 3),
            'min_us'     => round($min, 3),
            'max_us'     => round($max, 3),
            'ops_sec'    => round($throughput, 0)
        ];
    }

    public function run(): array
    {
        $profiles = $this->getProfiles();
        $targetDoc = $this->getTargetDocument();
        $benchmarkResults = [];

        // Warmup JIT/opcache
        for ($i = 0; $i < 50; $i++) {
            foreach ($profiles as $user) {
                $allowed = AccessControlMiddleware::can($user, 'secure_file_viewer.php');
                AccessScope::buildDocumentScopeFilter($user, null, 'd.');
                AccessScope::isDocumentInScope($user, $targetDoc);
            }
        }

        foreach ($profiles as $role => $user) {
            // 1. Stage 1: RBAC Permission Check
            $timingsRbac = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $t0 = hrtime(true);
                $allowed = AccessControlMiddleware::can($user, 'secure_file_viewer.php');
                $t1 = hrtime(true);
                $timingsRbac[] = ($t1 - $t0) / 1000.0; // nanoseconds to microseconds
            }

            // 2. Stage 2a: SQL Predicate Synthesis (Document Scope)
            $timingsSqlDoc = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $t0 = hrtime(true);
                $filter = AccessScope::buildDocumentScopeFilter($user, null, 'd.');
                $t1 = hrtime(true);
                $timingsSqlDoc[] = ($t1 - $t0) / 1000.0;
            }

            // 3. Stage 2b: SQL Predicate Synthesis (User Scope)
            $timingsSqlUser = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $t0 = hrtime(true);
                $filter = AccessScope::buildUserScopeFilter($user, null, 'u.');
                $t1 = hrtime(true);
                $timingsSqlUser[] = ($t1 - $t0) / 1000.0;
            }

            // 4. Stage 2c: In-Memory Object Scope Verification
            $timingsObj = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $t0 = hrtime(true);
                $inScope = AccessScope::isDocumentInScope($user, $targetDoc);
                $t1 = hrtime(true);
                $timingsObj[] = ($t1 - $t0) / 1000.0;
            }

            // 5. Full End-to-End Pipeline (RBAC + SQL Filter + Object Scoping)
            $timingsE2e = [];
            for ($i = 0; $i < self::ITERATIONS; $i++) {
                $t0 = hrtime(true);
                $passRbac = AccessControlMiddleware::can($user, 'secure_file_viewer.php');
                $sqlClause = AccessScope::buildDocumentScopeFilter($user, null, 'd.');
                $passObj  = AccessScope::isDocumentInScope($user, $targetDoc);
                $t1 = hrtime(true);
                $timingsE2e[] = ($t1 - $t0) / 1000.0;
            }

            $benchmarkResults[$role] = [
                'role'           => $role,
                'rbac'           => $this->computeStats($timingsRbac),
                'sql_doc_scope'  => $this->computeStats($timingsSqlDoc),
                'sql_user_scope' => $this->computeStats($timingsSqlUser),
                'object_scope'   => $this->computeStats($timingsObj),
                'full_pipeline'  => $this->computeStats($timingsE2e),
            ];
        }

        return $benchmarkResults;
    }
}

// Standalone CLI execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    echo "========================================================================\n";
    echo "DFMS Two-Stage Authorization & Scoping Overhead Benchmark\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP Version: " . PHP_VERSION . " (" . PHP_OS . ")\n";
    echo "Iterations per Role: 1,000 iterations\n";
    echo "========================================================================\n\n";

    $benchmark = new AuthBenchmark();
    $results = $benchmark->run();

    echo "--- STAGE 1: RBAC Permission Evaluation (AccessControlMiddleware::can) ---\n";
    printf("%-18s | %10s | %10s | %10s | %10s | %12s\n", "Role", "Mean (µs)", "p50 (µs)", "p95 (µs)", "p99 (µs)", "Throughput (ops/s)");
    echo str_repeat("-", 85) . "\n";
    foreach ($results as $role => $data) {
        $s = $data['rbac'];
        printf("%-18s | %10.3f | %10.3f | %10.3f | %10.3f | %12s\n", $role, $s['mean_us'], $s['p50_us'], $s['p95_us'], $s['p99_us'], number_format($s['ops_sec']));
    }

    echo "\n--- STAGE 2: Query-Level SQL Scope Synthesis (AccessScope::buildDocumentScopeFilter) ---\n";
    printf("%-18s | %10s | %10s | %10s | %10s | %12s\n", "Role", "Mean (µs)", "p50 (µs)", "p95 (µs)", "p99 (µs)", "Throughput (ops/s)");
    echo str_repeat("-", 85) . "\n";
    foreach ($results as $role => $data) {
        $s = $data['sql_doc_scope'];
        printf("%-18s | %10.3f | %10.3f | %10.3f | %10.3f | %12s\n", $role, $s['mean_us'], $s['p50_us'], $s['p95_us'], $s['p99_us'], number_format($s['ops_sec']));
    }

    echo "\n--- STAGE 2: Object-Level In-Memory Boundary Check (AccessScope::isDocumentInScope) ---\n";
    printf("%-18s | %10s | %10s | %10s | %10s | %12s\n", "Role", "Mean (µs)", "p50 (µs)", "p95 (µs)", "p99 (µs)", "Throughput (ops/s)");
    echo str_repeat("-", 85) . "\n";
    foreach ($results as $role => $data) {
        $s = $data['object_scope'];
        printf("%-18s | %10.3f | %10.3f | %10.3f | %10.3f | %12s\n", $role, $s['mean_us'], $s['p50_us'], $s['p95_us'], $s['p99_us'], number_format($s['ops_sec']));
    }

    echo "\n--- FULL TWO-STAGE PIPELINE: RBAC Route + SQL Predicate + Object Scoping ---\n";
    printf("%-18s | %10s | %10s | %10s | %10s | %12s\n", "Role", "Mean (µs)", "p50 (µs)", "p95 (µs)", "p99 (µs)", "Throughput (ops/s)");
    echo str_repeat("-", 85) . "\n";
    foreach ($results as $role => $data) {
        $s = $data['full_pipeline'];
        printf("%-18s | %10.3f | %10.3f | %10.3f | %10.3f | %12s\n", $role, $s['mean_us'], $s['p50_us'], $s['p95_us'], $s['p99_us'], number_format($s['ops_sec']));
    }

    echo "\nBenchmark completed successfully.\n";
}
