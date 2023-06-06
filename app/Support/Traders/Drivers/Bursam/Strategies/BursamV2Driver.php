<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultNYY;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultYNN;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOtcCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToCustomer;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToLender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BursamV2Driver extends BursamV1Driver
{
    protected $version = 'v2';

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder): ?Model
    {
        if ($financingOrder->initiatedTraderOrders()->exists()) {
            return $financingOrder->initiatedTraderOrders()->first();
        }

        return $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => '',
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
        ]);
    }

    /**
     * @param  TraderOrder  $traderOrder
     * @return void
     */
    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
        $dispatchableJob = match ((int) $traderOrder->last_history_action) {
            FinancingOrderHistory::GetTtiId => ProcessBursamOrderResultYNN::class,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => ProcessBursamBidCertificate::class,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => ProcessBursamTransferOwnershipToLender::class,
            FinancingOrderHistory::ContractSigned => ProcessBursamTransferOwnershipToCustomer::class,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::class,
            FinancingOrderHistory::ClientWakalaAccepted => ProcessBursamSellingCommodityToOpenMarket::class,
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => ProcessBursamOrderResultNYY::class,
            FinancingOrderHistory::CommoditySoldToMarket => ProcessBursamOtcCertificate::class,
            FinancingOrderHistory::GetOwnershipToCustomerCertificate => ProcessBursamStbCertificate::class,
            default => null,
        };

        if ($dispatchableJob) {
            $dispatchableJob::dispatch($traderOrder->id);
        }
    }

    public function isTraderOrderCancellable(TraderOrder $traderOrder)
    {
        $traderHistoryActions = $traderOrder->traderHistories->pluck('action')->toArray();

        return $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)
            || $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);
    }
}
