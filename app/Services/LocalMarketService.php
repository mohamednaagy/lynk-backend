<?php

namespace App\Services;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Enums\Trader;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use App\Settings\Classes\LocalMurabahaSettings;
use Illuminate\Support\Facades\DB;

class LocalMarketService
{
    /**
     * Retrieves an inventory item from the local market based on the loan amount, preferred item types, and used inventories.
     *
     * @param  int  $loanAmount  The loan amount.
     * @param  array  $preferredItemTypes  The preferred item types.
     * @param  array  $usedInventories  The used inventories.
     * @return LocalMarketInventory|null The retrieved inventory item, or null if not found.
     */
    public function getInventory($loanAmount, $preferredItemTypes = [], $usedInventories = [])
    {
        return LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where(function ($query) use ($preferredItemTypes) {
                if (! empty($preferredItemTypes)) {
                    $query->whereIn('commodity_type_id', $preferredItemTypes)
                        ->orWhere(function ($query) use ($preferredItemTypes) {
                            $query->whereNotIn('commodity_type_id', $preferredItemTypes);
                        });
                }
            })
            ->where('status', LocalMarketInventoryStatus::Active)
            ->when(! empty($usedInventories), function ($query) use ($usedInventories) {
                return $query->whereNotIn('id', $usedInventories);
            })
            ->when(! empty($preferredItemTypes), function ($query) use ($preferredItemTypes) {
                return $query->orderByRaw('CASE WHEN `commodity_type_id` IN ('.implode(',', $preferredItemTypes).') THEN 0 ELSE 1 END');
            })
            ->orderByRaw('(`available_quantity` * `max_price`) DESC')
            ->first();
    }

    public function getSuitableUnitsFromInventory($companyId, LocalMarketInventory $inventory, $loan)
    {
        $numberOfNeededUnits = floor($loan / $inventory->price());

        $availableUnits = $this->extractValidUnitsForCompany($inventory, $companyId, $numberOfNeededUnits);

        $totalAvailableUnitsCost = count($availableUnits) * $inventory->price();
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'inventoryId' => $inventory->id,
            'numberOfNeededUnits' => $numberOfNeededUnits,
            'numberOfSuitableUnits' => count($availableUnits),
            'availableUnits' => $availableUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
            'isLoanCovered' => ($remainingLoan == 0),
        ];
    }

    private function extractValidUnitsForCompany($inventory, $companyId, $numberOfNeededUnits)
    {
        if ($inventory->hasCompanyBoughtFromInventory($companyId)) {
            return $this->getUnitsWithOwnershipCheck($inventory, $numberOfNeededUnits, $companyId);
        } else {
            return $this->getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits);
        }
    }

    private function getUnitsWithOwnershipCheck($inventory, $numberOfNeededUnits, $companyId)
    {
        $number_of_rotations = app(LocalMurabahaSettings::class)->default_trade_order_roatation_count ?? 0;

        return LocalMarketInventoryUnits::join('local_market_unit_rotations', 'local_market_unit_rotations.inventory_unit_id', '=', 'local_market_inventory_units.id')
            ->where('local_market_inventory_units.status', LocalMarketInventoryUnitsStatus::Free)
            ->where('local_market_inventory_units.local_market_inventory_id', $inventory->id)
            ->where('local_market_unit_rotations.company_id', $companyId)
            ->where('local_market_unit_rotations.number_of_rotations', '>=', $number_of_rotations)
            ->limit($numberOfNeededUnits)
            ->get()
            ->toArray();
    }

    private function getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits)
    {
        return LocalMarketInventoryUnits::where('status', LocalMarketInventoryUnitsStatus::Free)
            ->where('local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->get()
            ->toArray();
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
            'source' => Trader::Lynk,
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
