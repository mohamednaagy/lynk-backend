<?php

namespace App\Services\LocalMarket;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\Lender;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
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
     * @param  float  $loanAmount  The amount of the loan, used to filter inventory items by maximum price.
     * @param  array  $preferredItemTypes  An optional array of preferred commodity type IDs. If provided, the function prioritizes items matching these types.
     * @param  array  $usedInventories  An optional array of inventory IDs that have already been used. These inventories are excluded from the results.
     * @return LocalMarketInventory|null The best matching inventory item, or null if no eligible inventory is found.
     */
    public function findEligibleInventoryForLoan(LocalMarketOrder $localMarketOrder)
    {
        $loanAmount = $localMarketOrder->amount;
        $companyId = $localMarketOrder->company_id;
        $preferredItemTypes = $localMarketOrder->preferred_commodity_type;

        $forcePreferredCommodityType = $localMarketOrder->lender
            ->lenderDetail
            ->force_preferred_commodity_type;
        // $inventories = $this->findEligibleInventoriesForLoanVersionOne($loanAmount);


        // Attempt to find the optimal combination with filtered inventories if required
        if ($forcePreferredCommodityType && !empty($preferredItemTypes)) {
            $inventories = $this->findEligibleInventoriesForLoanVersionTwo($loanAmount, $companyId, $preferredItemTypes);
            $combination = $this->findOptimalCombination($inventories, $loanAmount);

            if (!empty($combination)) {
                return $combination;
            }
        }else {
            $inventories = $this->findEligibleInventoriesForLoanVersionTwo($loanAmount, $companyId);
            // If forcing is not required or no combination was found, use the original inventories
            if (! empty($preferredItemTypes)) {
                // Filter inventories based on preferred commodity types
                $filteredInventories = $this->filterInventoriesByPreferredTypes($inventories, $preferredItemTypes);

                $combination = $this->findOptimalCombination($filteredInventories, $loanAmount);

                if (!empty($combination)) {
                    return $combination;
                }
            }

            return $this->findOptimalCombination($inventories, $loanAmount, $preferredItemTypes);

        }

       
    }

    private function findEligibleInventoriesForLoanVersionOne($loanAmount)
    {
        return LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->whereHas('type', function ($query){
                $query->where('status', CommodityTypeStatus::Active);
            })
            ->whereHas('supplier.detail', function ($query) {
                $query->where('status', CommoitySupplierStatus::Active);
            })
            ->orderBy('max_price', 'DESC')
            ->get();
    }

    private function findEligibleInventoriesForLoanVersionTwo($loanAmount, $companyId, $preferredItemTypes = [])
    {
        return LocalMarketInventory::query()
            ->select([
                'local_market_inventories.*',
                'local_market_eligible_quantities.eligible_quantity as available_quantity',
            ])
            ->join('local_market_eligible_quantities', function ($join) use ($companyId) {
                $join->on('local_market_inventories.id', '=', 'local_market_eligible_quantities.inventory_id')
                    ->where('local_market_eligible_quantities.company_id', '=', $companyId);
            })
            ->where('local_market_inventories.status', InventoryStatus::Active)
            ->where('local_market_inventories.available_quantity', '>', 0)
            ->where('local_market_inventories.max_price', '<=', $loanAmount)
            ->whereHas('type', function ($query) use ($preferredItemTypes) {
                $query->where('status', CommodityTypeStatus::Active);
                if (!empty($preferredItemTypes)) {
                    $query->whereIn('commodity_types.id', $preferredItemTypes);
                }
            })
            ->whereHas('supplier.detail', function ($query) {
                $query->where('status', CommoitySupplierStatus::Active);
            })
            ->orderBy('local_market_inventories.max_price', 'DESC')
            ->orderBy('local_market_eligible_quantities.eligible_quantity', 'desc')
            ->get();
    }

    private function findOptimalCombination($inventories, $loanAmount, array $preferredCommodities = [])
    {
        $maxUnits = config('trader.providers.lynk.max_units_per_trader', 10000);

        // Convert inventory objects to arrays
        $inventoriesArray = array_values($inventories->all());

        $finalSelectedInventories = [];
        $maxCoveredAmount = 0;
        $loanCovered = false; // Early exit flag

        Log::info("Trying to cover loan amount of $loanAmount with inventories...");

        // Outer loop to iterate through all inventories
        foreach ($inventoriesArray as $outerIndex => $outerInventory) {
            if ($loanCovered) {
                break;
            } // Early exit if already covered
            Log::info(" ====== Starting new combination attempt with inventory {$outerInventory['id']} =======");

            $coveredAmount = 0;
            $usedUnits = 0;
            $selectedInventories = [];

            // Inner loop to try combinations starting with the current outer inventory
            for ($innerIndex = $outerIndex; $innerIndex < count($inventoriesArray); $innerIndex++) {
                $inv = $inventoriesArray[$innerIndex];
                if ($coveredAmount >= $loanAmount || $usedUnits >= $maxUnits) {
                    break; // Stop if the loan is covered or max units reached
                }

                $availableUnits = min($inv['available_quantity'], $maxUnits - $usedUnits);
                $unitPrice = $inv['max_price'];
                $remainingLoanAmount = $loanAmount - $coveredAmount;
                $maxAffordableUnits = (int) floor($remainingLoanAmount / $unitPrice);

                $unitsToUse = min($availableUnits, $maxAffordableUnits);
                $amountToCover = $unitsToUse * $unitPrice;

                Log::info("Attempting inventory {$inv['id']} with {$unitsToUse} units at {$unitPrice} per unit, covering {$amountToCover}");

                if ($amountToCover > 0) {
                    $selectedInventories[] = [
                        'id' => $inv['id'],
                        'commodity_type_id' => $inv['commodity_type_id'],
                        'numberOfUnits' => $unitsToUse,
                        'amount' => $amountToCover,
                    ];

                    $coveredAmount += $amountToCover;
                    $usedUnits += $unitsToUse;
                    Log::info("Updated covered amount: $coveredAmount, used units: $usedUnits");
                }

                // Check if we've covered the loan amount
                if ($coveredAmount >= $loanAmount) {
                    // Update max covered amount if this combination is better
                    $maxCoveredAmount = $coveredAmount;
                    $finalSelectedInventories = $selectedInventories;
                    $loanCovered = true; // Mark as covered for early exit
                    Log::info("Successfully covered the loan amount with a total of $coveredAmount.");
                    break; // Exit the inner loop since we've covered the loan
                }
            }
        }

        if (! $loanCovered) {
            Log::info("Failed to cover the exact loan amount. Covered $maxCoveredAmount of $loanAmount.");

            return false;
        }

        Log::info("Successfully covered the loan amount with a total of $maxCoveredAmount.");

        return $finalSelectedInventories;
    }

    public static function deleteInventory($inventory)
    {
        try {
            Log::info("Start Deleting Inventory ID: {$inventory->id}");

            DB::select('CALL DeleteLocalMarketInventoryUnits(?, ? , ?)', [$inventory->id, InventoryUnitsStatus::Free, $inventory->available_quantity]);
            Log::info("Successfully soft deleted units for inventory ID: {$inventory->id}");
            $inventory->delete();

            // (new LiveMarketService)->handleInventoryDeletion($inventory);
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
        foreach ($localMarketOrder->orderInventories as $orderInventory) {
            $inventory = $orderInventory->inventory;
            LocalMarketInventoryUnits::where('hold_for', $localMarketOrder->id)
                ->where('local_market_inventory_id', $inventory->id)
                ->chunkById(100, function ($units) use ($localMarketOrder) {
                    foreach ($units as $unit) {
                        LocalMarketInventoryUnits::where('id', $unit->id)->update([
                            'status' => InventoryUnitsStatus::Free,
                            'hold_for' => 0,
                            'last_completed_order_id' => $localMarketOrder->id,
                            'previous_company_id_owners' => $this->getUpdatedPreviousOwners($unit, $localMarketOrder->company_id),
                        ]);
                    }
                });
            // Refresh stock quantities for the current inventory after processing all units
            $inventory->refreshStockQuantities();
        }
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

    /**
     * Filter inventories based on preferred commodity types.
     *
     * @param \Illuminate\Support\Collection $inventories
     * @param array|null $preferredItemTypes
     * @return \Illuminate\Support\Collection
     */
    private function filterInventoriesByPreferredTypes($inventories, $preferredItemTypes)
    {
        if (empty($preferredItemTypes)) {
            return $inventories;
        }

        return $inventories->filter(function ($inventory) use ($preferredItemTypes) {
            return in_array($inventory->commodity_type_id, $preferredItemTypes);
        });
    }
}
