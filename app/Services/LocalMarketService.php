<?php

namespace App\Services;

use App\Enums\LocalMarketInventoryStatus;
use App\Enums\LocalMarketInventoryUnitsStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Null_;

class LocalMarketService
{
    /**
     * @param $companyId
     * @param array $preferredTypes
     * @param $amount
     * @param int $rotations
     * @return bool|null
     */
    public function createInitialOrder($companyId, array $preferredTypes, $amount, int $rotations = 0)
    {
        return $this->reserveSuitableUnits($companyId, $preferredTypes, $amount, $rotations);
    }

    /**
     * @param $companyId
     * @param $preferredTypes
     * @param $loanAmount
     * @param $rotations
     * @param $usedInventories
     * @param $usedUnits
     * @return bool|void
     */
    public function reserveSuitableUnits($companyId, $preferredTypes, $loanAmount, $rotations, array $usedInventories = [], array $usedUnits = [])
    {
        // try to get the best inventory
        // we can enhance to get 5 inventories not one and try with them before call the same function
        $inventory = $this->getInventory($preferredTypes, $loanAmount, $usedInventories);
        if (empty($inventory)) {
            // we can't go with this order there is no suitable inventory
            // the loan is not completed
            //throw new \Exception("NO_SUITABLE_INVENTORIES", 400);
            return false;
        } else {
            // try to get the inventory for preferred types (happy scenario)
            // TODO : refactor this code with form to handle : reserved units , total cost , remaining total
            $suitableUnits = $this->getUnits($companyId, $inventory, $loanAmount, $usedUnits, $rotations);
            $usedUnits[] = $suitableUnits['availableUnits']; // hold the units for future use
            $usedUnitsIDs[] = collect($suitableUnits['availableUnits'])->pluck('id')->toArray();
            if (empty($suitableUnits['remainingLoan'])) { // no need to get another inventory
                $this->bulkInsertUnitsOwnerSHip($companyId, $suitableUnits['availableUnits']);
                return true;
            } else {
                Log::info("remaining" . $suitableUnits['remainingLoan']);
                // there is a need to get another inventory
                // recall the same function to get another inventory
                $usedInventories[] = $inventory->id;
                $this->reserveSuitableUnits($companyId, $preferredTypes, $suitableUnits['remainingLoan'], $rotations, $usedInventories, $usedUnitsIDs);
            }
        }
    }
    // ===========.  First Step getting  inventory  =====================
    public function getInventory($preferredItemTypes, $amount, $usedInventories)
    {
        Log::info("inventory", $usedInventories);
        Log::info("amount" . $amount);

        $preferredItemTypesSql = DB::raw("'" . implode("','", $preferredItemTypes) . "'");
        $usedInventoriesSql = DB::raw("'" . implode("','", $usedInventories) . "'");

        $inventory = DB::select(
            "SELECT * FROM `local_market_inventories`
                WHERE `commodity_type_id` IN ($preferredItemTypesSql)
                AND `id` NOT IN ($usedInventoriesSql)
                AND `max_price` <= ?
                AND `status` = ?
                ORDER BY (`available_quantity` * `max_price`) DESC
                LIMIT 1",
            [$amount, LocalMarketInventoryStatus::Active]
        );
        if (empty($inventory)) {
            $inventory = DB::select(
                "SELECT * FROM `local_market_inventories`
                    WHERE `commodity_type_id` IN ($preferredItemTypesSql)
                    AND `id` NOT IN ($usedInventoriesSql)
                    AND `max_price` <= ?
                    AND `status` = ?
                    ORDER BY (`available_quantity` * `max_price`) DESC
                    limit 1",
                [$preferredItemTypesSql, $usedInventoriesSql, $amount, LocalMarketInventoryStatus::Active]
            );
        }
        return $inventory[0] ?? null;
    }

    //================ second step check ownership ==================
    public function getUnits($companyId, $inventory, $loan, $usedUnits, $rotations = 0)
    {
        $needToCheckOwnership = $this->isNeedCheckUnitsOwnership($companyId, $inventory->id, $rotations);
        return $this->getSuitableUnits($companyId, $inventory, $loan, $needToCheckOwnership, $usedUnits, $rotations);
    }
    public function getSuitableUnits($companyId, $inventory, $loan, $needToCheckOwnerShip = false, $usedUnits, $rotations = 0)
    {
        $numberOfNeededUnits = $loan / $inventory->max_price;
        dd($numberOfNeededUnits);
        $whereClause = '';

        if ($usedUnits) {
            $usedUnitsSql = DB::raw("'" . implode("','", Arr::flatten($usedUnits)) . "'");
            $whereClause = " AND `id` NOT IN ($usedUnitsSql)";
        }

        if ($needToCheckOwnerShip) {
            $availableUnits = DB::select(
                "SELECT * FROM local_market_inventory_units units
                    JOIN unit_ownership ON unit_ownership.unit_id = units.id AND unit_ownership.company_id = ?
                    WHERE units.local_market_inventory_id = ?
                    AND unit_ownership.number_of_rotations >= ?
                    ORDER BY unit_ownership.id DESC
                    LIMIT ?",
                [$companyId, $inventory->id, $rotations, $numberOfNeededUnits]
            );
        } else {
            $availableUnits = DB::select(
                "SELECT * FROM local_market_inventory_units
                    WHERE status = ?
                    $whereClause
                    AND local_market_inventory_id = ?
                    LIMIT ?",
                [LocalMarketInventoryUnitsStatus::Free, $inventory->id, $numberOfNeededUnits]
            );
        }

        $totalAvailableUnitsCost = count($availableUnits) * $inventory->max_price;
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'availableUnits' => $availableUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
        ];
    }
    // helper functions
    private function bulkInsertUnitsOwnerSHip($companyId, $units)
    {
        $values = [];
        foreach ($units as $unit) {
            $values[] = "($unit->id, $companyId)";
        }
        $unitsSql = implode(',', array_map('intval', array_column($units, 'id')));

        $query =  DB::select(
            "UPDATE local_market_inventory_units
             SET status = ?
             WHERE `id` IN ($unitsSql)",
            [LocalMarketInventoryUnitsStatus::Reserved]
        );

        $query = "INSERT INTO unit_ownership (owner_type, unit_id,owner_id,owner_name) VALUES " . implode(', ', $values);
        // update number of rotations column for all old ownersip records
        // update need update number of rotations column FALSE to the last same company id and unit id
        DB::statement($query);
    }
    private function isNeedCheckUnitsOwnerShip($companyId, $inventoryId, $rotations = 0)
    {
        if ($rotations == 0) {
            return false;
        }
        return $this->ifCompanyBuyFromInventoryBefore($companyId, $inventoryId);
    }
    private function ifCompanyBuyFromInventoryBefore($companyId, $inventoryId): bool
    {
        $result = DB::select(
            "SELECT orders.*, inventories.*
                FROM local_market_orders orders
                LEFT JOIN local_market_order_has_inventories inventories
                ON orders.id = inventories.local_market_order_id
                WHERE orders.company_id = ?
                AND inventories.inventory_id = ?
                LIMIT 1",
            [$companyId, $inventoryId]
        );

        return !empty($result);
    }

    // function findValidPrice($minPrice, $maxPrice, $loanAmount)
    // {
    //     $startPrice = ceil($maxPrice);
    //     $endPrice = floor($minPrice);

    //     for ($price = $startPrice; $price > $endPrice; $price--) {
    //         if (fmod($loanAmount, $price) == 0) {
    //             return $price;
    //         }
    //     }

    //     throw new \Exception("Invalid price with item", 400);
    // }
}
