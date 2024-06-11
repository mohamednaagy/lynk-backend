<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamBidCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultNYY;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOrderResultYNN;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamOtcCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamSellingCommodityToOpenMarketForCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificate;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamStbCertificateAfterCancellation;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToCustomer;
use App\Support\Traders\Drivers\Bursam\Jobs\V2\ProcessBursamTransferOwnershipToLender;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;
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
            'mode' => TraderOrderMode::Automatic,
        ]);
    }

    public function getStatusWhenInitaitedNewTradeRequest()
    {
        return TraderOrderStatus::InProgress;
    }

    /**
     * @throws TraderException
     */
    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::Manual
    ): int {
        if ($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CommoditySoldToMarket)) {
            app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason);

            $traderOrder->update([
                'status' => TraderOrderStatus::Cancelled,
                'cancel_reason' => $cancelReason,
            ]);

            return TraderOrderCancellationStatus::Cancelled;
        }

        if ($traderOrder->doesLastActionMatchWith([
            FinancingOrderHistory::GetTtiId, FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
        ])) {
            throw new Exception(sprintf('Trader order (#%s) cannot be cancelled now', $traderOrder->id));
        }

        $traderOrder->update([
            'status' => TraderOrderStatus::PendingCancellation,
        ]);

        Bus::chain([
            new ProcessBursamSellingCommodityToOpenMarketForCancellation($traderOrder->id),
            new ProcessBursamStbCertificateAfterCancellation($traderOrder->id, $cancelReason),
            function () use ($traderOrder) {
                $activeTraderOrdersCount = TraderOrder::where('status', TraderOrderStatus::InProgress)
                    ->where('financing_order_id', $traderOrder->id)
                    ->count();

                if ($activeTraderOrdersCount !== 0) {
                    return;
                }

                $order = $traderOrder->order()->first();

                if ($order->status->is(FinancingOrderStatus::PendingCancellation)) {
                    $order->update([
                        'status' => FinancingOrderStatus::Cancelled,
                    ]);
                }

                if ($order->status->is(FinancingOrderStatus::InProgress)) {
                    $order->update([
                        'status' => FinancingOrderStatus::PendingTraderOrder,
                    ]);
                }
            },
        ])->dispatch();

        return TraderOrderCancellationStatus::PendingCancellation;
    }

    public function dispatchJobForTransitioningFlow(TraderOrder $traderOrder): void
    {
        $lastHistory = (int) $traderOrder->last_history_action;

        $dispatchableJob = match ($traderOrder->mode) {
            TraderOrderMode::Automatic => $this->transitionFlowInAutomaticMode($lastHistory),
            TraderOrderMode::Manual => $this->transitionFlowInManualMode($lastHistory),
            default => null,
        };

        if ($dispatchableJob) {
            $dispatchableJob::dispatch($traderOrder->id);
        }
    }

    protected function transitionFlowInManualMode($lastHistoryAction): ?string
    {
        return match ($lastHistoryAction) {
            FinancingOrderHistory::ContractSigned => ProcessBursamTransferOwnershipToCustomer::class,
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::class,
            default => null,
        };
    }

    protected function transitionFlowInAutomaticMode($lastHistoryAction): ?string
    {
        return match ($lastHistoryAction) {
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
    }

    public function isTraderOrderCancellable(TraderOrder $traderOrder, ?string $area)
    {
        return $this->isNotInTransitionStateForSellingOrBuying($traderOrder)
            && $this->isNotInContractSignedForLenderArea($traderOrder, $area);
    }

    protected function isNotInTransitionStateForSellingOrBuying(TraderOrder $traderOrder)
    {
        return ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetTtiId)
            && ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument);
    }

    protected function isNotInContractSignedForLenderArea(TraderOrder $traderOrder, $area)
    {
        return $area !== Area::Lender
            || ! $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::ContractSigned);
    }
}
