<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\Company;
use App\Services\LocalMarket\LiveMarketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleSupplierStatusChange implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $supplier;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $supplier)
    {
        $this->supplier = $supplier;
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(LiveMarketService $liveMarketService): void
    {
        try {
            $this->logStartProcess();

            $liveMarketService->handleSupplierStatusChange($this->supplier);

            $this->logSuccessfulProcess();
        } catch (\Exception $e) {
            $this->handleError($e);
            throw $e;
        }
    }

    /**
     * Log start of process
     */
    private function logStartProcess(): void
    {
        Log::channel('live_market')->info('Starting to process supplier status change', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'current_status' => $this->supplier->detail?->status->value,
        ]);
    }

    /**
     * Log successful process completion
     */
    private function logSuccessfulProcess(): void
    {
        Log::channel('live_market')->info('Successfully processed supplier status change', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'current_status' => $this->supplier->detail?->status->value,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->handleError($exception);
    }

    /**
     * Handle errors during job execution.
     */
    private function handleError(\Throwable $exception): void
    {
        Log::channel('live_market')->error('Failed to process supplier status change', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'current_status' => $this->supplier->detail?->status->value,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
