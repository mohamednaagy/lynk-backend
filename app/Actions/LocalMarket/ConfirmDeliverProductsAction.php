<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\ConfirmDeliverProducts;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\Log;
use App\Enums\LocalMarket\UnitOwnershipAction;
use Illuminate\Support\Facades\DB;

class ConfirmDeliverProductsAction implements ConfirmDeliverProducts
{
    public function __construct(
        private UnitService $unitService
    ) {}

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        DB::beginTransaction();
        try {
            $this->unitService->changeOrderUnitsOwnershipTo(
                $localMarketOrder,
                OwnershipTypes::Customer,
                $this->localMarketOrder->customer_name,
                UnitOwnershipAction::ConfirmedDelivery
            );
            $this->unitService->deleteAssociatedUnits($localMarketOrder);
            $localMarketOrder->update(['status' => LocalMarketOrderStatus::Delivered]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('local_market')->error('Error in ConfirmDeliverProductsAction', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $localMarketOrder->update(['status' => LocalMarketOrderStatus::FailedDelivery]);
        }
    }
}

