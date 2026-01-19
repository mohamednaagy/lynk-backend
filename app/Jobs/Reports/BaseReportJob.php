<?php

declare(strict_types=1);

namespace App\Jobs\Reports;

use App\Jobs\Reports\Dto\ReportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

abstract class BaseReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct()
    {
        $this->onQueue('mq');
    }

    /**
     * Build the message to be sent
     */
    abstract protected function buildMessage(): ReportMessage;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Queue::connection('rabbitmq')->pushRaw(
            json_encode($this->buildMessage()->toArray()),
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $jobName = static::class;
        $jobShortName = class_basename($jobName);

        Log::error("{$jobShortName} failed after all retries", $this->getLogContext($exception));
    }

    /**
     * Get the context for logging job failures
     */
    protected function getLogContext(?Throwable $exception): array
    {
        return [
            'queue' => config('services.rabbitmq.queue_name'),
            'exception' => $exception?->getMessage(),
            'exception_trace' => $exception?->getTraceAsString(),
        ];
    }
}
