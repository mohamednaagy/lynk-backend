<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarketInventoryStatus;
use App\Models\LocalMarketInventory;

class UpdateCommoditySupplierStatusAction
{
    /**
     * Update the inventory status based on supplier status.
     *
     * @param int $companyId
     * @param int $supplierStatus
     * @return void
     */
    public function handle(int $companyId, int $supplierStatus): void
    {
        LocalMarketInventory::where('company_id', $companyId)
            ->whereHas('type', function ($query) {
                $query->where('status', CommodityTypeStatus::Active);
            })
            ->update([
                'status' => ($supplierStatus == CommoitySupplierStatus::Inactive) 
                    ? LocalMarketInventoryStatus::Inactive 
                    : LocalMarketInventoryStatus::Active
            ]);
    }
}
