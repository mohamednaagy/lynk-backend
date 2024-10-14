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
    public function findEligibleInventoryForLoan(float $loanAmount, array $preferredItemTypes = [], array $usedInventories = [])
    {
        $inventory = $this->findInventory($loanAmount, $usedInventories, $preferredItemTypes);

        if (empty($inventory) && ! empty($preferredItemTypes)) {
            $inventory = $this->findInventory($loanAmount, $usedInventories);
        }

        return $inventory;
    }

    private function findInventory(float $loanAmount, array $usedInventories = [], array $preferredItemTypes = [])
    {
        $inventoryQuery = LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where('status', InventoryStatus::Active)
            ->whereHas('type', function ($query) {
                $query->where('status', CommodityTypeStatus::Active);
            })
            ->whereHas('supplier.detail', function ($query) {
                $query->where('status', CommoitySupplierStatus::Active);
            })
            ->when(! empty($usedInventories), function ($query) use ($usedInventories) {
                $query->whereNotIn('id', $usedInventories);
            });

        // First, try to find inventory items of the preferred type
        $preferredInventory = $this->findInventoryItems($inventoryQuery, $loanAmount, $preferredItemTypes);

        if ($preferredInventory) {
            return $preferredInventory;
        }

        // If not found, try to find inventory items of any type
        return $this->findInventoryItems($inventoryQuery, $loanAmount);
    }

    private function findInventoryItems($query, float $loanAmount, array $itemTypes = [])
    {
        if (! empty($itemTypes)) {
            $query = $query->whereIn('commodity_type_id', $itemTypes);
        }

        $inventories = $query->orderBy('max_price', 'DESC')->get();

        return $this->findExactCombination($inventories, $loanAmount);
    }

    private function findExactCombination($inventories, float $targetAmount, $currentCombination = [], $startIndex = 0)
    {
        if ($targetAmount == 0) {
            return $currentCombination;
        }

        if ($targetAmount < 0 || $startIndex >= count($inventories)) {
            return null;
        }

        for ($i = $startIndex; $i < count($inventories); $i++) {
            $inventory = $inventories[$i];

            if ($inventory->max_price <= $targetAmount) {
                $newCombination = array_merge($currentCombination, [$inventory]);
                $result = $this->findExactCombination(
                    $inventories,
                    $targetAmount - $inventory->max_price,
                    $newCombination,
                    $i + 1
                );

                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
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
