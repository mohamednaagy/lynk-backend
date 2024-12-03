<?php

namespace App\Jobs\LocalMarket\LiveMarket;

use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\LiveMarketService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishInventoryToLiveMarket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected LocalMarketInventory $inventory;

    /**
     * Create a new job instance.
     */
    public function __construct(LocalMarketInventory $inventory)
    {
        $this->inventory = $inventory;
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(LiveMarketService $liveMarketService): void
    {
        try {
            $this->logStartProcess();

            $liveMarketService->handleNewInventory($this->inventory);

            $this->logSuccessfulProcess();
        } catch (\Exception $e) {
            $this->handleError($e);
            throw $e;
        }
    }

    /**
     * Log the start of the process.
     */
    private function logStartProcess(): void
    {
        Log::channel('live_market')->info('Starting to publish inventory to live market', [
            'inventory_id' => $this->inventory->id,
            'commodity_item_id' => $this->inventory->commodity_item_id,
        ]);
    }

    /**
     * Log successful process completion.
     */
    private function logSuccessfulProcess(): void
    {
        Log::channel('live_market')->info('Successfully published inventory to live market', [
            'inventory_id' => $this->inventory->id,
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
        Log::channel('live_market')->error('Failed to publish inventory to live market', [
            'inventory_id' => $this->inventory->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
