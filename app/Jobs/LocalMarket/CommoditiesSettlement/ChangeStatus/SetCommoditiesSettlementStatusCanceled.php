<?php

namespace App\Jobs\LocalMarket\CommoditiesSettlement\ChangeStatus;

use App\Jobs\LocalMarket\CommoditiesSettlement\BaseCommoditiesSettlement;
use App\Jobs\LocalMarket\CommoditiesSettlement\Enums\CommoditySettlementStatus;
use App\Models\LocalMarketOrder;

class SetCommoditiesSettlementStatusCanceled extends BaseCommoditiesSettlement
{
    public function __construct(private int $localMarketOrderId)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        LocalMarketOrder::changeCommoditiesSettlementStatus($this->localMarketOrderId, CommoditySettlementStatus::SettlementCanceled);
    }
}
