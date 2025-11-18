<?php

namespace App\Observers;

use App\Actions\Commodities\CommoditySupplier\UpdateCommoditySupplierStatusAction;
use App\Models\CompanySupplierDetail;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CommoditySupplierObserver implements ShouldHandleEventsAfterCommit
{
    protected $UpdateCommoditySupplierStatusAction;

    public function __construct(UpdateCommoditySupplierStatusAction $UpdateCommoditySupplierStatusAction)
    {
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
            // Handle supplier status changes
            $this->UpdateCommoditySupplierStatusAction->handle($supplierDetails->company_id, $supplierDetails->status->value);
        }
    }
}
