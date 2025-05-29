<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus;

use App\Jobs\LocalMarket\CommoditiesSettlement\BaseCommoditiesSettlement;
use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Models\LocalMarketOrder;

class SetCommoditiesSettlementStatusPending extends BaseCommoditiesSettlement
{
    public function handle(): void
    {
        $this->logInfo('Changing commodities_settlement_status to PendingSettlement', [
            'order_id' => $this->localMarketOrderId,
        ]);

        try {
            LocalMarketOrder::changeCommoditiesSettlementStatus(
                $this->localMarketOrderId,
                CommoditySettlementStatus::PendingSettlement
            );
        } catch (\Throwable $e) {
            $this->logError('Failed to set commodities_settlement_status to PendingSettlement.', [
                'order_id' => $this->localMarketOrderId,
                'error_message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }

    }
}
