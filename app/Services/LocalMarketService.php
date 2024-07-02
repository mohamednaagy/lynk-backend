<?php

namespace App\Services;

use App\Enums\LocalMarketInventoryStatus;
use Illuminate\Support\Facades\DB;

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
            return false;
        } else {
            dd($inventory);
            // try to get the inventory for preferred types (happy scenario)
            // TODO : refactor this code with form to handle : reserved units , total cost , remaining total
            $suitableUnits = $this->getUnits($companyId, $inventory, $loanAmount, $rotations);
            if (empty($suitableUnits['remainingLoan'])) { // no need to get another inventory
            $this->bulkInsertUnitsOwnerSHip($companyId, $suitableUnits['availableUnits']);
            return true;
            } else {
            // there is a need to get another inventory
            // recall the same function to get another inventory
            $usedUnits[] = $suitableUnits['availableUnits']; // hold the units for future use
            $usedInventories[] = $inventory->id;
            $this->reserveSuitableUnits($companyId, $preferredTypes, $suitableUnits['remainingLoan'], $rotations, $usedInventories, $usedUnits);
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
                WHERE `commodity_type_id` IN (?)
                AND `id` NOT IN (?)
                AND `max_price` <= ?
                AND `status` = ".LocalMarketInventoryStatus::Active."
                ORDER BY (available_quantity * max_price) DESC
                LIMIT 1",
            [$preferredItemTypesSql, $usedInventoriesSql, $amount]
        );
        if(empty($inventory)) {
            $inventory = DB::select(
            "SELECT * FROM `local_market_inventories`
                    WHERE `commodity_type_id` NOT IN (?)
                    AND `id` NOT IN (?)
                    AND `max_price` <= ?
                    AND `status` = ".LocalMarketInventoryStatus::Active."
                    ORDER BY (`available_quantity` * `max_price`) DESC
                    limit 1",
                [$preferredItemTypesSql, $usedInventoriesSql, $amount]
            );
        }
    return $inventory;
    }

    //================ second step check ownership ==================
    public function getUnits($companyId, $inventory, $loan, $rotations = 0)
    {
    $needToCheckOwnerShip =  $this->isNeedCheckUnitsOwnerShip($companyId, $inventory->id, $rotations);
    // check if this company buy anything from this inventory before
    return  $this->getSuitableUnits($companyId, $inventory, $loan, $needToCheckOwnerShip, $rotations);
    }
    public function getSuitableUnits($companyId, $inventory, $loan, $needToCheckOwnerShip = false, $rotations = 0)
    {
    $numberOfNeededUnits = $loan / $inventory->price;
    // TODO discuess i can add limit with numberOfNeededUnits but may be i need more
    // i can check for ownership in database engine or in laravel code we can check the best performance
    if ($needToCheckOwnerShip) {
    $availableUnits = DB::row("
    select * from inventoryUnits
    join unit_ownership on unit_ownership.unit_id = inventoryUnits.id and unit_ownership.company_id = $companyId
    where inventoryId = $inventory->id
    and unit_ownership.number_of_rotations >= $rotations limit $numberOfNeededUnits
    order by unit_ownership desc // get the last row of ownership for this company
    limit 1
    ");
    } else {
    $availableUnits = DB::row("
    select * from inventoryUnits where status = free limit $numberOfNeededUnits and inventory_id = $inventory
    ");
    }
    $totalAvailableUnitsCost = count($availableUnits) * $inventory->price;
    $remeningLoan = $loan - $totalAvailableUnitsCost;
    return [
    'availableUnits' => $availableUnits,
    'totalCost' => $totalAvailableUnitsCost,
    'remeningLoan' => $remeningLoan,
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
    $result = DB::row("
    select * from companyInventoryHistory where companyId = $companyId and
    inventoryId = $inventoryId limit 1
    ");
    return !empty($result);
    }
}
