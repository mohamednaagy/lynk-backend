<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamOrderResult;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamTransferOwnershipToCustomer;
use App\Support\Traders\Drivers\Bursam\Jobs\ProcessBursamTransferOwnershipToLender;

class BursamV2Driver extends BursamV1Driver
{
    /**
     * @param  TraderOrder  $traderOrder
     * @return void
     */
    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
        match ((int) $traderOrder->last_history_action) {
            FinancingOrderHistory::GetTtiId => ProcessBursamOrderResult::dispatch($traderOrder),
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => ProcessBursamBidCertificate::dispatch($traderOrder),
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => ProcessBursamTransferOwnershipToLender::dispatch($traderOrder),
            FinancingOrderHistory::ContractSigned => ProcessBursamTransferOwnershipToCustomer::dispatch($traderOrder),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => ProcessBursamSellingCommodityToOpenMarket::dispatch($traderOrder),
            default => null,
        };
    }
}
