<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OwnershipService
{
    public function changeUnitOwnership(LocalMarketOrder $localMarketOrder, $ownerType, $ownerIdentifier)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        DB::table('local_market_unit_ownership')->insertUsing(
            [
                'unit_id',
                'local_market_order_id',
                'current_owner',
                'current_owner_type',
                'previous_owner',
                'previous_owner_type',
                'created_at',
                'updated_at',
            ],
            DB::table('local_market_inventory_units')
                ->select(
                    'id as unit_id',
                    DB::raw("{$localMarketOrder->id}"),
                    DB::raw("'{$ownerIdentifier}' as current_owner"),
                    DB::raw("'{$ownerType}' as current_owner_type"),
                    'current_owner as previous_owner',
                    'current_owner_type as previous_owner_type',
                    DB::raw("'{$timestamp}' as created_at"),
                    DB::raw("'{$timestamp}' as updated_at")
                )
                ->where('hold_for', $localMarketOrder->id)
        );
    }
}
