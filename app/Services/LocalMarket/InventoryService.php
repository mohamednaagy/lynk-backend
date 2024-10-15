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
            })->get();

        return $this->findOptimalCombination($inventories, $loanAmount, $preferredItemTypes);
    }

    private function findOptimalCombination($inventories, $loanAmount, array $preferredCommodities)
    {
        $maxUnits = config('trader.providers.lynk.max_units_per_trader', 10000);

        // Convert inventory objects to arrays
        $inventoriesArray = $inventories->all();
        $preferredInventories = array_filter($inventoriesArray, fn ($inventory) => in_array($inventory['commodity_type_id'], $preferredCommodities));

        $coveredAmount = 0;
        $usedUnits = 0;
        $selectedInventories = [];

        // Function to cover loan amount from given inventories
        $coverFromInventories = function ($inventories) use (&$coveredAmount, &$usedUnits, $loanAmount, $maxUnits, &$selectedInventories) {
            foreach ($inventories as $inventory) {
                if ($coveredAmount >= $loanAmount || $usedUnits >= $maxUnits) {
                    break;
                }

                $availableUnits = min($inventory['available_quantity'], $maxUnits - $usedUnits);
                $unitPrice = $inventory['max_price'];

                // Calculate maximum amount possible without exceeding remaining loan
                $remainingLoanAmount = $loanAmount - $coveredAmount;
                $maxAffordableUnits = (int) floor($remainingLoanAmount / $unitPrice);

                // Determine units to use from this inventory
                $unitsToUse = min($availableUnits, $maxAffordableUnits);
                $amountToCover = $unitsToUse * $unitPrice;

                if ($amountToCover > 0) {
                    $selectedInventories[] = [
                        'id' => $inventory['id'],
                        'commodity_type_id' => $inventory['commodity_type_id'],
                        'numberOfUnits' => $unitsToUse,
                        'amount' => $amountToCover,
                    ];

                    $coveredAmount += $amountToCover;
                    $usedUnits += $unitsToUse;
                }
            }
        };

        // Try to cover the entire loan from preferred inventories first
        $coverFromInventories($preferredInventories);

        // If the loan amount is not fully covered, try to cover it with all inventories
        if ($coveredAmount < $loanAmount) {
            // Reset selected inventories and amounts for this second attempt
            $selectedInventories = [];
            $coveredAmount = 0;
            $usedUnits = 0;

            // Sort all inventories by max price (desc) for the second attempt
            usort($inventoriesArray, fn ($a, $b) => $b['max_price'] - $a['max_price']);
            $coverFromInventories($inventoriesArray);
        }

        // Ensure exact loan amount is covered
        if ($coveredAmount < $loanAmount) {
            Log::info("Exact loan amount not covered: covered $coveredAmount of $loanAmount.");

            return false;
        }

        return $selectedInventories;
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
