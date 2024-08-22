<?php

namespace App\Services;

use App\Enums\LocalMarket\InventoryStatus;
use App\Models\LocalMarketInventory;

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
    public static function findEligibleInventoryForLoan(int $loanAmount, array $preferredItemTypes = [], array $usedInventories = [])
    {
        return LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where('status', InventoryStatus::Active)
            ->when(! empty($preferredItemTypes), function ($query) use ($preferredItemTypes) {
                $query->whereIn('commodity_type_id', $preferredItemTypes);
            })
            ->when(! empty($usedInventories), function ($query) use ($usedInventories) {
                $query->whereNotIn('id', $usedInventories);
            })
            ->orderByRaw('(`available_quantity` * `max_price`) DESC')
            ->first();
    }
}
