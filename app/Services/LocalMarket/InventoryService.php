<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    
    public static function deleteInventory($inventory)
    {
        try {
            Log::info("Start Deleting Inventory ID: {$inventory->id}");

            DB::select('CALL DeleteLocalMarketInventoryUnits(?, ? , ?)', [$inventory->id, LocalMarketInventoryUnitsStatus::Free, $inventory->available_quantity]);
            Log::info("Successfully soft deleted units for inventory ID: {$inventory->id}");
            $inventory->delete();

            Log::info("Success for deleting inventory ID: {$inventory->id}");
        } catch (\Exception $e) {
            Log::error("Updated Inventory ID: {$inventory->id} status to Problem due to error: {$e->getMessage()}");
            throw $e;
        }
    }
}
