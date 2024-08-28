<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;

class UnitService
{
    /**
     * Retrieves eligible units from the inventory based on the loan amount and company history.
     */
    public function getEligibleUnits(int $orderNo, int $companyId, LocalMarketInventory $inventory, float $loan): array
    {
        $numberOfNeededUnits = $this->calculateNeededUnits($loan, $inventory->price());

        if ($this->hasCompanyPreviouslyPurchased($inventory, $companyId)) {
            $eligibleUnitsCount = $this->getUnitsWithOwnershipCheck($orderNo, $inventory, $numberOfNeededUnits, $companyId);
        } else {
            $eligibleUnitsCount = $this->getUnitsWithoutOwnershipCheck($orderNo, $inventory, $numberOfNeededUnits);
        }

        $totalAvailableUnitsCost = $eligibleUnitsCount * $inventory->price();
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'inventoryId' => $inventory->id,
            'item' => [
                'id' => $inventory->commodity_item_id,
                'name' => $inventory->item->name,
            ],
            'location' => [
                'id' => $inventory->location->id,
                'name' => $inventory->location->name,
                'unique_identifier' => $inventory->location->unique_identifier,
            ],
            'supplier' => [
                'id' => $inventory->location->supplier->id,
                'name' => $inventory->location->supplier->name,
            ],
            'price' => $inventory->price(),
            'numberOfNeededUnits' => $numberOfNeededUnits,
            'numberOfSuitableUnits' => $eligibleUnitsCount,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
            'isLoanCovered' => ($remainingLoan == 0),
        ];
    }

    /**
     * Calculate the number of needed units based on the loan amount and unit price.
     */
    private function calculateNeededUnits(float $loan, float $unitPrice): int
    {
        return (int) floor($loan / $unitPrice);
    }

    /**
     * Check if a company has previously purchased from the inventory.
     */
    private function hasCompanyPreviouslyPurchased(LocalMarketInventory $inventory, int $companyId): bool
    {
        return $inventory->hasCompanyBoughtFromInventory($companyId);
    }

    /**
     * Retrieves units from the local market inventory with an ownership check.
     */
    private function getUnitsWithOwnershipCheck(int $orderNo, LocalMarketInventory $inventory, int $numberOfNeededUnits, int $companyId)
    {
        // NAGY uncomment this line
        //        $rotationThreshold = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count ?? 0;
        $rotationThreshold = 0;

        return LocalMarketInventoryUnits::join('local_market_unit_rotations', 'local_market_unit_rotations.inventory_unit_id', '=', 'local_market_inventory_units.id')
            ->where('local_market_inventory_units.status', InventoryUnitsStatus::Free)
            ->where('local_market_inventory_units.local_market_inventory_id', $inventory->id)
            ->where('local_market_unit_rotations.company_id', $companyId)
            ->where('local_market_unit_rotations.number_of_rotations', '>=', $rotationThreshold)
            ->limit($numberOfNeededUnits)
            ->update(['local_market_inventory_units.hold_for' => $orderNo]);

        // get units
        // return LocalMarketInventoryUnits::join('local_market_unit_rotations', 'local_market_unit_rotations.inventory_unit_id', '=', 'local_market_inventory_units.id')
        //     ->where('local_market_inventory_units.status', InventoryUnitsStatus::Free)
        //     ->where('local_market_inventory_units.local_market_inventory_id', $inventory->id)
        //     ->where('local_market_unit_rotations.company_id', $companyId)
        //     ->where('local_market_unit_rotations.number_of_rotations', '>=', $rotationThreshold)
        //     ->limit($numberOfNeededUnits)
        //     ->select(
        //         'local_market_inventory_units.id',
        //         'local_market_inventory_units.local_market_inventory_id',
        //         'local_market_inventory_units.current_owner_type',
        //         'local_market_inventory_units.current_owner'
        //     )
        //     ->get()
        //     ->toArray();
    }

    /**
     * Retrieve units from the local market inventory without checking ownership.
     */
    private function getUnitsWithoutOwnershipCheck(int $orderNo, LocalMarketInventory $inventory, int $numberOfNeededUnits)
    {

        // update unit status
        return LocalMarketInventoryUnits::where('status', InventoryUnitsStatus::Free)
            ->where('local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->update(['hold_for' => $orderNo]);

        // Getting Units
        //     return LocalMarketInventoryUnits::where('status', InventoryUnitsStatus::Free)
        //         ->where('local_market_inventory_id', $inventory->id)
        //         ->limit($numberOfNeededUnits)
        //         ->select('id', 'local_market_inventory_id', 'current_owner_type', 'current_owner')
        //         ->get()
        //         ->toArray();
        // }
    }

    /**
     * Change the status of specified units.
     */
    public function changeUnitStatus(LocalMarketOrder $localMarketOrder, string $status = InventoryUnitsStatus::Reserved): void
    {
        LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)->update(['status' => $status]);
    }
}
