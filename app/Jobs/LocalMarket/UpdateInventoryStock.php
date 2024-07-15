<?php

namespace App\Jobs;

use App\Models\LocalMarketInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateInventoryStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;

    protected $total;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LocalMarketInventory $inventory, $total)
    {
        $this->inventory = $inventory;
        $this->total = $total;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 1- Start Transaction
        // 2- disable inventory => pending
        // 3- increase / decrease units
        // 4- recalculate free,total inventory
        // 5- enable inventory => active
        // 6- commit transaction or ROllback
        // 7- add more logs

        $newTotalItems = $this->total_items;

        if ($newTotalItems > $originalTotalItems) {
            $newUnits = $newTotalItems - $originalTotalItems;
            app(InventoryItemUnitsService::class)->createItemUnits($inventory, $newUnits);
        } elseif ($newTotalItems < $originalTotalItems) {
            $unitsToRemove = $originalTotalItems - $newTotalItems;
            if ($unitsToRemove > 0) {
                app(InventoryItemUnitsService::class)->decreaseItemUnits($inventory, $unitsToRemove);
            }
        }
    }
}
