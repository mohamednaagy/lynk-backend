<?php

namespace App\Actions\LocalMarket;

use Illuminate\Support\Facades\DB;

class CheckUnitsOwnershipAction
{
    public function execute($companyId, $inventoryId, $rotations = 0)
    {
        if ($rotations == 0) {
            return false;
        }
        return $this->ifCompanyBoughtFromInventoryBefore($companyId, $inventoryId);
    }

    private function ifCompanyBoughtFromInventoryBefore($companyId, $inventoryId): bool
    {
        $result = DB::select(
            "SELECT orders.*, inventories.*
                FROM local_market_orders orders
                LEFT JOIN local_market_order_has_inventories inventories
                ON orders.id = inventories.local_market_order_id
                WHERE orders.company_id = ?
                AND inventories.inventory_id = ?
                LIMIT 1",
            [$companyId, $inventoryId]
        );

        return !empty($result);
    }
}
