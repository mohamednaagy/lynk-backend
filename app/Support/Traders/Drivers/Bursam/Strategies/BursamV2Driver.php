<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Jobs\General\ProcessProceedContractAndClientWakala;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
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

        $traderOrder = $financingOrder->traderOrders()->create([
            'uuid_one' => Str::uuid(),
            'provider' => $this->provider,
            'reference' => '',
            'status' => TraderOrderStatus::Initiated,
            'version' => $this->version,
            'mode' => TraderOrderMode::Automatic,
        ]);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::GetTtiId);

        return $traderOrder;
    }

    public function getDefaultInitialTradeOrderStatus()
    {
        return TraderOrderStatus::InProgress;
    }

    /**
     * @throws TraderException
     */
    public function cancelTraderOrder(
        TraderOrder $traderOrder,
        int $cancelReason = TraderOrderCancelReason::TraderOrderIsCancelled,
        $cancelledByType = TraderOrderCancelType::System,
        ?User $cancelledBy = null
    ): int {
        $user = auth()->check() ? auth()->user() : null;
        if ($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CommoditySoldToMarket) || $traderOrder->checkOrderHistoryAction(FinancingOrderHistory::OnHold)) {
            app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, $cancelReason, cancelledByType: $cancelledByType, cancelledBy: $cancelledBy);
            app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason);

            return TraderOrderCancellationStatus::Cancelled;
        }

        if ($traderOrder->doesLastActionMatchWith([
            FinancingOrderHistory::GetTtiId, FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
        ])) {
            throw new Exception(sprintf('Trader order (#%s) cannot be cancelled now', $traderOrder->id));
        }

        app(UpdateTraderOrderStatusToPendingCancel::class)->handle($traderOrder, $cancelReason, cancelledByType: $cancelledByType, cancelledBy: $cancelledBy);

        Bus::chain([
            new ProcessBursamSellingCommodityToOpenMarketForCancellation($traderOrder->id),
            new ProcessBursamStbCertificateAfterCancellation($traderOrder->id, $cancelReason, $cancelledByType, $cancelledBy),
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

        if ($traderOrder->status->isNot(TraderOrderStatus::InProgress) && $traderOrder->status->isNot(TraderOrderStatus::Hold)) {
            return false;
        }

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

    /**
     * @return string <Driver>_<trader_orders.reference_number>.pdf
     */
    public function generatePdfFileName($traderOrder, $collectionName): string
    {
        return $traderOrder->provider.'-'.$traderOrder->reference.'.pdf';
    }

    // use it in public api to proceed order after purchasing commodity step by one step
    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);
    }

    public function contractSignedMessage(TraderOrder $traderOrder)
    {
        return null;
    }

    public function retryOrder(TraderOrder $traderOrder)
    {
        $traderOrder->order->update(['status' => FinancingOrderStatus::Approved]);
    }

    public function confirmCancelledFromProvider(TraderOrder $traderOrder): void {}
}
