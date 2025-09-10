<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\ConfirmDeliverProducts;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Enums\LocalMarketOrderStatus;
use App\Services\LocalMarket\InventoryService;
use App\Services\LocalMarket\UnitService;
use App\Traits\LocalMarket\LocalMarketTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmDeliverProductsAction implements ConfirmDeliverProducts
{
    use LocalMarketTrait;

    private InventoryService $inventoryService;

    private UnitService $unitService;

    public function __construct(
    ) {
        $this->inventoryService = app(InventoryService::class);
        $this->unitService = app(UnitService::class);
    }

    public function handle(string $reference): void
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
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
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Error at ConfirmDeliverProductsAction', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::FailedDelivery);
        }
    }
}
