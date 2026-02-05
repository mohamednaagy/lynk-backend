<?php

namespace App\Services\Health\Checks;

use App\Services\Health\CheckResult;
use App\Services\Health\Contracts\HealthCheck;
use Illuminate\Support\Facades\Queue;
use Throwable;

class RabbitMQCheck implements HealthCheck
{
    public function name(): string
    {
        return 'rabbitmq';
    }

    public function run(): CheckResult
    {
        $start = microtime(true);

        try {
            $config = config('queue.connections.rabbitmq');

            if (! $config || empty($config['hosts'])) {
                return new CheckResult(
                    name: $this->name(),
                    status: 'skipped',
                    durationMs: round((microtime(true) - $start) * 1000, 2),
                    meta: ['reason' => 'not_configured'],
                );
            }

            $connection = Queue::connection('rabbitmq');
            $queueSize = $connection->size();

            $host = $config['hosts'][0];

            return new CheckResult(
                name: $this->name(),
                status: 'healthy',
                durationMs: round((microtime(true) - $start) * 1000, 2),
                meta: [
                    'host' => $host['host'],
                    'port' => $host['port'],
                    'vhost' => $host['vhost'],
                    'queue_size' => $queueSize,
                ],
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
