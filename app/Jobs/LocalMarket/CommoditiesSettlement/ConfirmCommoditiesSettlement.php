<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Jobs\LocalMarket\CommoditiesSettlement\Exceptions\ConfirmCommoditiesSettlementException;
use App\Jobs\LocalMarket\LynkWebhooks\CommoditiesSettledWebhook;
use App\Models\LocalMarketOrder;
use Exception;

class ConfirmCommoditiesSettlement extends BaseCommoditiesSettlement
{
    public function handle(): void
    {
        LocalMarketOrder::query()->where('commodities_settlement_status', CommoditySettlementStatus::CommoditySettled)
            ->where('id', $this->localMarketOrderId)
            ->chunkById(self::CHUNK_SIZE, function ($orders) {
                try {
                    foreach ($orders as $order) {
                        CommoditiesSettledWebhook::dispatch($order->id);
                        LocalMarketOrder::changeCommoditiesSettlementStatus($order->id, CommoditySettlementStatus::SettlementConfirmed);
                    }
                } catch (Exception $e) {
                    self::logError('ConfirmCommoditiesSettlement failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    LocalMarketOrder::changeCommoditiesSettlementStatus($order->id, CommoditySettlementStatus::SettlementFailed);

                    throw new ConfirmCommoditiesSettlementException($e->getMessage());
                }

            });
    }
}
