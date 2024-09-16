<?php

namespace App\Jobs\LocalMarket;

use App\Models\LocalMarketInventory;
use App\Services\LocalMarket\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected LocalMarketInventory $inventory)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            InventoryService::deleteInventory($this->inventory);
        } catch (\Exception $e) {
            Log::error("Updated Inventory ID: {$this->inventory->id} status to Problem due to error: {$e->getMessage()}");
        }
       
    }
}
