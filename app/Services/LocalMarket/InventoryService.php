<?php

namespace App\Services\LocalMarket;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Models\LocalMarketInventory;
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
    public function findEligibleInventoryForLoan($loanAmount, $preferredItemTypes)
    {
        $inventories = LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->whereHas('type', function ($query) {
                $query->where('status', CommodityTypeStatus::Active);
            })
            ->whereHas('supplier.detail', function ($query) {
                $query->where('status', CommoitySupplierStatus::Active);
            })
            ->orderBy('max_price', 'DESC')
            ->get();

        if (! empty($preferredItemTypes)) {
            $filteredInventories = $inventories->filter(function ($inventory) use ($preferredItemTypes) {
                return in_array($inventory->commodity_type_id, $preferredItemTypes);
            });

            $combination = $this->findOptimalCombination($filteredInventories, $loanAmount, $preferredItemTypes);

            if (! empty($combination)) {
                return $combination;
            }
        }

        return $this->findOptimalCombination($inventories, $loanAmount, $preferredItemTypes);
    }

    private function findOptimalCombination($inventories, $loanAmount, array $preferredCommodities)
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

    public static function refreshInventoryStocks($inventories)
    {
        LocalMarketInventory::query()->whereIn('id', $inventories)->each(function ($inventory) {
            $inventory->refreshStockQuantities();
        });
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
}
