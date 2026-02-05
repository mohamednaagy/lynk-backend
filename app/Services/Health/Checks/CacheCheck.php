<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class CacheCheck implements HealthCheck
{
    public function name(): string
    {
        return 'cache';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        try {
            $key = 'health_check_'.uniqid();
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== 'ok') {
                throw new RuntimeException('Cache read/write failed');
            }

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['driver' => config('cache.default')],
            );
        } catch (Throwable $e) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: [
                    'driver' => config('cache.default'),
                    'error' => $e->getMessage(),
                ],
            );
        }
    }
}
