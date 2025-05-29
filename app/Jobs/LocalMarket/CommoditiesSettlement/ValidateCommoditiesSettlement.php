<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement;

use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\Exceptions\ValidateCommoditiesSettlementException;
use App\Models\LocalMarketOrder;
use Exception;

class ValidateCommoditiesSettlement extends BaseCommoditiesSettlement
{
    public function handle(): void
    {
        try {
            // Retrieve the order and check that all their units are either deleted or sold.
            $order = LocalMarketOrder::query()
                ->select('id')
                ->whereId($this->localMarketOrderId)
                ->where('commodities_settlement_status', CommoditySettlementStatus::PendingSettlement)
                ->whereDoesntHave('orderUnits', function ($query) {
                    $query->whereNull('settlement_status');
                })->first();

            if (! $order) {
                return;
            }

            $this->markOrderAsSettled($order->id);
            ConfirmCommoditiesSettlement::dispatch($order->id);
        } catch (Exception $e) {
            self::logError('ValidateCommoditiesSettlement failed', [
                'order_id' => $this->localMarketOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ValidateCommoditiesSettlementException($e->getMessage());
        }
    }

    private function markOrderAsSettled(int $orderId): void
    {
        LocalMarketOrder::changeCommoditiesSettlementStatus($orderId, CommoditySettlementStatus::CommoditySettled);
    }
}
