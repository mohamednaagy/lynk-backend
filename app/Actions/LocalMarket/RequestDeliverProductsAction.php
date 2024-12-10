<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\RequestDeliverProducts;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\UnitService;
use Illuminate\Support\Facades\Log;

class RequestDeliverProductsAction implements RequestDeliverProducts
{
    private UnitService $unitService;

    public function __construct()
    {
        $this->unitService = app(UnitService::class);
    }

    public function handle(LocalMarketOrder $localMarketOrder): void
    {
        try {
            $this->unitService->changeOrderUnitsOwnershipTo(
                $localMarketOrder,
                OwnershipTypes::TraderOrder,
                $localMarketOrder->external_order_no,
                UnitOwnershipAction::SellCommodity
            );
            Log::info("Delivery requested for Trader Order ID: {$localMarketOrder->id}");
        } catch (\Exception $e) {
            Log::error("Failed to request delivery for Trader Order ID: {$localMarketOrder->id}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
