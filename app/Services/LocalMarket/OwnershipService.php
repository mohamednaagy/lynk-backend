<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketUnitOwnership;
use Illuminate\Support\Facades\Log;

class OwnershipService
{
    // used it in observer
    public function addOwnershipLogsToDB(
        $localMarketOrder,
        LocalMarketInventoryUnits $unit,
        $currentOwner,
        $currentOwnerIdentifier,
        $previousOwner,
        $previousOwnerIdentifier,
        $action
    ) {
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('Adding ownership logs to DB for local_market_order_id '.$localMarketOrder.' - '.$action, $localMarketOrder), [
            'localMarketOrderId' => $localMarketOrder,
            'action' => $action,
        ]);
        LocalMarketUnitOwnership::create([
            'local_market_order_id' => $localMarketOrder,
            'current_owner' => $currentOwner,
            'current_owner_type' => $currentOwnerIdentifier,
            'previous_owner' => $previousOwner,
            'previous_owner_type' => $previousOwnerIdentifier,
            'unit_id' => $unit->id,
            'action' => $action,
        ]);
    }
}
