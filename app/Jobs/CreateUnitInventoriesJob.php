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

class CreateUnitInventoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventory;

    protected $type;

    protected $itemId;

    protected $volumeSellableUnits;

    protected $startChunk;

    protected $endChunk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Inventory $inventory, $type, $itemId, $volumeSellableUnits, $startChunk, $endChunk)
    {
        $this->inventory = $inventory;
        $this->type = $type;
        $this->itemId = $itemId;
        $this->volumeSellableUnits = $volumeSellableUnits;
        $this->startChunk = $startChunk;
        $this->endChunk = $endChunk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $inventoryUnits = [];           
        for ($j = $this->startChunk; $j < $this->endChunk; $j++) {
            $qrCode = $this->type . $this->itemId . $j . str_pad(mt_rand(1000000, 9999999), 6, '0', STR_PAD_LEFT);

            $inventoryUnits[] = [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $qrCode,
            ];
        }

        InventoryUnits::insert($inventoryUnits);
        unset($inventoryUnits);

        $this->inventory->update([
            'status' => InventoryStatus::Active,
        ]);
    }
}
