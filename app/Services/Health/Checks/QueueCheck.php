<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\Queue;
use Throwable;

class QueueCheck implements HealthCheck
{
    public function name(): string
    {
        return 'queue';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        try {
            $driver = config('queue.default');
            Queue::connection($driver)->size('default');

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['driver' => $driver],
            );
        } catch (Throwable $e) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: [
                    'driver' => config('queue.default'),
                    'error' => $e->getMessage(),
                ],
            );
        }
    }
}
