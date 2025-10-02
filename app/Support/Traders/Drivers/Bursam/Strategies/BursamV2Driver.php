<?php

namespace App\Support\Traders\Drivers\Bursam\Strategies;

use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToCancel;
use App\Actions\Contracts\Orders\TraderOrders\UpdateTraderOrderStatusToPendingCancel;
use App\Actions\Contracts\Orders\Webhooks\FireWebhookWhenStatusIsCancelled;
use App\Enums\Area;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\FinancingOrderStatus;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderCancellationStatus;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Exceptions\TraderException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Jobs\General\ProcessProceedClientWakala;
use App\Jobs\General\ProcessProceedContractSigned;
use App\Jobs\TraderOrder\AutoCompleteSell\ProcessAutoCompleteSell;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Models\User;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BursamV2Driver extends BursamV1Driver
{
    protected $version = 'v2';

    public function getOrInitiateTraderOrder(FinancingOrder $financingOrder, ?int $preferredCommodityTypeId = null): ?Model
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
            'creator_id' => auth()?->user()?->id,
            'commodity_type_id' => $preferredCommodityTypeId,
        ]);

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
        $this->validateOrderCanBeCancelled($traderOrder);

        $this->handleCancellationProcess(
            $traderOrder,
            $cancelReason,
            $cancelledByType,
            $cancelledBy
        );

        return TraderOrderCancellationStatus::Cancelled;

    }

    private function validateOrderCanBeCancelled(TraderOrder $traderOrder): void
    {
        if (! $traderOrder->canBeCancelled()) {
            throw new Exception(sprintf('Trader order (#%s) cannot be cancelled now', $traderOrder->id));
        }
    }

    private function handleCancellationProcess(
        TraderOrder $traderOrder,
        int $cancelReason,
        int $cancelledByType,
        ?User $cancelledBy
    ): void {
        $this->updateTraderOrderToPendingCancellation($traderOrder, $cancelReason, $cancelledByType, $cancelledBy);

        if ($this->requiresSellingBeforeCancellation($traderOrder)) {
            $this->sellCommoditiesBeforeCancellation($traderOrder, $cancelReason, $cancelledByType, $cancelledBy);
        } else {
            $this->finalizeCancellation($traderOrder, $cancelReason);
        }
    }

    private function updateTraderOrderToPendingCancellation(
        TraderOrder $traderOrder,
        int $cancelReason,
        int $cancelledByType,
        ?User $cancelledBy
    ): void {
        app(UpdateTraderOrderStatusToPendingCancel::class)->handle(
            $traderOrder,
            $cancelReason,
            cancelledByType: $cancelledByType,
            cancelledBy: $cancelledBy
        );
    }

    private function finalizeCancellation(TraderOrder $traderOrder, int $cancelReason): void
    {
        app(UpdateTraderOrderStatusToCancel::class)->handle($traderOrder, $cancelReason);
        $this->updateFinancingOrderStatus($traderOrder->order);
        app(FireWebhookWhenStatusIsCancelled::class)->handle($traderOrder);
    }

    private function requiresSellingBeforeCancellation(TraderOrder $traderOrder): bool
    {
        return $traderOrder->mode === TraderOrderMode::Automatic && ($traderOrder->checkOrderHistoryAction(FinancingOrderHistory::CommoditySoldToMarket) || $traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity));
    }

    private function sellCommoditiesBeforeCancellation(
        TraderOrder $traderOrder,
        int $cancelReason,
        int $cancelledByType,
        ?User $cancelledBy
    ): void {
        Bus::chain([
            new ProcessBursamSellingCommodityToOpenMarketForCancellation($traderOrder->id),
            new ProcessBursamStbCertificateAfterCancellation($traderOrder->id, $cancelReason, $cancelledByType, $cancelledBy),
            function () use ($traderOrder, $cancelReason) {
                $this->finalizeCancellation($traderOrder, $cancelReason);
            },
        ])->dispatch();
    }

    public function updateFinancingOrderStatus(FinancingOrder $financingOrder): void
    {
        if ($financingOrder->status->is(FinancingOrderStatus::PendingCancellation)) {
            $financingOrder->update([
                'status' => FinancingOrderStatus::Cancelled,
            ]);
        }

        if ($financingOrder->status->is(FinancingOrderStatus::InProgress) && $financingOrder->activeTraderOrder()->count() === 0) {
            $financingOrder->update([
                'status' => FinancingOrderStatus::PendingTraderOrder,
            ]);
        }
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
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAutoCompleteSell::class,
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
        if ($traderOrder->isTraderManualAndPurchaseStepNotComplete()) {
            return true;
        }

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
        log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('processProceedContractAndClientWakala', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $traderOrder->id,
        ]);

        if (app(TraderOrderProceedCaseService::class)->getLatestCase($traderOrder->id)->value != FinancingOrderProceedCase::ContractSigned) {
            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('we will fire ProcessProceedContractSigned Job', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
            ProcessProceedContractSigned::dispatch($traderOrder->id);
        }

        if ($this->shouldProcessClientWakala($traderOrder)) {
            log::channel(LOG_CHANNEL_BURSAM)->info(formatLogTitle('we will fire ProcessProceedClientWakala Job', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
            ProcessProceedClientWakala::dispatch($traderOrder->id);
        }
    }

    public function shouldProcessClientWakala($traderOrder)
    {
        return app(TraderOrderProceedCaseService::class)->checkIfTraderHasCase($traderOrder->id, FinancingOrderProceedCase::ContractAndClientWakalaCompleted) &&
                app(TraderOrderProceedCaseService::class)->checkIfTraderHasCase($traderOrder->id, FinancingOrderProceedCase::ContractSigned) &&
                ! app(TraderOrderProceedCaseService::class)->checkIfTraderHasCase($traderOrder->id, FinancingOrderProceedCase::ClientWakalaAccepted);
    }

    public function contractSignedMessage(TraderOrder $traderOrder): ?string
    {
        if (! $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned)) {
            return null;
        }
        $traderOrderProceedCaseService = app(TraderOrderProceedCaseService::class);

        $contractAndClientWakalaCompletedCase = $traderOrderProceedCaseService->getCreatorNameForCase(
            $traderOrder->id,
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted
        );

        if ($contractAndClientWakalaCompletedCase) {
            return str_replace('{USER}', $contractAndClientWakalaCompletedCase, __('order.trader.bursa.steps.contract_signed.v2.wakalaAndSell'));
        }

        $contractSignedCase = $traderOrderProceedCaseService->getCreatorNameForCase(
            $traderOrder->id,
            FinancingOrderProceedCase::ContractSigned
        );

        return str_replace('{USER}', $contractSignedCase, __('order.trader.bursa.steps.contract_signed.v2.proceed'));
    }

    public function clientWakalaMessage(TraderOrder $traderOrder): ?string
    {
        if (! $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala)) {
            return null;
        }

        $traderOrderProceedCaseService = app(TraderOrderProceedCaseService::class);

        // Prefer the more specific case; only query the fallback if needed
        $contractAndClientWakalaCompletedCase = $traderOrderProceedCaseService->getCreatorNameForCase(
            $traderOrder->id,
            FinancingOrderProceedCase::ContractAndClientWakalaCompleted
        );

        if ($contractAndClientWakalaCompletedCase) {
            return str_replace('{USER}', $contractAndClientWakalaCompletedCase, __('order.trader.bursa.steps.client_wakala.v2.wakalaAndSell'));
        }

        $clientWakalaAcceptedCase = $traderOrderProceedCaseService->getCreatorNameForCase(
            $traderOrder->id,
            FinancingOrderProceedCase::ClientWakalaAccepted
        );

        return str_replace('{USER}', $clientWakalaAcceptedCase, __('order.trader.bursa.steps.client_wakala.v2.sell'));
    }

    public function confirmCancelledFromProvider(TraderOrder $traderOrder): void {}
}
