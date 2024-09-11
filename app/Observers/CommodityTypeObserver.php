<?php

namespace App\Observers;

use App\Enums\CommodityTypeStatus;
use App\Enums\CommoitySupplierStatus;
use App\Enums\LocalMarketInventoryStatus;
use App\Models\CommodityType;
use App\Models\LocalMarketInventory;
use App\Models\Supplier;

class CommodityTypeObserver
{
    public $afterCommit = true;

    /**
     * Handle the CommodityItem "updated" event.
     *
     * @return void
     */
    public function updated(CommodityType $type)
    {
        if ($type->wasChanged('status')) {

            // Get IDs of suppliers with active details
            $activeSupplierIds = Supplier::whereHas('detail', function ($query) {
                $query->where('status', CommoitySupplierStatus::Active);
            })->pluck('id');

            // Update inventory status based on commodity type status
            LocalMarketInventory::where('commodity_type_id', $type->id)
            ->whereIn('company_id', $activeSupplierIds)
            ->update([
                'status' => ($type->status->value == CommodityTypeStatus::Inactive) ? LocalMarketInventoryStatus::Inactive : LocalMarketInventoryStatus::Active
            ]);
        }
    }
}
