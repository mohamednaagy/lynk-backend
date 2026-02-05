<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class NightwatchCheck implements HealthCheck
{
    public function name(): string
    {
        return 'nightwatch';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        if (! config('nightwatch.enabled')) {
            return new CheckResult(
                name: $this->name(),
                status: 'skipped',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['reason' => 'not_enabled'],
            );
        }

        try {
            $exitCode = Artisan::call('nightwatch:status');

            if ($exitCode === 0) {
                return new CheckResult(
                    name: $this->name(),
                    status: 'healthy',
                    durationMs: round((microtime(true) - $start) * 1000, 2),
                    meta: ['message' => 'The Nightwatch agent is running and accepting connections.'],
                );
            }

            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['error' => 'Nightwatch agent is not running or not accepting connections.'],
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
