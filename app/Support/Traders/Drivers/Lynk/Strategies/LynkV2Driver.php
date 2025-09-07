<?php

namespace App\Support\Traders\Drivers\Lynk\Strategies;

use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderTimeLimitType;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Jobs\General\ProcessProceedClientWakala;
use App\Jobs\General\ProcessProceedContractSigned;
use App\Jobs\TraderOrder\AutoCompleteSell\ProcessAutoCompleteSell;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkSellingCommodityToOpenMarket;
use App\Support\Traders\Drivers\Lynk\Jobs\ProcessLynkTransferOwnershipToCustomer;
use Illuminate\Support\Facades\Log;

class LynkV2Driver extends LynkV1Driver
{
    protected $provider = 'lynk';

    protected $version = 'v2';

    protected function transitionFlowInAutomaticMode(TraderOrder $traderOrder, int $lastHistoryAction): void
    {
        match ($lastHistoryAction) {
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => ProcessAutoCompleteSell::dispatch($traderOrder->id),
            FinancingOrderHistory::ContractSigned => ProcessLynkTransferOwnershipToCustomer::dispatch($traderOrder->id),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => ProcessLynkSellingCommodityToOpenMarket::dispatch($traderOrder->id),
            default => null,
        };
    }

    protected function transitionFlowInManualMode(TraderOrder $traderOrder, int $lastHistoryAction): void
    {
        match ($lastHistoryAction) {
            FinancingOrderHistory::ContractSigned => $this->createSellingCommodityToCustomerDocument($traderOrder),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => $this->handleManualSellTransition($traderOrder),
            default => null,
        };
    }

    public function contractSignedMessage(TraderOrder $traderOrder): ?string
    {
        if (! $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned)) {
            return null;
        }

        return $this->isContractAndWakalaCompleted($traderOrder)
            ? __('order.trader.lynk.steps.contract_signed.v2.wakalaAndSell')
            : __('order.trader.lynk.steps.contract_signed.v2.proceed');
    }

    public function clientWakalaMessage(TraderOrder $traderOrder): ?string
    {
        if (! $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala)) {
            return null;
        }

        if ($this->isContractAndWakalaCompleted($traderOrder)) {
            return __('order.trader.lynk.steps.client_wakala.v2.wakalaAndSell');
        }

        return match ($traderOrder->contract_signed_type->value) {
            ContractSignedType::Sell => __('order.trader.lynk.steps.client_wakala.v2.sell'),
            ContractSignedType::Delivery => __('order.trader.lynk.steps.client_wakala.v2.deliver'),
            default => null,
        };
    }

    public function validateDeliverySequence(TraderOrder $traderOrder, bool $forceToProceed): void
    {
        $invalidSequence =
            $traderOrder->isPreviousStepNotCompleted(MurabhaStep::ClientWakala) ||
            (! $forceToProceed && $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder));

        if ($invalidSequence) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }
    }

    public function confirmDelivery(TraderOrder $traderOrder): bool
    {
        $traderOrder->update(['contract_signed_type' => ContractSignedType::Delivery]);
        app(TimeLimitService::class)->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);

        return $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::ClientWakala
        );
    }

    // TODO: This can be refactored later once the BaseTrader class is added.
    //       The logic will then be implemented there, accepting $action as a second argument.
    public function isOrderInSellableState(TraderOrder $traderOrder): bool
    {
        return $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::ClientWakalaAccepted);
    }

    public function isContractSignLimitEligibleForExpiry(TraderOrder $traderOrder): bool
    {
        return $traderOrder->doesLastActionMatchWith([FinancingOrderHistory::CreateTransferOwnershipToLenderDocument, FinancingOrderHistory::WaitingClientWakala]);
    }

    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('processProceedContractAndClientWakala', $traderOrder), [
            'financingOrderId' => $traderOrder->financing_order_id,
            'traderOrderId' => $traderOrder->id,
        ]);
        if (app(TraderOrderProceedCaseService::class)->getLatestCase($traderOrder->id)->value != FinancingOrderProceedCase::ContractSigned) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('We Will Fire ContractSigned Job', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
            ProcessProceedContractSigned::dispatch($traderOrder->id);
        }

        if (app(TraderOrderProceedCaseService::class)->getLatestCase($traderOrder->id)->value == FinancingOrderProceedCase::ContractSigned) {
            log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLogTitle('We Will Fire ProcessProceedClientWakala Job', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
            ProcessProceedClientWakala::dispatch($traderOrder->id);
        }
    }
}
