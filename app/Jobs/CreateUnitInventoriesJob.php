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

    protected $startChunk;

    protected $endChunk;

    protected $is_last_chunk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Inventory $inventory, $startChunk, $endChunk, $is_last_chunk = false)
    {
        $this->inventory = $inventory;
        $this->startChunk = $startChunk;
        $this->endChunk = $endChunk;
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
        for ($j = $this->startChunk; $j < $this->endChunk; $j++) {

            $inventoryUnits[] = [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $this->inventory->generateQrCode(),
            ];
        }

        InventoryUnits::insert($inventoryUnits);
        unset($inventoryUnits);

        if ($this->is_last_chunk) {
            $this->inventory->update([
                'status' => InventoryStatus::Active,
            ]);
        }
    }
}
