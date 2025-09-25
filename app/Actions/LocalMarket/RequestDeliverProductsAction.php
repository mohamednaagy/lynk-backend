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
                $localMarketOrder->borrower_identifier,
                UnitOwnershipAction::BorrowerOwnershipTransfer
            );
            $localMarketOrder->changeStatusTo(LocalMarketOrderStatus::PendingDelivery);
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle('Delivery requested at RequestDeliverProductsAction', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
            ]);
        } catch (\Exception $e) {
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->error(formatLocalMarketOrderTitle('Failed to request delivery at RequestDeliverProductsAction', $localMarketOrder), [
                'localMarketOrderId' => $localMarketOrder->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
