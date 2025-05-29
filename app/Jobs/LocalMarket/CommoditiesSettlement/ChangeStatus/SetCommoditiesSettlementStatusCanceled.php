<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus;

use App\Jobs\LocalMarket\CommoditiesSettlement\BaseCommoditiesSettlement;
use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Models\LocalMarketOrder;
use Illuminate\Support\Facades\Log;

class SetCommoditiesSettlementStatusCanceled extends BaseCommoditiesSettlement
{
    public function handle(): void
    {
        Log::channel('local_market')->info('Changing commodities_settlement_status to SettlementCanceled', [
            'order_id' => $this->localMarketOrderId,
        ]);

        LocalMarketOrder::changeCommoditiesSettlementStatus($this->localMarketOrderId, CommoditySettlementStatus::SettlementCanceled);
    }
}
