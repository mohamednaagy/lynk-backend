<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckService
{
    protected array $checks = [];

    protected string $overallStatus = 'healthy';

    protected float $startTime;

    public function run(): array
    {
        $this->startTime = microtime(true);

        $this->checkDatabase('mysql');
        $this->checkDatabase('wallet');
        $this->checkRedis();
        $this->checkCache();
        $this->checkQueue();
        $this->checkRabbitMQ();
        $this->checkStorage();
        $this->checkApplication();

        return $this->buildResponse();
    }

    protected function checkDatabase(string $connection): void
    {
        try {
            DB::connection($connection)->select('SELECT 1');
            $version = DB::connection($connection)->selectOne('SELECT VERSION() as version');

            $this->checks[$connection.'_database'] = [
                'status' => 'healthy',
                'connection' => $connection,
                'version' => $version->version ?? 'unknown',
            ];
        } catch (Throwable $e) {
            $this->fail(
                $connection.'_database',
                [
                    'connection' => $connection,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    protected function checkRedis(): void
    {
        try {
            $redis = Redis::connection();
            $redis->ping();
            $info = $redis->info();

            $this->checks['redis'] = [
                'status' => 'healthy',
                'version' => $info['redis_version'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
            ];
        } catch (Throwable $e) {
            $this->fail('redis', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkCache(): void
    {
        try {
            $key = 'health_check_'.uniqid();
            Cache::put($key, 'ok', 5);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== 'ok') {
                throw new \RuntimeException('Cache read/write failed');
            }

            $this->checks['cache'] = [
                'status' => 'healthy',
                'driver' => config('cache.default'),
            ];
        } catch (Throwable $e) {
            $this->fail('cache', [
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkQueue(): void
    {
        try {
            $driver = config('queue.default');

            $this->checks['queue'] = [
                'status' => 'healthy',
                'driver' => $driver,
            ];
        } catch (Throwable $e) {
            $this->fail('queue', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkRabbitMQ(): void
    {
        try {
            $config = config('queue.connections.rabbitmq');
            $queueName = config('services.rabbitmq.queue_name');

            // Skip if RabbitMQ is not configured
            if (! $config || empty($config['hosts']) || ! $queueName) {
                $this->checks['rabbitmq'] = [
                    'status' => 'skipped',
                    'reason' => 'not_configured',
                ];

                return;
            }

            // Test RabbitMQ connection using Queue facade (same as SupplierMonthlyUsageJob)
            $connection = Queue::connection('rabbitmq');

            // Get queue size to verify connection works
            $queueSize = $connection->size($queueName);

            $host = $config['hosts'][0];

            $this->checks['rabbitmq'] = [
                'status' => 'healthy',
                'host' => $host['host'],
                'port' => $host['port'],
                'vhost' => $host['vhost'],
                'queue' => $queueName,
                'queue_size' => $queueSize,
            ];
        } catch (Throwable $e) {
            $this->fail('rabbitmq', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkStorage(): void
    {
        try {
            $disk = config('filesystems.default');
            $file = 'health_check_'.uniqid().'.txt';

            Storage::disk($disk)->put($file, 'ok');

            if (! Storage::disk($disk)->exists($file)) {
                throw new \RuntimeException('Storage write failed');
            }

            Storage::disk($disk)->delete($file);

            $this->checks['storage'] = [
                'status' => 'healthy',
                'disk' => $disk,
            ];
        } catch (Throwable $e) {
            $this->fail('storage', [
                'disk' => config('filesystems.default'),
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkApplication(): void
    {
        $this->checks['application'] = [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    protected function fail(string $key, array $data): void
    {
        $this->checks[$key] = array_merge(
            ['status' => 'unhealthy'],
            $data
        );

        $this->overallStatus = 'unhealthy';
    }

    protected function buildResponse(): array
    {
        return [
            'status' => $this->overallStatus,
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => round((microtime(true) - $this->startTime) * 1000, 2),
            'checks' => $this->checks,
        ];
    }
}
