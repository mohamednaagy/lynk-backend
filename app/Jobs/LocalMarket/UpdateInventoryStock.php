<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Exceptions\ErrorCreatingUnitsForThisINventory;
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
            $this->inventory->update(['status' => LocalMarketInventoryStatus::Pending]);
            Log::info("Starting transaction for updating inventory ID: {$this->inventory->id}");

            DB::beginTransaction();
            if ($this->inventoryWasRecentlyCreated) {
                $this->createItemUnits($this->inventory, $this->inventory->available_quantity);
            } else {
                if ($this->total > $this->inventory->total_items) {
                    $this->createItemUnits($this->inventory, $this->total - $this->inventory->total_items);
                } elseif ($this->total < $this->inventory->total_items) {
                    $this->decreaseItemUnits($this->inventory, $this->inventory->total_items - $this->total);
                }
            }
            DB::commit();

            // Enable inventory (set status to active)
            $this->inventory->update([
                'status' => LocalMarketInventoryStatus::Active,
                'available_quantity' => $this->total - $this->inventory->reserved_items,
            ]);
            Log::info("Set inventory ID: {$this->inventory->id} to status active");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->inventory->update([
                'status' => LocalMarketInventoryStatus::Problem,
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
                3,  // TODO aadel double call enum when merge with dev branch
                LocalMarketInventoryUnitsStatus::Free,
                $inventory->generateQrCodeBaseName(),
            ]);
        } catch (\Exception $e) {
            throw new ErrorCreatingUnitsForThisINventory;
        }
    }

    public function decreaseItemUnits(LocalMarketInventory $inventory, $decreased_amount)
    {
        Log::info("Decreasing units by: {$decreased_amount}");

        try {
            DB::select('CALL DeleteLocalMarketInventoryUnits(?, ? , ?)', [$inventory->id, LocalMarketInventoryUnitsStatus::Free, $decreased_amount]);
        } catch (\Exception $e) {
            throw new FailedDecreaseUnitsForInventory;
        }
    }
}
