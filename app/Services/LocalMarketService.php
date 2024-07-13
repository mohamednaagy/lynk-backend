<?php

namespace App\Services;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use DragonCode\Support\Facades\Helpers\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocalMarketService
{
    public function getInventory($preferredItemTypes, $amount, $usedInventories)
    {
        Log::info($usedInventories);
        $preferredItemTypesSql = DB::raw("'" . implode("','", $preferredItemTypes) . "'");
        $usedInventoriesSql = $usedInventories ? DB::raw("'" . implode("','", $usedInventories) . "'") : null;
        $whereClause = $usedInventoriesSql ? " AND `id` NOT IN ($usedInventoriesSql)" : '';

        $inventory = DB::select(
            "SELECT * FROM `local_market_inventories`
                WHERE `commodity_type_id` IN ($preferredItemTypesSql)
                $whereClause
                AND `max_price` <= ?
                AND `status` = ?
                ORDER BY (`available_quantity` * `max_price`) DESC
                LIMIT 1",
            [$amount, LocalMarketInventoryStatus::Active]
        );
        Log::info($inventory);
        return $inventory[0] ?? null;
    }

    public function getSuitableUnits($companyId, $inventory, $loan, $usedUnits, $rotations = 0)
    {
        $needToCheckOwnership = $this->ifCompanyBoughtFromInventoryBefore($companyId, $inventory->id);

        $numberOfNeededUnits = floor($loan / $inventory->max_price);
        $whereClause = '';

        if ($usedUnits) {
            $usedUnitsSql = DB::raw("'" . implode("','", Arr::flatten($usedUnits)) . "'");
            $whereClause = " AND `units.id` NOT IN ($usedUnitsSql)";
        }
        $availableUnits = $needToCheckOwnership
            ? $this->getUnitsWithOwnershipCheck($whereClause, $companyId, $inventory, $rotations, $numberOfNeededUnits)
            : $this->getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits, $usedUnits);
        $totalAvailableUnitsCost = count($availableUnits) * $inventory->max_price;
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'availableUnits' => $availableUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
        ];
    }

    public function getUnitsWithOwnershipCheck($whereClause, $companyId, $inventory, $rotations, $numberOfNeededUnits)
    {
        return DB::select(
            "SELECT 
                units.*, 
                ownership.id AS ownership_id, 
                ownership.owner_id, 
                rotations.id AS rotation_id, 
                rotations.is_need_rotations_update, 
                rotations.number_of_rotations 
                FROM local_market_inventory_units units
                LEFT JOIN local_market_unit_ownership ownership ON ownership.inventory_unit_id = units.id 
                AND ownership.owner_id = ?
                LEFT JOIN local_market_unit_rotations rotations ON rotations.inventory_unit_id = units.id
                WHERE units.local_market_inventory_id = ?
                AND units.status = ?
                $whereClause
                AND (rotations.id IS NULL OR (rotations.is_need_rotations_update = 1 AND rotations.number_of_rotations > ?))
                ORDER BY ownership.id DESC
                LIMIT ?",
            [$companyId, $inventory->id, (int) LocalMarketInventoryUnitsStatus::Free, $rotations, $numberOfNeededUnits]
        );
    }

    public function getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits, $usedUnits)
    {
        return DB::table('local_market_inventory_units')
            ->whereNotIn('id', Arr::flatten($usedUnits))
            ->where('status', (int) LocalMarketInventoryUnitsStatus::Free)
            ->where('local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->get()->toArray();
    }

    public function checkUnitsOwnership($companyId, $inventoryId, $rotations = 0)
    {
        return $rotations > 0 && $this->ifCompanyBoughtFromInventoryBefore($companyId, $inventoryId);
    }

    public function ifCompanyBoughtFromInventoryBefore($companyId, $inventoryId)
    {
        $result = DB::select(
            "SELECT orders.*, inventories.*
                FROM local_market_orders orders
                LEFT JOIN local_market_order_has_inventories inventories
                ON orders.id = inventories.local_market_order_id
                WHERE orders.company_id = ?
                AND inventories.id = ?
                LIMIT 1",
            [$companyId, $inventoryId]
        );
        return !empty($result);
    }

    public function updateInventoryUnitsStatus($unitsSql)
    {
        DB::update(
            "UPDATE local_market_inventory_units
             SET status = ?
             WHERE `id` IN ($unitsSql)",
            [LocalMarketInventoryUnitsStatus::Reserved]
        );
    }

    public function createOrder($traderOrder, $financialOrder, $preferredTypes, $companyId)
    {
        return LocalMarketOrder::create([
            'source' => 'LYNK',
            'trader_order_id' => $traderOrder->id,
            'amount' => $financialOrder->amount->convertAndFormatByDecimal(),
            'national_id' => $financialOrder->national_id,
            'customer_name' => $financialOrder->customer_name,
            'preferred_commodity_type' => json_encode($preferredTypes),
            'company_id' => $companyId,
            'comment' => null,
            'status' => LocalMarketOrderStatus::InProgress,
        ]);
    }

    public function findCommodityItem($commodityItemId)
    {
        return DB::table('commodity_items')->find($commodityItemId);
    }

    public function createOrderInventory($orderId, $inventory, $item)
    {
        return LocalMarketOrderHasInventory::create([
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
        ]);
    }

    public function insertOrderUnits($units, $inventoryId)
    {
        $chunks = array_chunk($units, 3000);
        foreach ($chunks as $chunk) {
            $insertData = [];
            foreach ($chunk as $unit) {
                //for bulk insert
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

    public function updateInventoryUnitCounts($inventory, $unitCount)
    {
        DB::table('local_market_inventories')
            ->where('id', $inventory->id)
            ->update([
                'reserved_items' => $unitCount,
                'available_quantity' => DB::raw('available_quantity - ' . $unitCount),
            ]);
    }

    public function updateOwnership($inventory, $companyId)
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
                'owner_type' => $company->type,
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
