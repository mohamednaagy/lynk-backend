<?php

namespace App\Services\Health;

use App\Services\Health\Checks\CacheCheck;
use App\Services\Health\Checks\ConfigCheck;
use App\Services\Health\Checks\DatabaseCheck;
use App\Services\Health\Checks\NightwatchCheck;
use App\Services\Health\Checks\QueueCheck;
use App\Services\Health\Checks\RabbitMQCheck;
use App\Services\Health\Checks\RedisCheck;
use App\Services\Health\Checks\StorageCheck;
use App\Services\Health\Checks\WritableDirectoriesCheck;
use App\Services\Health\Contracts\HealthCheck;

class HealthCheckService
{
    /** @var HealthCheck[] */
    protected array $checks;

    public function __construct()
    {
        $this->checks = [
            new DatabaseCheck('mysql'),
            new DatabaseCheck('wallet'),
            new RedisCheck,
            new CacheCheck,
            new QueueCheck,
            new RabbitMQCheck,
            new StorageCheck,
            new WritableDirectoriesCheck,
            new NightwatchCheck,
            new ConfigCheck,
        ];
    }

    /**
     * @param  string[]|null  $only  Filter to specific check names
     * @return array{status: string, timestamp: string, response_time_ms: float, checks: array}
     */
    public function run(?array $only = null): array
    {
        $startTime = microtime(true);

        $checks = $this->checks;

        if ($only !== null) {
            $checks = array_filter($checks, fn (HealthCheck $check) => in_array($check->name(), $only));
        }

        $results = [];
        $overallStatus = 'healthy';

        foreach ($checks as $check) {
            $result = $check->run();
            $results[$result->name] = $result->toArray();

            if (! $result->isHealthy() && $result->status !== 'skipped') {
                $overallStatus = 'unhealthy';
            }
        }

        return [
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'checks' => $results,
        ];
    }

    /**
     * @return CheckResult[]
     */
    public function runChecks(?array $only = null): array
    {
        $checks = $this->checks;

        if ($only !== null) {
            $checks = array_filter($checks, fn (HealthCheck $check) => in_array($check->name(), $only));
        }

        $results = [];

        foreach ($checks as $check) {
            $results[] = $check->run();
        }

        return $results;
    }
}
