<?php

namespace App\Services\LocalMarket;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanCoverageStrategy\Contracts\LoanCoverageStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(private LoanCoverageStrategy $loanCoverageStrategy) {}

    /**
     * Finds eligible inventory from the local market based on the loan amount, preferred item types, and previously used inventories.
     *
     * This function retrieves the best matching inventory item that meets the criteria specified:
     * - It considers inventory items with a maximum price less than or equal to the loan amount.
     * - Filters inventory items based on their status (must be active).
     * - Allows filtering by preferred commodity types.
     * - Excludes inventories that have already been used.
     * - Orders the inventory by a combination of available quantity and maximum price to prioritize the most suitable items.
     *
     * @return LocalMarketInventory|false The best matching inventory item, or false if no eligible inventory is found.
     */
    public function findEligibleInventoryForLoan(LocalMarketOrder $localMarketOrder)
    {
        $loanAmount = $localMarketOrder->amount;
        $companyId = $localMarketOrder->company_id;
        $preferredItemTypes = $localMarketOrder->preferred_commodity_type;

        // First try with preferred commodity types
        $preferredInventories = $this->findEligibleInventoriesForLoan(
            $loanAmount,
            $companyId,
            $preferredItemTypes
        );
        $combination = $this->findOptimalCombination($loanAmount, $preferredInventories);

        // Return combination if found or if we must use preferred types
        if (! empty($preferredItemTypes) || ! empty($combination)) {
            return $combination;
        } else {
            // Fallback to all inventory types if allowed

            $allInventories = $this->findEligibleInventoriesForLoan($loanAmount, $companyId);

            return $this->findOptimalCombination($loanAmount, $allInventories);
        }
    }

    private function findEligibleInventoriesForLoan($loanAmount, $companyId, $preferredItemTypes = [])
    {
        return LocalMarketInventory::query()
            ->select([
                'local_market_inventories.*',
                'local_market_eligible_quantities.eligible_quantity as available_quantity',
                'commodity_items.max_price as max_price', // Select max_price for ordering
                'commodity_items.commodity_type_id',
            ])
            ->join('local_market_eligible_quantities', function ($join) use ($companyId) {
                $join->on('local_market_inventories.id', '=', 'local_market_eligible_quantities.inventory_id')
                    ->where('local_market_eligible_quantities.company_id', '=', $companyId);
            })
            ->join('commodity_items', 'local_market_inventories.commodity_item_id', '=', 'commodity_items.id')
            ->where('local_market_inventories.status', InventoryStatus::Active)
            ->where('commodity_items.max_price', '<=', $loanAmount)
            ->where('local_market_eligible_quantities.eligible_quantity', '>', 0)
            ->whereHas('type', function ($query) use ($preferredItemTypes) {
                $query->where('status', CommodityTypeStatus::Active);
                if (! empty($preferredItemTypes)) {
                    $query->whereIn('commodity_types.id', $preferredItemTypes);
                }
            })
            ->whereHas('supplier.detail', function ($query) {
                $query->where('status', CommoitySupplierStatus::Active);
            })
            ->lockForUpdate()
            ->orderBy('commodity_items.max_price', 'DESC')
            ->orderBy('local_market_eligible_quantities.eligible_quantity', 'desc')
            ->get();

    }

    private function findOptimalCombination($loanAmount, $inventories)
    {
        if ($inventories->isEmpty()) {
            Log::channel('local_market')->info('The inventories list are empty');

            return false;
        }

        if (fmod($loanAmount, 1) !== 0.0) {
            Log::channel('local_market')->info('The loan amount must be integer');

            return false;
        }

        $result = $this->loanCoverageStrategy->calculateCombination($loanAmount, $inventories->all());

        return empty($result) ? false : $result;
    }

    public static function deleteInventory($inventory)
    {
        try {
            Log::info("Start Deleting Inventory ID: {$inventory->id}");

            DB::select('CALL DeleteLocalMarketInventoryUnits(?, ? , ?)', [$inventory->id, InventoryUnitsStatus::Free, $inventory->available_quantity]);
            Log::info("Successfully soft deleted units for inventory ID: {$inventory->id}");
            $inventory->delete();

            Log::info("Success for deleting inventory ID: {$inventory->id}");
        } catch (\Exception $e) {
            Log::error("Updated Inventory ID: {$inventory->id} status to Problem due to error: {$e->getMessage()}");
            throw $e;
        }
    }

    public function cancelOrderUnits(LocalMarketOrder $localMarketOrder)
    {

        foreach ($localMarketOrder->orderInventories as $orderInventory) {
            $inventory = $orderInventory->inventory;
            LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
                ->where('local_market_inventory_id', $inventory->id)
                ->update([
                    'status' => InventoryUnitsStatus::Free,
                    'hold_for' => 0,
                ]);
            $inventory->refreshStockQuantities();
        }
    }

    public function completeOrderUnits(LocalMarketOrder $localMarketOrder)
    {
        $inventoriesToRefresh = collect();

        foreach ($localMarketOrder->orderInventories as $orderInventory) {
            $inventory = $orderInventory->inventory;
            $inventoriesToRefresh->push($inventory);

            LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
                ->where('local_market_inventory_id', $inventory->id)
                ->chunkById(500, function ($units) use ($localMarketOrder) {
                    foreach ($units as $unit) {
                        LocalMarketInventoryUnits::where('id', $unit->id)->update([
                            'status' => InventoryUnitsStatus::Free,
                            'hold_for' => 0,
                            'last_completed_order_id' => $localMarketOrder->id,
                            'previous_company_id_owners' => $this->getUpdatedPreviousOwners($unit, $localMarketOrder->company_id),
                        ]);
                    }
                });
        }

        $inventoriesToRefresh->unique('id')
            ->sortBy('id')
            ->each(function ($inventory) {
                $inventory->refreshStockQuantities();
            });
    }

    private function getUpdatedPreviousOwners(LocalMarketInventoryUnits $unit, $ownerId)
    {
        $previousOwners = $unit->previous_company_id_owners ?? [];

        // Ensure the array has a maximum length of 10
        if (count($previousOwners) >= 10) {
            array_pop($previousOwners); // Remove the oldest owner
        }

        // Add the new owner to the beginning of the array
        array_unshift($previousOwners, $ownerId);

        return $previousOwners;
    }

    /**
     * Confirm the delivery of order units by updating their status and
     * refreshing stock quantities.
     *
     * This function processes the units associated with the given local market
     * order, setting their status to 'Free', removing their hold, and deleting
     * them. It also refreshes the stock quantities for each inventory involved.
     * The operation is performed within a database transaction to ensure
     * atomicity. In case of an error, it logs the failure.
     *
     * @param  LocalMarketOrder  $localMarketOrder  The order whose units are to be
     *                                              confirmed for delivery.
     */
    public function confirmDeliverOrderUnits(LocalMarketOrder $localMarketOrder): void
    {
        try {
            DB::transaction(function () use ($localMarketOrder) {
                LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
                    ->update(['status' => InventoryUnitsStatus::Free, 'hold_for' => null, 'deleted_at' => now()]);
                foreach ($localMarketOrder->orderInventories as $orderInventory) {
                    $orderInventory->inventory->refreshStockQuantities();
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to deliver order units.', [
                'localMarketOrderId' => $localMarketOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
