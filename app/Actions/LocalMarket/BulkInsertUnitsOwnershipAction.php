<?php

namespace App\Actions\LocalMarket;

use App\Enums\LocalMarketInventoryUnitsStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkInsertUnitsOwnershipAction
{
    public function execute($companyId, $units, $usedInventories)
    {
        $values = [];
        foreach ($units as $items) {
            foreach ($items as $unit) {
                $values[] = $unit->id;
            }
        }
        Log::info($units);
        Log::info($usedInventories);
        $unitsSql = implode(',', $values);

        DB::transaction(function () use ($unitsSql) {
            DB::update(
                "UPDATE local_market_inventory_units
                 SET status = ?
                 WHERE `id` IN ($unitsSql)",
                [LocalMarketInventoryUnitsStatus::Reserved]
            );

            $query = "INSERT INTO local_market_order_has_inventories (owner_type, unit_id, owner_id, owner_name) VALUES " . implode(', ', $values);
            $query = "INSERT INTO local_market_order_has_units (inventory_unit_id, order_has_inventory_id) VALUES " . implode(', ', $values);

            DB::statement($query);
        });
    }
}
