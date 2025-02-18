<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\ConfirmDeliverProducts;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmDeliverProductsAction implements ConfirmDeliverProducts
{
    private InventoryService $inventoryService;

    private UnitService $unitService;

    public function __construct(
    ) {
        $this->inventoryService = app(InventoryService::class);
        $this->unitService = app(UnitService::class);
    }

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        DB::beginTransaction();
        try {
            $this->unitService->changeOrderUnitsOwnershipTo(
                $localMarketOrder,
                OwnershipTypes::TraderOrder,
                $localMarketOrder->external_order_no,
                UnitOwnershipAction::DeliverCommodity
            );
            $this->inventoryService->confirmDeliverOrderUnits($localMarketOrder);
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::Delivered);
            $localMarketOrder->skipSellConfirmationCertificate();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('local_market')->error('Error in ConfirmDeliverProductsAction', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::FailedDelivery);
        }
    }
}
