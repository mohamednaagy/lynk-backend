<?php

namespace App\Observers;

use App\Actions\Commodities\CommoditySupplier\UpdateCommoditySupplierStatusAction;
use App\Models\CompanySupplierDetail;

class CommoditySupplierObserver
{
    public $afterCommit = true;
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
           $this->UpdateCommoditySupplierStatusAction->handle($supplierDetails->company_id, $supplierDetails->status->value);
        }
    }
}
