<?php

namespace App\Jobs;

use App\Enums\InventoryStatus;
use App\Models\Inventory;
use App\Models\InventoryUnits;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
        // Log::alert('xxxx');
        // echo 'xxxxx';
        $numberOfUnits = $this->inventory->available_units/$this->volumeSellableUnits;
        // $chunkSize = 1000;
        // $totalChunks = ceil($numberOfUnits / $chunkSize);
        // Log::info('starting chunk size');
        // for ($i = 0; $i < $totalChunks; $i++) {
        //     $start = $i * $chunkSize;
        //     $end = min(($i + 1) * $chunkSize, $numberOfUnits);
        //     $inventoryUnits = [];

        //     for ($j = $start; $j < $end; $j++) {
        //         $qrCode = $this->type . $this->itemId . str_pad(mt_rand(10000, 99999), 6, '0', STR_PAD_LEFT);
        //         Log::info($qrCode);
        //         $inventoryUnits[] = [
        //             'inventory_id' => $this->inventory->id,
        //             'commodity_item_id' => $this->inventory->item->id,
        //             'qr_code' => $qrCode,
        //         ];
        //     }
        //     Log::error($inventoryUnits);
        //     InventoryUnits::insert($inventoryUnits);
        // }

        $this->inventory->update([
            'status' => InventoryStatus::Active,
        ]);

        $data = [];
        for ($i = 0; $i < $numberOfUnits; $i++) {
            $qrCode =  $qrCode = $this->type . $this->itemId . str_pad(mt_rand(10000, 99999), 6, '0', STR_PAD_LEFT);
            $data [] = [
                'inventory_id' => $this->inventory->id,
                'commodity_item_id' => $this->inventory->item->id,
                'qr_code' => $qrCode,
            ];
        }
        DB::transaction(function() use ($data){
            try {
        
                InventoryUnits::insert($data);
                $this->inventory->update([
                    'status' => 'active',
                ]);
        
            }
            catch(Exception $e) {
                return $e;
            }
        
        });
    }
}
