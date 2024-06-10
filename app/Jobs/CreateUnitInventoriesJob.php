<?php

namespace App\Jobs;

use App\Enums\InventoryStatus;
use App\Models\Inventory;
use App\Models\InventoryUnits;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class CreateUnitInventoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;

    protected $chunkSize;

    protected $is_last_chunk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Inventory $inventory, $chunkSize, $is_last_chunk = false)
    {
        $this->inventory = $inventory;
        $this->chunkSize = $chunkSize;
        $this->is_last_chunk = $is_last_chunk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $inventoryUnits = [];
        $baseName = $this->inventory->generateQrCode();
        for ($j = 0; $j < $this->chunkSize; $j++) {
            $randomNumber = str_pad(mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            $uuid = Uuid::uuid4()->toString();
            $time = time();
            $inventoryUnits[] = [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $baseName . '-' . $randomNumber . '-' . $uuid . '-' . $time,
            ];
        }

        InventoryUnits::insert($inventoryUnits);

        if ($this->is_last_chunk) {
            $totalUnitsCreated = $this->inventory->CountOfUnits();
            $availableQuantity = $this->inventory->available_quantity;

            if ($totalUnitsCreated < $availableQuantity) {
                $missingUnits = $availableQuantity - $totalUnitsCreated;
                $this->createMissingUnits($missingUnits);
            }

            // Update the inventory status to active if the unit count matches
            if ($this->inventory->CountOfUnits() == $this->inventory->available_quantity) {
                $this->inventory->update([
                    'status' => InventoryStatus::Active,
                ]);
            }
        }

        unset($inventoryUnits);
        // if ($this->is_last_chunk && $this->inventory->CountOfUnits() == $this->inventory->available_quantity) {
        //     $this->inventory->update([
        //         'status' => InventoryStatus::Active,
        //     ]);
        // }
    }

    private function createMissingUnits($missingUnits)
    {
        $inventoryUnits = [];
        $baseName = $this->inventory->generateQrCode();
        for ($j = 0; $j < $missingUnits; $j++) {
            $randomNumber = str_pad(mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            $uuid = Uuid::uuid4()->toString();
            $time = time();
            $inventoryUnits[] = [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $baseName . '-' . $randomNumber . '-' . $uuid . '-' . $time,
            ];
        }

        InventoryUnits::insert($inventoryUnits);
    }
}
