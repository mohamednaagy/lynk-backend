<?php

namespace App\Observers;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarketInventoryStatus;
use App\Models\CompanySupplierDetail;
use App\Models\LocalMarketInventory;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;

class CommoditySupplierObserver
{
    public $afterCommit = true;

    /**
     * Handle the CommodityItem "updated" event.
     *
     * @return void
     */
    public function updated(CompanySupplierDetail $supplier)
    {
        if ($supplier->wasChanged('status')) {
            
            // Update inventory status based on commodity supplier status
            LocalMarketInventory::where('company_id', $supplier->company_id)->whereHas('type', function ($query) {
                $query->where('status', CommodityTypeStatus::Active);
            })->update([
                'status' => ($supplier->status->value == CommoitySupplierStatus::Inactive) ? LocalMarketInventoryStatus::Inactive : LocalMarketInventoryStatus::Active
            ]);
        }
    }
}
