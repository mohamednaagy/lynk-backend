<?php

namespace App\Actions\Commodities\CommoditySupplier;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\LocalMarketInventory;

class UpdateCommoditySupplierStatusAction
{
    /**
     * Update the inventory status based on supplier status.
     */
    public function handle(int $companyId, int $supplierStatus): void
    {
        LocalMarketInventory::where('company_id', $companyId)
            ->whereHas('type', function ($query) {
                $query->where('status', CommodityTypeStatus::Active);
            })
            ->chunkById(100, function ($inventories) use ($supplierStatus) {
                foreach ($inventories as $inventory) {
                    $inventory->status = ($supplierStatus == CommoitySupplierStatus::Inactive)
                        ? InventoryStatus::Inactive
                        : InventoryStatus::Active;
                    $inventory->save();
                }
            });
    }
}
