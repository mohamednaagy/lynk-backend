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
    public function createInitialOrder($companyId, array $preferredTypes, $amount, int $rotations = 0): ?bool
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
            if (empty($suitableUnits['remainingLoan'])) { // no need to get another inventory
                dd('xx');
                $this->bulkInsertUnitsOwnerSHip($companyId, $suitableUnits['availableUnits']);
                return true;
            } else {
                Log::info($suitableUnits['remainingLoan']);
                // there is a need to get another inventory
                // recall the same function to get another inventory
                $usedUnits[] = $suitableUnits['availableUnits']; // hold the units for future use
                $usedUnitsIDs[] = collect($suitableUnits['availableUnits'])->pluck('id')->toArray();
                $usedInventories[] = $inventory->id;
                $this->reserveSuitableUnits($companyId, $preferredTypes, $suitableUnits['remainingLoan'], $rotations, $usedInventories, $usedUnitsIDs);
                dd($usedInventories);
            }
        }
    }
    // ===========.  First Step getting  inventory  =====================
    public function getInventory($preferredItemTypes, $amount, $usedInventories)
    {
        // TODISCUESS adding this condition or not
        //and (available_quantity * price) >= $amount
        //
        $preferredItemTypesSql = DB::raw("'" . implode("','", $preferredItemTypes) . "'");
        $usedInventoriesSql = DB::raw("'" . implode("','", $usedInventories) . "'");
        $inventory = DB::select(
            "SELECT * FROM `local_market_inventories`
                WHERE `commodity_type_id` IN ($preferredItemTypesSql)
                AND `id` NOT IN ($usedInventoriesSql)
                AND `max_price` <= ?
                AND `status` = " . LocalMarketInventoryStatus::Active . "
                ORDER BY (available_quantity * max_price) DESC
                LIMIT 1",
            [$amount]
        );
        if (empty($inventory)) {
            $inventory = DB::select(
                "SELECT * FROM `local_market_inventories`
                    WHERE `commodity_type_id` IN ($preferredItemTypesSql)
                    AND `id` NOT IN ($usedInventoriesSql)
                    AND `max_price` <= ?
                    AND `status` = " . LocalMarketInventoryStatus::Active . "
                    ORDER BY (`available_quantity` * `max_price`) DESC
                    limit 1",
                [$preferredItemTypesSql, $usedInventoriesSql, $amount]
            );
        }
        return $inventory[0] ?? null;
    }

    //================ second step check ownership ==================
    public function getUnits($companyId, $inventory, $loan, $usedUnits, $rotations = 0)
    {
        $needToCheckOwnerShip =  $this->isNeedCheckUnitsOwnerShip($companyId, $inventory->id, $rotations);
        // check if this company buy anything from this inventory before
        return  $this->getSuitableUnits($companyId, $inventory, $loan, $needToCheckOwnerShip, $usedUnits, $rotations);
    }
    public function getSuitableUnits($companyId, $inventory, $loan, $needToCheckOwnerShip = false, $usedUnits, $rotations = 0)
    {
        $numberOfNeededUnits = floor($loan / $inventory->max_price);
        if ($usedUnits) {
            $usedUnitsSql = DB::raw("'" . implode("','", Arr::flatten($usedUnits)) . "'");
            $whereClause = " AND `id` NOT IN ($usedUnitsSql)";
        }else {
            $whereClause = Null;
        }

        if ($needToCheckOwnerShip) {
            $availableUnits = DB::select(
                "SELECT * FROM local_market_inventory_units units
                    JOIN unit_ownership on unit_ownership.unit_id = units.id AND unit_ownership.company_id = ?
                    WHERE units.local_market_inventory_id = ?
                    AND unit_ownership.number_of_rotations >= ? 
                    ORDER BY unit_ownership.id DESC
                    LIMIT ?
                ",
                [$companyId, $inventory->id, $rotations, $numberOfNeededUnits]
            );
        } else {
            $availableUnits = DB::select(
                "SELECT * FROM local_market_inventory_units 
                    WHERE status = " . LocalMarketInventoryUnitsStatus::Free . " 
                    ".$whereClause."
                    AND local_market_inventory_id = ?
                    LIMIT ?
                ",
                [$inventory->id, $numberOfNeededUnits]
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
            $values[] = "(COMPANY, $unit, $companyId, $companyName)";
        }
        $query =  DB::row("
    update inventoryUnits where id in  $units set status = reserved
    ");
        // TODO move it to be after sell units
        $query = "INSERT INTO unit_ownership (owner_type, unit_id,owner_id,owner_name) VALUES " . implode(', ', $values);
        // TODO
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
                limit 1
            ",
            [$companyId, $inventoryId]
        );

        return !empty($result);
    }
}
