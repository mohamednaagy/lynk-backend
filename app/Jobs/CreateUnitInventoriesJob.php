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
        $baseName = $this->inventory->generateQrCodeBaseName();
        for ($j = 0; $j < $this->chunkSize; $j++) {
            $uuid = Uuid::uuid4()->toString();
            $inventoryUnits[] = [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $baseName . '-' . $uuid ,
            ];
        }

        InventoryUnits::insert($inventoryUnits);

        if ($this->is_last_chunk) {
            $totalUnitsCreated = $this->inventory->CountOfUnits();
            $availableQuantity = $this->inventory->available_quantity;

            if ($totalUnitsCreated < $availableQuantity) {
                $missingUnits = $availableQuantity - $totalUnitsCreated;
                CreateUnitInventoriesJob::dispatch($this->inventory, $missingUnits, true)->onQueue('unit-inventory');
            }

            // Update the inventory status to active if the unit count matches
            if ($this->inventory->CountOfUnits() == $this->inventory->available_quantity) {
                $this->inventory->update([
                    'status' => InventoryStatus::Active,
                ]);
            }
        }

        unset($inventoryUnits);
    }
}
