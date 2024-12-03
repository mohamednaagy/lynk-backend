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

class DeleteSupplierFromLiveMarket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $supplier;

    protected LiveMarketService $liveMarketService;

    /**
     * Create a new job instance.
     *
     * @param  LiveMarketService  $liveMarketService
     */
    public function __construct(Company $supplier)
    {
        $this->supplier = $supplier;
        $this->liveMarketService = new LiveMarketService;
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->logStartDeletion();

            $this->liveMarketService->handleCompanyRemoval($this->supplier);

            $this->logSuccessfulDeletion();
        } catch (\Exception $e) {
            $this->handleError($e);
            throw $e;
        }
    }

    /**
     * Log the start of deletion process
     */
    private function logStartDeletion(): void
    {
        Log::channel('live_market')->info('Starting to delete supplier from live market', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
        ]);
    }

    /**
     * Log successful deletion
     */
    private function logSuccessfulDeletion(): void
    {
        Log::channel('live_market')->info('Successfully deleted supplier from live market', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
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
        Log::channel('live_market')->error('Failed to delete supplier from live market', [
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
