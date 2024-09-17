<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasUnit;
use Illuminate\Support\Facades\Log;

class UnitService
{
    /**
     * Retrieves eligible units from the inventory based on the loan amount and company history.
     */
    public function getEligibleUnits(int $orderNo, int $companyId, LocalMarketInventory $inventory, float $loan, int $maxNumberOfUnits = 10000): array
    {

        // TODO naser
        // 1- mark unit as reserced or hold
        // 2- recalculate invnetory units

        $numberOfNeededUnits = $this->calculateNeededUnits($loan, $inventory);
        // Check if the number of needed units exceeds the maximum limit
        if ($numberOfNeededUnits > $maxNumberOfUnits) {
            return $this->buildResponseArray($inventory, $numberOfNeededUnits, 0, $loan, false, "Number of needed units {$numberOfNeededUnits} exceeds the maximum limit {$maxNumberOfUnits}");
        }

        // Determine eligibility based on company history
        $eligibleUnitsCount = $this->hasCompanyPreviouslyPurchased($inventory, $companyId)
            ? $this->getUnitsWithOwnershipCheck($orderNo, $inventory, $numberOfNeededUnits, $companyId)
            : $this->getUnitsWithoutOwnershipCheck($orderNo, $inventory, $numberOfNeededUnits);

        $totalAvailableUnitsCost = $eligibleUnitsCount * $inventory->price();
        $remainingLoan = $loan - $totalAvailableUnitsCost;
        $isLoanCovered = ($remainingLoan <= 0);
        Log::info('unit message', ['max_unit' => $eligibleUnitsCount]);

        return $this->buildResponseArray($inventory, $numberOfNeededUnits, $eligibleUnitsCount, $remainingLoan, $isLoanCovered);
    }

    /**
     * Builds the response array for eligible units.
     */
    private function buildResponseArray(LocalMarketInventory $inventory, int $numberOfNeededUnits, int $eligibleUnitsCount, float $remainingLoan, bool $isLoanCovered, ?string $failureReason = null): array
    {
        $item = $inventory->item;
        $location = $inventory->location;

        return [
            'inventoryId' => $inventory->id,
            'item' => [
                'id' => $inventory->commodity_item_id,
                'name' => $item->name,
                'type' => $item->type->name,
                'volume_sellable_unit' => $item->volume_sellable_unit,
            ],
            'currency' => ['id' => $item->currency_id, 'name' => $item->currency->name],
            'measurement' => ['id' => $item->measurement_id, 'name' => $item->measurement->name],
            'commodityType' => ['id' => $item->commodity_type_id, 'name' => $item->type->name],
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'unique_identifier' => $location->unique_identifier,
            ],
            'supplier' => [
                'id' => $location->supplier->id,
                'name' => $location->supplier->name,
            ],
            'price' => $inventory->price(),
            'numberOfNeededUnits' => $numberOfNeededUnits,
            'numberOfSuitableUnits' => $eligibleUnitsCount,
            'totalCost' => $eligibleUnitsCount * $inventory->price(),
            'remainingLoan' => $remainingLoan,
            'isLoanCovered' => $isLoanCovered,
            'failureReason' => $failureReason,
        ];
    }

    /**
     * Calculate the number of needed units based on the loan amount and unit price.
     */
    private function calculateNeededUnits(float $loan, LocalMarketInventory $inventory): int
    {
        return (int) floor($loan / $inventory->price());
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
    }

    /**
     * Change the status of specified units.
     */
    public function changeUnitStatus(LocalMarketOrder $localMarketOrder, string $status = InventoryUnitsStatus::Reserved): void
    {
        LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)->update(['status' => $status]);
    }

    public function areUnitsUsedForTheFirstTime(LocalMarketOrder $order): bool
    {
        $unitIds = $order->orderUnits()->pluck('unit_id')->toArray();

        return ! LocalMarketOrderHasUnit::where('local_market_order_id', '!=', $order->id)->whereIn('unit_id', $unitIds)->exists();
    }
}
