<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Settings\Classes\LocalMurabahaSettings;
use Illuminate\Support\Facades\Log;

class UnitService
{
    /**
     * Retrieves eligible units from the inventory based on the loan amount and company history.
     */
    public function getEligibleUnits(int $companyId, LocalMarketInventory $inventory, float $loan): array
    {
        $numberOfNeededUnits = $this->calculateNeededUnits($loan, $inventory->price());

        if ($this->hasCompanyPreviouslyPurchased($inventory, $companyId)) {
            $eligibleUnits = $this->getUnitsWithOwnershipCheck($inventory, $numberOfNeededUnits, $companyId);
        }
        $eligibleUnits = $this->getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits);
        $totalAvailableUnitsCost = count($eligibleUnits) * $inventory->price();
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        Log::info("getEligibleUnits for company {$companyId} and inventory {$inventory->id} with loan {$loan} ", [
            'inventoryId' => $inventory->id,
            'numberOfNeededUnits' => $numberOfNeededUnits,
            'numberOfSuitableUnits' => count($eligibleUnits),
            'availableUnits' => $eligibleUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
            'isLoanCovered' => ($remainingLoan == 0),
        ]);

        return [
            'inventoryId' => $inventory->id,
            'numberOfNeededUnits' => $numberOfNeededUnits,
            'numberOfSuitableUnits' => count($eligibleUnits),
            'availableUnits' => $eligibleUnits,
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
    private function getUnitsWithOwnershipCheck(LocalMarketInventory $inventory, int $numberOfNeededUnits, int $companyId): array
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
            ->select(
                'local_market_inventory_units.id',
                'local_market_inventory_units.local_market_inventory_id',
                'local_market_inventory_units.current_owner_type',
                'local_market_inventory_units.current_owner'
            )
            ->get()
            ->toArray();
    }

    /**
     * Retrieve units from the local market inventory without checking ownership.
     */
    private function getUnitsWithoutOwnershipCheck(LocalMarketInventory $inventory, int $numberOfNeededUnits): array
    {
        return LocalMarketInventoryUnits::where('status', InventoryUnitsStatus::Free)
            ->where('local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->select('id', 'local_market_inventory_id', 'current_owner_type', 'current_owner')
            ->get()
            ->toArray();
    }

    /**
     * Change the status of specified units.
     */
    public function changeUnitStatus(array $units, string $status = InventoryUnitsStatus::Reserved): void
    {
        $ids = array_column($units, 'id');
        LocalMarketInventoryUnits::whereIn('id', $ids)->update(['status' => $status]);
    }
}
