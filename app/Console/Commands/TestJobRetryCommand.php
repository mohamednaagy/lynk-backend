<?php

namespace App\Console\Commands;

use App\Jobs\Test\TestRetryJob;
use Illuminate\Console\Command;

class TestJobRetryCommand extends Command
{
    protected $signature = 'test:job-retry {--fail : Should the job fail} {--attempts=1 : Number of attempts before failing}';
    protected $description = 'Test job retry mechanism';

    public function handle()
    {
        $jobId = time();
        $shouldFail = $this->option('fail');
        $failAfterAttempts = (int) $this->option('attempts');

        $this->info("Dispatching test job with ID: {$jobId}");
        $this->info("Should fail: " . ($shouldFail ? 'Yes' : 'No'));
        $this->info("Will fail after {$failAfterAttempts} attempts");

        TestRetryJob::dispatch($jobId, $shouldFail, $failAfterAttempts);

        $this->info('Job dispatched. Check the logs in storage/logs/bursam.log');
        $this->info('To watch the logs: tail -f storage/logs/bursam.log');
    }
} 