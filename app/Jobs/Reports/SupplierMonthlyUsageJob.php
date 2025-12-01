<?php

namespace App\Jobs\Reports;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

class SupplierMonthlyUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public int $modelId,
        public string $startDate,
        public string $endDate
    ) {
        $this->onQueue('mq');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $queueName = config('services.rabbitmq.queue_name');

        $message = [
            'type' => 'SUPPLIER_MONTHLY_USAGE',
            'model_type' => Company::class,
            'model_id' => $this->modelId,
            'filters' => [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ],
        ];

        Queue::connection('rabbitmq')->pushRaw(json_encode($message), $queueName);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::channel(LOG_CHANNEL_REPORTS)->error('SupplierMonthlyUsageJob failed after all retries', [
            'model_id' => $this->modelId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'queue' => config('services.rabbitmq.queue_name'),
            'exception' => $exception?->getMessage(),
            'exception_trace' => $exception?->getTraceAsString(),
        ]);
    }
}
