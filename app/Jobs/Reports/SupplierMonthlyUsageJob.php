<?php

namespace App\Jobs\Reports;

use App\Jobs\Reports\Dto\ReportMessage;
use App\Jobs\Reports\Dto\SupplierMonthlyUsageMessage;
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

        Queue::connection('rabbitmq')->pushRaw(
            json_encode($this->buildMessage()->toArray()),
            $queueName
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("SupplierMonthlyUsageJob failed after all retries - supplier id {$this->modelId}", [
            'model_id' => $this->modelId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'queue' => config('services.rabbitmq.queue_name'),
            'exception' => $exception?->getMessage(),
            'exception_trace' => $exception?->getTraceAsString(),
        ]);
    }

    private function buildMessage(): ReportMessage
    {
        return new SupplierMonthlyUsageMessage(
            modelId: $this->modelId,
            startDate: $this->startDate,
            endDate: $this->endDate,
        );
    }
}
