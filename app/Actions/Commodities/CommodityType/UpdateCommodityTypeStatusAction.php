<?php

namespace App\Actions\Commodities\CommodityType;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\LocalMarketInventory;
use App\Models\Supplier;

class UpdateCommodityTypeStatusAction
{
    /**
     * Update the inventory status based on supplier status.
     *
     * @param int $commodityTypeId
     * @param int $commodityStatus
     * @return void
     */
    public function handle(int $commodityTypeId, int $commodityStatus): void
    {
        $activeSuppliers = Supplier::withSupplierStatus(CommoitySupplierStatus::Active)->pluck('id');
        // Update inventory status based on commodity type status
        LocalMarketInventory::where('commodity_type_id', $commodityTypeId)
        ->whereIn('company_id', $activeSuppliers)
        ->update([
            'status' => ($commodityStatus == CommodityTypeStatus::Inactive) ? InventoryStatus::Inactive : InventoryStatus::Active
        ]);
    }
}
