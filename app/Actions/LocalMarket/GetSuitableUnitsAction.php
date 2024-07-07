<?php

namespace App\Actions\LocalMarket;

use App\Enums\LocalMarketInventoryUnitsStatus;
use DragonCode\Support\Facades\Helpers\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetSuitableUnitsAction
{
    public function execute($companyId, $inventory, $loan, $usedUnits, $rotations = 0)
    {
        $needToCheckOwnership = app(CheckUnitsOwnershipAction::class)->execute($companyId, $inventory->id, $rotations);

        $numberOfNeededUnits = floor($loan / $inventory->max_price);
        Log::info("numberofUnits" . $numberOfNeededUnits);
        $whereClause = '';

        if ($usedUnits) {
            $usedUnitsSql = DB::raw("'" . implode("','", Arr::flatten($usedUnits)) . "'");
            $whereClause = " AND `id` NOT IN ($usedUnitsSql)";
        }

        if ($needToCheckOwnership) {
            $availableUnits = DB::select(
                "SELECT * FROM local_market_inventory_units units
                    JOIN unit_ownership ON unit_ownership.unit_id = units.id AND unit_ownership.company_id = ?
                    WHERE units.local_market_inventory_id = ?
                    AND unit_ownership.number_of_rotations >= ?
                    ORDER BY unit_ownership.id DESC
                    LIMIT ?",
                [$companyId, $inventory->id, $rotations, $numberOfNeededUnits]
            );
        } else {
            $availableUnits = DB::select(
                "SELECT * FROM local_market_inventory_units
                    WHERE status = ?
                    $whereClause
                    AND local_market_inventory_id = ?
                    LIMIT ?",
                [LocalMarketInventoryUnitsStatus::Free, $inventory->id, $numberOfNeededUnits]
            );
        }

        $totalAvailableUnitsCost = count($availableUnits) * $inventory->max_price;
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'availableUnits' => $availableUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
        ];
    }
}
