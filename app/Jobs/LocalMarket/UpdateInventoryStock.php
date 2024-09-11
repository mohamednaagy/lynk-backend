<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Exceptions\ErrorCreatingUnitsForThisInventory;
use App\Exceptions\FailedDecreaseUnitsForInventory;
use App\Models\LocalMarketInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateInventoryStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected LocalMarketInventory $inventory, protected $total, protected $inventoryWasRecentlyCreated = false) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try {

            Log::info("Starting transaction for updating inventory ID: {$this->inventory->id}");
            $this->inventory->update(['status' => InventoryStatus::Pending]);

            if ($this->inventoryWasRecentlyCreated) {
                $this->createItemUnits($this->inventory, $this->inventory->available_quantity);
            } else {
                if ($this->total > $this->inventory->total_items) {
                    $this->createItemUnits($this->inventory, $this->total - $this->inventory->total_items);
                } elseif ($this->total < $this->inventory->total_items) {
                    $this->decreaseItemUnits($this->inventory, $this->inventory->total_items - $this->total);
                }
            }

            // Enable inventory (set status to active)
            $this->inventory->update([
                'status' => InventoryStatus::Active,
                'available_quantity' => $this->total - $this->inventory->reserved_items,
            ]);
            Log::info("Set inventory ID: {$this->inventory->id} to status active");
        } catch (\Exception $e) {
            $this->inventory->update([
                'status' => InventoryStatus::Problem,
            ]);
            Log::error('Error in transaction: '.$e->getMessage());
        }
    }

    public function createItemUnits(LocalMarketInventory $inventory, $numberOfUnits)
    {
        Log::info("Increasing units by: {$numberOfUnits} for inventory ID: {$inventory->id}");
        try {
            DB::select('CALL GenerateRandomInventoryUnitsQRCode(?, ? , ?, ?, ?, ?, ?)', [
                $inventory->id,
                $inventory->commodity_item_id,
                $numberOfUnits,
                $inventory->company_id,
                OwnershipTypes::OriginalSupplier,  // TODO aadel double call enum when merge with dev branch
                InventoryUnitsStatus::Free,
                $inventory->generateQrCodeBaseName(),
            ]);
        } catch (\Exception $e) {
            throw new ErrorCreatingUnitsForThisInventory;
        }
    }

    public function decreaseItemUnits(LocalMarketInventory $inventory, $decreased_amount)
    {
        Log::info("Decreasing units by: {$decreased_amount}");
        try {
            DB::select('CALL DeleteLocalMarketInventoryUnits(?, ? , ?)', [$inventory->id, InventoryUnitsStatus::Free, $decreased_amount]);
        } catch (\Exception $e) {
            throw new FailedDecreaseUnitsForInventory;
        }
    }
}
