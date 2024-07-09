<?php

namespace App\Actions\LocalMarket;

use App\Enums\LocalMarketInventoryStatus;
use DragonCode\Support\Facades\Helpers\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetInventoryAction
{
    public function execute($preferredItemTypes, $amount, $usedInventories)
    {
        $preferredItemTypesSql = DB::raw("'" . implode("','", $preferredItemTypes) . "'");
        $whereClause = '';
        if ($usedInventories) {
            $usedInventoriesSql = DB::raw("'" . implode("','", Arr::flatten($usedInventories)) . "'");
            $whereClause = " AND `id` NOT IN ($usedInventoriesSql)";
        }
        

        $inventory = DB::select(
            "SELECT * FROM `local_market_inventories`
                WHERE `commodity_type_id` IN ($preferredItemTypesSql)
                $whereClause
                AND `max_price` <= ?
                AND `status` = ?
                ORDER BY (`available_quantity` * `max_price`) DESC
                LIMIT 1",
            [$amount, LocalMarketInventoryStatus::Active]
        );

        return $inventory[0] ?? null;
    }
}
