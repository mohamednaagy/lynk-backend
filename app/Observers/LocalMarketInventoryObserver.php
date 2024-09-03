<?php

namespace App\Observers;

use App\Enums\LocalMarket\InventoryStatus;
use App\Jobs\LocalMarket\UpdateInventoryStock;
use App\Models\LocalMarketInventory;
use Illuminate\Support\Facades\DB;

class LocalMarketInventoryObserver
{
    public $afterCommit = true;

    public function creating(LocalMarketInventory $inventory)
    {
        $inventory->status = InventoryStatus::Active();
    }

    /**
     * Handle the LocalMarketInventory "created" event.
     *
     * @return void
     */
    public function created(LocalMarketInventory $inventory)
    {
        UpdateInventoryStock::dispatch($inventory, $inventory->available_quantity, $inventory->wasRecentlyCreated);
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     *
     * @return void
     */
    public function updating(LocalMarketInventory $inventory)
    {

    }

    public function createItemUnits(LocalMarketInventory $inventory)
    {
        DB::select('CALL GenerateRandomInventoryUnitsQRCode(?, ?, ?,?)', [$inventory->id, $inventory->commodity_item_id, $inventory->available_quantity, $inventory->company_id]);
    }
}
