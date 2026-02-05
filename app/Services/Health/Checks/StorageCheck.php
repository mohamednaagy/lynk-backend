<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StorageCheck implements HealthCheck
{
    public function name(): string
    {
        return 'storage';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        try {
            $disk = config('filesystems.default');
            $file = 'health_check_'.uniqid().'.txt';

            Storage::disk($disk)->put($file, 'ok');

            if (! Storage::disk($disk)->exists($file)) {
                throw new RuntimeException('Storage write failed');
            }

            Storage::disk($disk)->delete($file);

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: ['disk' => $disk],
            );
        } catch (Throwable $e) {
            return new CheckResult(
                name: $this->name(),
                status: 'unhealthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: [
                    'disk' => config('filesystems.default'),
                    'error' => $e->getMessage(),
                ],
            );
        }
    }
}
