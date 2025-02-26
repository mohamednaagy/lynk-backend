<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement;

use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Models\LocalMarketOrder;

class DispatchOrderSettlementCheck extends BaseCommoditiesSettlement
{
    public function __construct(private ?int $inventoryId = null)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        LocalMarketOrder::query()->where('commodities_settlement_status', CommoditySettlementStatus::PendingSettlement)
            ->chunkById(self::CHUNK_SIZE, function ($orders) {
                foreach ($orders as $order) {
                    CheckOrderUnitSettlement::dispatch($order->id, $this->inventoryId);
                }
            });
    }

    public function uniqueId(): string
    {
        return $this->inventoryId ?
            __CLASS__.'_'.$this->inventoryId
            : parent::uniqueId();
    }
}
