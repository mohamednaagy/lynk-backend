<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\RequestDeliverProducts;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Enums\LocalMarketOrderStatus;
use App\Services\LocalMarket\UnitService;
use App\Traits\LocalMarket\LocalMarketTrait;
use Illuminate\Support\Facades\Log;

class RequestDeliverProductsAction implements RequestDeliverProducts
{
    use LocalMarketTrait;

    private UnitService $unitService;

    public function __construct()
    {
        $this->unitService = app(UnitService::class);
    }

    public function handle(string $reference): void
    {
        $localMarketOrder = $this->getLocalMarketOrderByReference($reference);
        try {
            $this->unitService->changeOrderUnitsOwnershipTo(
                $localMarketOrder,
                OwnershipTypes::Customer,
                $localMarketOrder->customer_name,
                UnitOwnershipAction::BorrowerOwnershipTransfer
            );
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingDelivery);
            Log::channel('local_market')->info("Delivery requested for Local Market Order ID: {$localMarketOrder->id}");
        } catch (\Exception $e) {
            Log::channel('local_market')->error("Failed to request delivery for Local Market Order ID: {$localMarketOrder->id}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
