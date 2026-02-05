<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;

class ConfigCheck implements HealthCheck
{
    public function name(): string
    {
        return 'config';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        $required = [
            'APP_KEY' => config('app.key'),
            'APP_ENV' => config('app.env'),
            'DB_HOST' => config('database.connections.mysql.host'),
        ];

        $missing = [];

        foreach ($required as $key => $value) {
            if (empty($value)) {
                $missing[] = $key;
            }
        }

        if (! empty($missing)) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['missing' => $missing],
            );
        }

        return new CheckResult(
            name: $this->name(),
            status: 'healthy',
            durationMs: round((microtime(true) - $start) * 1000, 2),
        );
    }
}
