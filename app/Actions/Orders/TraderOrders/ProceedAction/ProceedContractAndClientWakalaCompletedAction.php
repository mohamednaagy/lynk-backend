<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractAndClientWakalaCompleted;
use App\Enums\MurabhaStep;
use App\Enums\Trader as EnumTrader;
use App\Exceptions\OrderRequiresClientVerification;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;

class ProceedContractAndClientWakalaCompletedAction implements ProceedContractAndClientWakalaCompleted
{
    use TraderHelperTrait;

    private $requiredStepForProccessedTraderOrder = [
        EnumTrader::Lynk => MurabhaStep::ContractSigned,
        EnumTrader::Bursam => MurabhaStep::ContractSigned,
        EnumTrader::FakeDmcc => MurabhaStep::ClientWakala,
        EnumTrader::Dmcc => MurabhaStep::ClientWakala,
    ];

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws OrderRequiresClientVerification
     */
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        $order = FinancingOrder::query()
            ->lockForUpdate()
            ->findOrFail($traderOrder->financing_order_id);

        if ($order->is_verification_required) {
            throw new OrderRequiresClientVerification;
        }

        $currenttraderOrderStatus = $traderOrder->traderHistories()->latest('id')->first();

        if ($this->isPreviousStepOfContractAndClientWakalaNotCompleted($traderOrder) || is_null($currenttraderOrderStatus)) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        Trader::driver($traderOrder->provider, $traderOrder->version)->processProceedContractAndClientWakala($traderOrder);

        return [];

    }

    protected function isPreviousStepOfContractAndClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $murabhaSteps = array_keys(get_murabha_steps($traderOrder->provider, $traderOrder->version));
        $stepIndex = array_search($this->requiredStepForProccessedTraderOrder[$traderOrder->provider], $murabhaSteps);

        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf($murabhaSteps[$stepIndex])->step
        );

    }
}
