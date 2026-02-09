<?php

namespace App\Validators\TraderOrders\ProceedOrder;

use App\Contracts\TraderOrders\TraderOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Enums\Trader as EnumTrader;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Support\Facades\Log;

class ContractAndClientWakalaCompletedCase implements TraderOrderProceedCase
{
    /**
     * @var array<string, string>
     */
    private array $requiredStepForProcessedTraderOrder = [
        EnumTrader::Lynk => MurabhaStep::ContractSigned,
        EnumTrader::Bursam => MurabhaStep::ContractSigned,
        EnumTrader::FakeDmcc => MurabhaStep::ClientWakala,
        EnumTrader::Dmcc => MurabhaStep::ClientWakala,
    ];

    public function canProceed(TraderOrder $traderOrder, bool $forceToProceed = false): bool
    {
        if ($this->isPreviousStepOfContractAndClientWakalaNotCompleted($traderOrder) || $this->isContractSigned($traderOrder) || is_null($traderOrder->last_history_action)) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('ContractAndClientWakalaCompletedCase: traderOrderId: '.$traderOrder->id.' - can not proceed', $traderOrder), [
                'traderOrderId' => $traderOrder->id,
                'financingOrderId' => $traderOrder->order?->id,
                'forceToProceed' => $forceToProceed,
                'isPreviousStepOfContractAndClientWakalaNotCompleted' => $this->isPreviousStepOfContractAndClientWakalaNotCompleted($traderOrder),
                'lastHistoryAction' => $traderOrder->last_history_action,
            ]);

            return false;
        }

        return true;
    }

    protected function isPreviousStepOfContractAndClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $murabhaSteps = array_keys(get_murabha_steps($traderOrder->provider, $traderOrder->version));
        $stepIndex = array_search($this->requiredStepForProcessedTraderOrder[$traderOrder->provider], $murabhaSteps);

        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
                ->getPreviousStepOf($murabhaSteps[$stepIndex])->step
        );
    }

    protected function isContractSigned(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }
}
