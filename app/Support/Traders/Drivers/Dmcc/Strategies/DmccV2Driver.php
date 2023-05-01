<?php

namespace App\Support\Traders\Drivers\Dmcc\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccMpoOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccRespondedToPtpOrder;
use App\Support\Traders\Drivers\Dmcc\Jobs\V1\ProcessDmccSellingCommodityToCustomerOrder;

class DmccV2Driver extends DmccV1Driver
{
    public function jobDispatch(TraderOrder $traderOrder)
    {
        match ((int) $traderOrder->last_history_action) {
            FinancingOrderHistory::RespondPtp => ProcessDmccRespondedToPtpOrder::dispatch($traderOrder->id),
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ContractSigned => ProcessDmccSellingCommodityToCustomerOrder::dispatch($traderOrder->id),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessDmccMpoOrder::dispatch($traderOrder->id),
            default => null,
        };
    }
}
