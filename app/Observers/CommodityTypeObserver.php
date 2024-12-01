<?php

namespace App\Observers;

use App\Actions\Commodities\CommodityType\UpdateCommodityTypeStatusAction;
use App\Models\CommodityType;
use App\Services\LocalMarket\LiveMarketService;

class CommodityTypeObserver
{
    public $afterCommit = true;

    protected $UpdateCommodityTypeStatusAction;

    protected LiveMarketService $liveMarketService;

    public function __construct(
        UpdateCommodityTypeStatusAction $UpdateCommodityTypeStatusAction,
        LiveMarketService $liveMarketService
    ) {
        $this->UpdateCommodityTypeStatusAction = $UpdateCommodityTypeStatusAction;
        $this->liveMarketService = $liveMarketService;
    }

    /**
     * Handle the CommodityType "updated" event.
     */
    public function updated(CommodityType $type): void
    {
        // Handle status changes for commodity type action
        if ($type->wasChanged('status')) {
            $this->UpdateCommodityTypeStatusAction->handle($type->id, $type->status->value);
            $this->liveMarketService->handleCommodityTypeStatusChange($type);
        }
    }
}
