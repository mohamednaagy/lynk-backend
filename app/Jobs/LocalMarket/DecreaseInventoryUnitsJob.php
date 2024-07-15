<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DecreaseInventoryUnitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;
    protected $numberOfUnits;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LocalMarketInventory $inventory, $numberOfUnits)
    {
        $this->inventory = $inventory;
        $this->numberOfUnits = $numberOfUnits;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Fetch IDs of units to be deleted
        $ids = LocalMarketInventoryUnits::select('id')
            ->where('local_market_inventory_id', $this->inventory->id)
            ->where('status', (int) LocalMarketInventoryUnitsStatus::Free)
            ->limit($this->numberOfUnits)
            ->pluck('id');

        // Delete the fetched rows by IDs
        LocalMarketInventoryUnits::whereIn('id', $ids)->delete();
    }
}
