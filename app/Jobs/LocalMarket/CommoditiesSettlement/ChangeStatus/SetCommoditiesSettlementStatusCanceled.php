<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus;

use App\Jobs\LocalMarket\CommoditiesSettlement\BaseCommoditiesSettlement;
use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Models\LocalMarketOrder;

class SetCommoditiesSettlementStatusCanceled extends BaseCommoditiesSettlement
{
    public function handle(): void
    {
        $this->logInfo('Changing commodities_settlement_status to SettlementCanceled', [
            'order_id' => $this->localMarketOrderId,
        ]);

        try {
            LocalMarketOrder::changeCommoditiesSettlementStatus(
                $this->localMarketOrderId,
                CommoditySettlementStatus::SettlementCanceled
            );
        } catch (\Throwable $e) {
            $this->logError('Failed to set commodities_settlement_status to SettlementCanceled.', [
                'order_id' => $this->localMarketOrderId,
                'error_message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

    }
}
