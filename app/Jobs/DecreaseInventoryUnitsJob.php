<?php

namespace App\Jobs;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\NeedManuallyCheckUnitsAndStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DecreaseInventoryUnitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $inventory;

    protected $chunkSize;

    protected $isLastChunk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(LocalMarketInventory $inventory, $chunkSize, $isLastChunk = false)
    {
        $this->inventory = $inventory;
        $this->chunkSize = $chunkSize;
        $this->isLastChunk = $isLastChunk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Decrease number of units for this inventory
        $ids = LocalMarketInventoryUnits::select('id')->where('local_market_inventory_id', $this->inventory->id)
        ->where('status', (int) LocalMarketInventoryUnitsStatus::Free)
        ->limit($this->chunkSize)
        ->pluck('id');
        // Delete the fetched rows by IDs
        LocalMarketInventoryUnits::whereIn('id', $ids)->delete();

        if ($this->isLastChunk) {
            $totalUnitsAfterDecreased = $this->inventory->CountOfUnits();
            $availableQuantity = $this->inventory->total_items;
            // Update the inventory status to active if the unit count matches
            if ($totalUnitsAfterDecreased == $availableQuantity) {
                $this->inventory->update([
                    'status' => LocalMarketInventoryStatus::Active,
                ]);
            } elseif ($totalUnitsAfterDecreased > $availableQuantity) {
                $missingUnits = $availableQuantity - $totalUnitsAfterDecreased;
                DecreaseInventoryUnitsJob::dispatch($this->inventory, $missingUnits, true)->onQueue('unit-inventory');
            } else {
                // throw exception
                // change inventory status to be having a problem status
                $this->inventory->update([
                    'status' => LocalMarketInventoryStatus::Problem,
                ]);

                throw new NeedManuallyCheckUnitsAndStatus();
            }
        }
    }
}
