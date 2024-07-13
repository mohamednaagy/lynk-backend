<?php

namespace App\Services;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use Illuminate\Support\Facades\DB;

class LocalMarketService
{
    /**
     * Get the suitable inventory based on loan amount, preferred item types, and used inventories.
     *
     * @param  float  $loanAmount
     * @param  array  $preferredItemTypes
     * @param  array  $usedInventories
     * @return LocalMarketInventory|null
     */
    public function getInventory($loanAmount, $preferredItemTypes = [], $usedInventories = [])
    {
        return LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->when(! empty($preferredItemTypes), function ($query) use ($preferredItemTypes) {
                return $query->whereIn('commodity_type_id', $preferredItemTypes);
            })
            ->where('status', LocalMarketInventoryStatus::Active)
            ->when(! empty($usedInventories), function ($query) use ($usedInventories) {
                return $query->whereNotIn('id', $usedInventories);
            })
            ->orderByRaw('(`available_quantity` * `max_price`) DESC')
            ->first();
    }

    public function getSuitableUnitsFromInventory($companyId, LocalMarketInventory $inventory, $loan)
    {
        $numberOfNeededUnits = floor($loan / $inventory->price());

        dd($inventory->hasCompanyBoughtFromInventory($companyId), $inventory->id, $companyId);
        if ($inventory->hasCompanyBoughtFromInventory($companyId)) { // check for rotations
            $rotations = 5;
            $availableUnits = $this->getUnitsWithOwnershipCheck($inventory, $numberOfNeededUnits, $companyId, $rotations);
        } else {
            $availableUnits = $this->getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits);
        }

        $totalAvailableUnitsCost = count($availableUnits) * $inventory->price();
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'availableUnits' => $availableUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
        ];
    }

    private function getUnitsWithOwnershipCheck($inventory, $numberOfNeededUnits, $companyId, $rotations)
    {
        return LocalMarketInventoryUnits::join('local_market_unit_ownership', 'local_market_unit_ownership.inventory_unit_id', '=', 'local_market_inventory_units.id')
            ->where('local_market_unit_ownership.owner_id', $companyId)
            ->where('local_market_inventory_units.local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->get();
    }

    private function getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits)
    {
        return LocalMarketInventoryUnits::where('status', LocalMarketInventoryUnitsStatus::Free)
            ->where('local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->get();
    }

    public function checkUnitsOwnership($companyId, $inventoryId, $rotations = 0)
    {
        return $rotations > 0 && $inventory->hasCompanyBoughtFromInventory($companyId);
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

    public function createOrder($financialOrder, $preferredTypes, $companyId)
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

    public function findCommodityItem($commodityItemId)
    {
        return DB::table('commodity_items')->find($commodityItemId);
    }

    public function createOrderInventory($orderId, $inventory, $item)
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

    public function insertOrderUnits($units, $inventoryId)
    {
        $chunks = array_chunk($units->toArray(), 3000);
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

    public function updateInventoryUnitCounts($inventory, $unitCount)
    {
        DB::table('local_market_inventories')
            ->where('id', $inventory->id)
            ->update([
                'reserved_items' => $unitCount,
                'available_quantity' => DB::raw('available_quantity - '.$unitCount),
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
