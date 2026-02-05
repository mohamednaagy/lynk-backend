<?php

namespace App\Console\Commands;

use App\Services\Health\HealthCheckService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check
        {--only= : Comma-separated list of check names to run}
        {--json : Output results as JSON}';

    protected $description = 'Run application health checks';

    public function handle(HealthCheckService $service): int
    {
        $only = $this->option('only')
            ? array_map('trim', explode(',', $this->option('only')))
            : null;

        $results = $service->runChecks($only);

        $hasUnhealthy = false;

        foreach ($results as $result) {
            if (! $result->isHealthy() && $result->status !== 'skipped') {
                $hasUnhealthy = true;
                break;
            }
        }

        if ($this->output->getVerbosity() <= OutputInterface::VERBOSITY_QUIET) {
            return $hasUnhealthy ? self::FAILURE : self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->line(json_encode($this->buildJsonOutput($results, $hasUnhealthy), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $hasUnhealthy ? self::FAILURE : self::SUCCESS;
        }

        $rows = [];
        foreach ($results as $result) {
            $status = match ($result->status) {
                'healthy' => '<fg=green>healthy</>',
                'unhealthy' => '<fg=red>unhealthy</>',
                'skipped' => '<fg=yellow>skipped</>',
                default => $result->status,
            };

            $meta = $result->meta;
            $detail = $meta['error'] ?? ($meta['reason'] ?? '');

            $rows[] = [
                $result->name,
                $status,
                $result->durationMs.'ms',
                $detail,
            ];
        }

        $this->table(
            ['Check', 'Status', 'Duration', 'Detail'],
            $rows,
        );

        $overall = $hasUnhealthy ? '<fg=red>UNHEALTHY</>' : '<fg=green>HEALTHY</>';
        $this->newLine();
        $this->line("Overall: $overall");

        return $hasUnhealthy ? self::FAILURE : self::SUCCESS;
    }

    private function buildJsonOutput(array $results, bool $hasUnhealthy): array
    {
        $checks = [];
        $totalDuration = 0.0;

        foreach ($results as $result) {
            $checks[$result->name] = $result->toArray();
            $totalDuration += $result->durationMs;
        }

        return [
            'status' => $hasUnhealthy ? 'unhealthy' : 'healthy',
            'timestamp' => now()->toIso8601String(),
            'response_time_ms' => round($totalDuration, 2),
            'checks' => $checks,
        ];
    }
}
