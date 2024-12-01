<?php

namespace App\Observers;

use App\Actions\Commodities\CommoditySupplier\UpdateCommoditySupplierStatusAction;
use App\Models\CompanySupplierDetail;
use App\Services\LocalMarket\LiveMarketService;

class CommoditySupplierObserver
{
    public $afterCommit = true;

    protected $UpdateCommoditySupplierStatusAction;

    protected LiveMarketService $liveMarketService;

    public function __construct(LiveMarketService $liveMarketService, UpdateCommoditySupplierStatusAction $UpdateCommoditySupplierStatusAction)
    {
        $this->liveMarketService = $liveMarketService;
        $this->UpdateCommoditySupplierStatusAction = $UpdateCommoditySupplierStatusAction;
    }

    /**
     * Handle the CommodityItem "updated" event.
     *
     * @return void
     */
    public function updated(CompanySupplierDetail $supplierDetails)
    {
        if ($supplierDetails->wasChanged('status')) {
            $this->UpdateCommoditySupplierStatusAction->handle($supplierDetails->company_id, $supplierDetails->status->value);
            // Handle supplier status changes
            $this->liveMarketService->handleSupplierStatusChange($supplierDetails);
        }
    }
}
