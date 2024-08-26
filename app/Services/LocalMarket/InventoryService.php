<?php

namespace App\Services;

use App\Enums\LocalMarket\InventoryStatus;
use App\Models\LocalMarketInventory;
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
        // get preferred types first if there is no preferred get any inventory
        $inventory = $this->findInventory($loanAmount, $usedInventories, $preferredItemTypes);

        if (empty($inventory) && ! empty($preferredItemTypes)) {
            $inventory = $this->findInventory($loanAmount, $usedInventories);
        }

        Log::info('InventoryService:findEligibleInventoryForLoan', [
            'loanAmount' => $loanAmount,
            'preferredItemTypes' => $preferredItemTypes,
            'usedInventories' => $usedInventories,
            'inventory' => $inventory,
        ]);

        return $inventory;
    }

    private function findInventory(float $loanAmount, array $usedInventories = [], array $preferredItemTypes = [])
    {
        $inventory = LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where('status', InventoryStatus::Active)
            ->when(! empty($usedInventories), function ($query) use ($usedInventories) {
                $query->whereNotIn('id', $usedInventories);
            });

        if (empty($preferredItemTypes)) {
            return $inventory->orderByRaw('(`available_quantity` * `max_price`) DESC')
                ->first();
        } else {
            return $inventory->whereIn('commodity_type_id', $preferredItemTypes)
                ->orderByRaw('(`available_quantity` * `max_price`) DESC')
                ->first();
        }
    }

    public static function refreshInventoryStocks($inventories)
    {
        LocalMarketInventory::query()->whereIn('id', $inventories)->each(function ($inventory) {
            $inventory->refreshStockQuantities();
        });
    }
}
