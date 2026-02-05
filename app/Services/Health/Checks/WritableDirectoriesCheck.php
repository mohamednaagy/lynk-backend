<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;

class WritableDirectoriesCheck implements HealthCheck
{
    public function name(): string
    {
        return 'writable_directories';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        $directories = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'storage/logs' => storage_path('logs'),
        ];

        $unwritable = [];

        foreach ($directories as $label => $path) {
            if (! is_writable($path)) {
                $unwritable[] = $label;
            }
        }

        if (! empty($unwritable)) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['unwritable' => $unwritable],
            );
        }

        return new CheckResult(
            name: $this->name(),
            status: 'healthy',
            durationMs: round((microtime(true) - $start) * 1000, 2),
        );
    }
}
