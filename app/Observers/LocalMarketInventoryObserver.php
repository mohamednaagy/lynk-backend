<?php

namespace App\Observers;

use App\Enums\LocalMarket\InventoryStatus;
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
        $this->createItemUnits($inventory);
    }

    /**
     * Handle the LocalMarketInventory "updated" event.
     *
     * @return void
     */
    public function updated(LocalMarketInventory $inventory)
    {
        //TODO: Handle the LocalMarketInventory "updated" event
        //DROP OLD CREATED UNITS FROM LocalMarketInventoryUnits
        //$this->createItemUnits($inventory);
    }

    public function createItemUnits(LocalMarketInventory $inventory)
    {
        DB::select('CALL GenerateRandomQRCodesOptimized(?, ?, ?)', [$inventory->id, $inventory->commodity_item_id, $inventory->available_quantity]);
    }
}
