<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResult;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToCustomer;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToLender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BursamV2Driver extends BursamV1Driver
{
    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->initiateTraderOrder()->exists()) {
            return $financingOrder->initiateTraderOrder()->first();
        }

        return $financingOrder->traderOrders()->create([
            'uuid' => Str::uuid(),
            'provider' => 'bursam',
            'status' => TraderOrderStatus::Initiated,
            'version' => 'v2',
        ]);
    }

    /**
     * @param  TraderOrder  $traderOrder
     * @return void
     */
    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
        match ((int) $traderOrder->last_history_action) {
            FinancingOrderHistory::GetTtiId => ProcessBursamOrderResult::dispatch($traderOrder->id),
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => ProcessBursamBidCertificate::dispatch($traderOrder->id),
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => ProcessBursamTransferOwnershipToLender::dispatch($traderOrder->id),
            FinancingOrderHistory::ContractSigned => ProcessBursamTransferOwnershipToCustomer::dispatch($traderOrder->id),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => ProcessBursamSellingCommodityToOpenMarket::dispatch($traderOrder->id),
            default => null,
        };
    }
}
