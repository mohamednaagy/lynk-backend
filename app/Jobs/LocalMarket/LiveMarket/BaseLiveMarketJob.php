<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

abstract class BaseLiveMarketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $maxExceptions = 3;

    protected int $currentAttempt = 1;

    public function __construct()
    {
        $this->onQueue('local_market');
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $baseContext = [
            'job_id' => $this->job?->getJobId(),
            'queue' => $this->queue,
            'attempt' => $this->currentAttempt,
            'max_tries' => $this->tries,
            'job_class' => static::class,
        ];

        Log::channel('live_market')->$level($message, array_merge($baseContext, $context));
    }

    protected function logJobStart(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    protected function logJobSuccess(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    protected function logJobError(string $message, \Throwable $exception, array $additionalContext = []): void
    {
        $errorContext = [
            'error' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->log('error', $message, array_merge($errorContext, $additionalContext));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $context = [
            'failed_at' => now()->toDateTimeString(),
            'total_attempts' => $this->attempts(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 .'MB',
            'time_elapsed' => $this->job?->timeElapsed().'s',
        ];

        if (method_exists($this, 'getFailedJobContext')) {
            $context = array_merge($context, $this->getFailedJobContext());
        }

        $this->logJobError(
            sprintf(
                'Job failed after %d attempts. Max tries: %d',
                $this->attempts(),
                $this->tries
            ),
            $exception,
            $context
        );
    }

    /**
     * Get additional context for failed job logging.
     * Can be implemented by child classes to add specific context.
     */
    protected function getFailedJobContext(): array
    {
        return [];
    }
}
