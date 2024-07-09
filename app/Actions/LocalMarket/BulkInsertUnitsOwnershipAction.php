<?php

namespace App\Actions\LocalMarket;

use App\Enums\CompanyType;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Enums\LocalMarketOrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkInsertUnitsOwnershipAction
{
    public function execute($financialOrder, $preferredTypes, $companyId, $units, $usedInventories)
    {
        $unitIds = $this->extractUnitIds($units);
        Log::info($units);
        Log::info($usedInventories);

        $this->associateUnitsWithInventories($usedInventories, $units);

        $unitsSql = implode(',', $unitIds); 
        $orderId = null;

        DB::transaction(function () use ($financialOrder, $companyId, $unitsSql, $preferredTypes, $usedInventories, &$orderId) {
            $this->updateInventoryUnitsStatus($unitsSql);
            $orderId = $this->createOrder($financialOrder, $preferredTypes, $companyId);
            $this->processUsedInventories($usedInventories, $orderId, $companyId);
        });

        return response()->json([
            'local_market_order_id' => $orderId,
            'order' => $financialOrder,
            'products' => $usedInventories,
            'success' => true
        ]);
    }

    protected function extractUnitIds($units)
    {
        return collect($units)->flatten()->pluck('id')->toArray();
    }

    protected function associateUnitsWithInventories(&$usedInventories, $units)
    {
        foreach ($usedInventories as $i => $inventory) {
            $inventory->units = $units[$i];
        }
    }

    protected function updateInventoryUnitsStatus($unitsSql)
    {
        DB::update(
            "UPDATE local_market_inventory_units
             SET status = ?
             WHERE `id` IN ($unitsSql)",
            [LocalMarketInventoryUnitsStatus::Reserved]
        );
    }

    protected function createOrder($financialOrder, $preferredTypes, $companyId)
    {
        return DB::table('local_market_orders')
            ->insertGetId([
                'source' => 'LYNK',
                'amount' => $financialOrder->amount->convertAndFormatByDecimal(),
                'national_id' => $financialOrder->national_id,
                'customer_name' => $financialOrder->customer_name,
                'preferred_commodity_type' => json_encode($preferredTypes),
                'company_id' => $companyId,
                'comment' => null,
                'status' => LocalMarketOrderStatus::InProgress,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function processUsedInventories($usedInventories, $orderId, $companyId)
    {
        foreach ($usedInventories as $inventory) {
            $item = DB::table('commodity_items')->find($inventory->commodity_item_id);
            $inventoryId = $this->createOrderInventory($orderId, $inventory, $item);

            $this->insertOrderUnits($inventory->units, $inventoryId);
            $this->updateInventoryUnitCounts($inventory, count($inventory->units));
            $this->updateOwnership($inventory, $companyId);
        }
    }

    protected function createOrderInventory($orderId, $inventory, $item)
    {
        return DB::table('local_market_order_has_inventories')
            ->insertGetId([
                'local_market_order_id' => $orderId,
                'local_market_inventory_id' => $inventory->id,
                'quantity' => count($inventory->units),
                'price' => $inventory->max_price,
                'measurement_id' => $item->measurement_id,
                'currency_id' => $item->currency_id,
                'location_id' => $inventory->supplier_location_id,
                'supplier_id' => $inventory->company_id,
                'previous_owner' => '',
                'commodity_item_id' => $inventory->commodity_item_id,
                'commodity_type_id' => $inventory->commodity_type_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function insertOrderUnits($units, $inventoryId)
    {
        $chunks = array_chunk($units, 3000);
        foreach ($chunks as $chunk) {
            $insertData = [];
            foreach ($chunk as $unit) {
                $insertData[] = [
                    'inventory_unit_id' => $unit->id,
                    'order_has_inventory_id' => $inventoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('local_market_order_has_units')->insert($insertData);
        }
    }

    protected function updateInventoryUnitCounts($inventory, $unitCount)
    {
        DB::table('local_market_inventories')
            ->where('id', $inventory->id)
            ->update([
                'reserved_items' => $unitCount,
                'available_quantity' => DB::raw('available_quantity - ' . $unitCount),
            ]);
    }

    protected function updateOwnership($inventory, $companyId)
    {
        $ownershipData = [];
        $company = DB::table('companies')->where('id', $companyId)->first();
        foreach ($inventory->units as $unit) {
            $previousOwner = DB::table('local_market_unit_ownership')
                ->where('inventory_unit_id', $unit->id)
                ->latest()
                ->first();

            $ownershipData[] = [
                'inventory_unit_id' => $unit->id,
                'owner_type' => $company->type, // Adjust this based on your needs
                'owner_id' => $companyId,
                'owner_name' => $company->unique_name,
                'previous_owner' => $previousOwner ? $previousOwner->owner_id : $inventory->company_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $chunks = array_chunk($ownershipData, 3000);
        foreach ($chunks as $chunk) {
            DB::table('local_market_unit_ownership')->insert($chunk);
        }
    }
}
