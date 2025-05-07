<?php

namespace App\Support\Traders\Drivers\Lynk\Strategies;

use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderTimeLimitType;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Jobs\General\ProcessAskClientForWakala;
use App\Jobs\General\ProcessProceedContractAndClientWakala;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;

class LynkV2Driver extends LynkV1Driver
{
    protected $provider = 'lynk';

    protected $version = 'v2';

    protected function transitionFlowInManualMode(TraderOrder $traderOrder, int $lastHistoryAction): void
    {
        match ($lastHistoryAction) {
            FinancingOrderHistory::ContractSigned => $this->createSellingCommodityToCustomerDocument($traderOrder),
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => ProcessAskClientForWakala::dispatch($traderOrder->id),
            FinancingOrderHistory::ClientWakalaAccepted => $this->handleManualSellTransition($traderOrder),
            default => null,
        };
    }

    public function contractSignedMessage(TraderOrder $traderOrder)
    {
        return __('order.trader.lynk.steps.contract_signed.v2.proceed');
    }

    public function clientWakalaMessage(TraderOrder $traderOrder)
    {
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
            (!$forceToProceed && $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder));

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

    public function processProceedContractAndClientWakala(TraderOrder $traderOrder)
    {
        ProcessProceedContractAndClientWakala::dispatchSync($traderOrder->id);
    }
}
