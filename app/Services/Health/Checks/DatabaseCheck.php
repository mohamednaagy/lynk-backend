<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseCheck implements HealthCheck
{
    public function __construct(
        protected string $connection = 'mysql',
    ) {}

    public function name(): string
    {
        return $this->connection.'_database';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        try {
            DB::connection($this->connection)->select('SELECT 1');

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['connection' => $this->connection],
            );
        } catch (Throwable $e) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: [
                    'connection' => $this->connection,
                    'error' => $e->getMessage(),
                ],
            );
        }
    }
}
