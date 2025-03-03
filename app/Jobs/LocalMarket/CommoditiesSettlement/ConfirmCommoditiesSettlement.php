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
    public function __construct(private ?int $localMarketOrderId = null)
    {
        parent::__construct();
    }

    public function handle(LocalMarketWebhook $localMarketWebhook): void
    {
        LocalMarketOrder::query()->where('commodities_settlement_status', CommoditySettlementStatus::CommoditySettled)
            ->when($this->localMarketOrderId, fn ($q) => $q->where('id', $this->localMarketOrderId))
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

    /**
     * Unique identifier for job deduplication.
     */
    public function uniqueId(): string
    {
        return $this->localMarketOrderId
            ? __CLASS__.'_'.$this->localMarketOrderId
            : parent::uniqueId();
    }
}
