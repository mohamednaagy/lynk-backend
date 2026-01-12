<?php

declare(strict_types=1);

namespace App\Jobs\Reports;

use App\Jobs\Reports\Dto\OrderListMessage;
use App\Jobs\Reports\Dto\ReportMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

class ExportOrderListJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        public int $userId,
        public string $exportType,
        public array $exportData = []
    ) {
        $this->onQueue('mq');
    }

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
        Log::error("ExportOrderListJob failed after all retries - user id {$this->userId}", [
            'user_id' => $this->userId,
            'export_type' => $this->exportType,
            'export_data' => $this->exportData,
            'queue' => config('services.rabbitmq.queue_name'),
            'exception' => $exception?->getMessage(),
            'exception_trace' => $exception?->getTraceAsString(),
        ]);
    }

    private function buildMessage(): ReportMessage
    {
        return new OrderListMessage(
            modelId: $this->userId,
            exportType: $this->exportType,
            exportData: $this->exportData,
        );
    }
}
