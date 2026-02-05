<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisCheck implements HealthCheck
{
    public function name(): string
    {
        return 'redis';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        try {
            Redis::connection()->ping();

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
            );
        } catch (Throwable $e) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['error' => $e->getMessage()],
            );
        }
    }
}
