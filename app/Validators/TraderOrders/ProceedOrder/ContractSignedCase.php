<?php

namespace App\Validators\TraderOrders\ProceedOrder;

use App\Contracts\TraderOrders\TraderOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Support\Facades\Log;

class ContractSignedCase implements TraderOrderProceedCase
{
    public function canProceed(TraderOrder $traderOrder, bool $forceToProceed = false): bool
    {
        if (
            $this->isPreviousStepOfContractSignedNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isContractSignedStepCompleted($traderOrder))
        ) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('ContractSignedCase: traderOrderId: '.$traderOrder->id.' - can not proceed', $traderOrder), [
                'traderOrderId' => $traderOrder->id,
                'financingOrderId' => $traderOrder->order?->id,
                'forceToProceed' => $forceToProceed,
                'isPreviousStepOfContractSignedNotCompleted' => $this->isPreviousStepOfContractSignedNotCompleted($traderOrder),
                'isContractSignedStepCompleted' => $this->isContractSignedStepCompleted($traderOrder),
            ]);

            return false;
        }

        return true;
    }

    protected function isPreviousStepOfContractSignedNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
                ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
        );
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }
}
