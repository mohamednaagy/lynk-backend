<?php

namespace App\Observers;

use App\Models\LocalMarketInventoryUnits;
use App\Services\LocalMarket\OwnershipService;

class LocalMarketInventoryUnitsObserver
{
    public function updated(LocalMarketInventoryUnits $unit)
    {
        if ($unit->wasChanged(['current_owner', 'current_owner_type'])) {
            app(OwnershipService::class)->addOwnershipLogsToDB($unit->hold_for, $unit, $unit->current_owner, $unit->current_owner_type, $unit->previous_owner, $unit->previous_owner_type);
        }
    }
}
