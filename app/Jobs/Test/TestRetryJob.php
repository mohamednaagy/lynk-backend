<?php

namespace App\Jobs\Test;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestRetryJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LOG_CHANNEL = 'bursam';

    public function __construct(
        private readonly int $jobId,
        private readonly bool $shouldFail = false,
        private readonly int $failAfterAttempts = 0
    ) {
        $this->onQueue('bursam');
    }

    public function handle(): void
    {
        $currentAttempt = $this->attempts();
        
        Log::channel(self::LOG_CHANNEL)->info('Test job attempt started', [
            'job_id' => $this->jobId,
            'attempt_number' => $currentAttempt,
            'should_fail' => $this->shouldFail,
            'fail_after_attempts' => $this->failAfterAttempts,
            'timestamp' => now()->toIso8601String(),
        ]);

        if ($this->shouldFail && $currentAttempt >= $this->failAfterAttempts) {
            Log::channel(self::LOG_CHANNEL)->error('Test job intentionally failing', [
                'job_id' => $this->jobId,
                'attempt_number' => $currentAttempt,
            ]);
            throw new \Exception("Test job failed on attempt {$currentAttempt}");
        }

        Log::channel(self::LOG_CHANNEL)->info('Test job completed successfully', [
            'job_id' => $this->jobId,
            'attempt_number' => $currentAttempt,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel(self::LOG_CHANNEL)->error('Test job failed', [
            'job_id' => $this->jobId,
            'attempt_number' => $this->attempts(),
            'retry_count' => $this->retries(),
            'error_message' => $exception->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function retryUntil(): Carbon
    {
        return now()->addMinutes(5);
    }

    public function backoff(): array
    {
        return [5, 10, 15]; // Shorter delays for testing
    }

    public function uniqueId(): string
    {
        return __CLASS__ . '_' . $this->jobId;
    }
} 