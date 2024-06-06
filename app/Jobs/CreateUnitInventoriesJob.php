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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Inventory $inventory, $type, $itemId, $volumeSellableUnits)
    {
        $this->inventory = $inventory;
        $this->type = $type;
        $this->itemId = $itemId;
        $this->volumeSellableUnits = $volumeSellableUnits;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //        DB::transaction(function(){
        //             try {
        //        Log::channel('daily')->info(['sss inventory : '.$this->inventory , ' -    -  qunatity => ' . $this->inventory->available_quantity]);
        //
        ini_set('memory_limit', '-1');
        $numberOfUnits = $this->inventory->available_quantity;
        $chunkSize = 10000;
        $totalChunks = ceil($numberOfUnits / $chunkSize); // Use ceil to ensure covering all units
        Log::info('total of chunks : '.$totalChunks);
        for ($i = 0; $i < $totalChunks; $i++) {
            $start = $i * $chunkSize;
            $end = min(($i + 1) * $chunkSize, $numberOfUnits);
            $inventoryUnits = [];
            for ($j = $start; $j < $end; $j++) {
                $qrCode = $this->type.$this->itemId.str_pad(mt_rand(10000, 99999), 6, '0', STR_PAD_LEFT);

                $inventoryUnits[] = [
                    'inventory_id' => $this->inventory->id,
                    'commodity_item_id' => $this->inventory->item->id,
                    'qr_code' => $qrCode,
                ];
            }

            Log::info('Processing chunk '.$i.' from '.$start.' to '.$end);
            InventoryUnits::insert($inventoryUnits);
            unset($inventoryUnits);

        }
        $this->inventory->update([
            'status' => InventoryStatus::Active,
        ]);

    }
}
