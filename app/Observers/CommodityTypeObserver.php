<?php

namespace App\Observers;

use App\Actions\Commodities\CommodityType\UpdateCommodityTypeStatusAction;
use App\Models\CommodityType;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CommodityTypeObserver implements ShouldHandleEventsAfterCommit
{
    protected $UpdateCommodityTypeStatusAction;

    public function __construct(
        UpdateCommodityTypeStatusAction $UpdateCommodityTypeStatusAction
    ) {
        $this->UpdateCommodityTypeStatusAction = $UpdateCommodityTypeStatusAction;
    }

    /**
     * Handle the CommodityType "updated" event.
     */
    public function updated(CommodityType $type): void
    {
        // Handle status changes for commodity type action
        if ($type->wasChanged('status')) {
            $this->UpdateCommodityTypeStatusAction->handle($type->id, $type->status->value);
        }
    }
}
