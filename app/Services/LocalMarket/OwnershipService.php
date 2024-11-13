<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketUnitOwnership;

class OwnershipService
{
    // used it in observer
    public function addOwnershipLogsToDB($localMarketOrder, LocalMarketInventoryUnits $unit, $currentOwner, $currentOwnerIdentifier, $previousOwner, $previousOwnerIdentifier)
    {
        LocalMarketUnitOwnership::create([
            'local_market_order_id' => $localMarketOrder,
            'current_owner' => $currentOwner,
            'current_owner_type' => $currentOwnerIdentifier,
            'previous_owner' => $previousOwner,
            'previous_owner_type' => $previousOwnerIdentifier,
            'unit_id' => $unit->id,
        ]);
    }
}
