<?php

namespace App\Validators\TraderOrders\ProceedOrder;

use App\Contracts\TraderOrders\TraderOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Support\Facades\Log;

class ClientWakalaAcceptedCase implements TraderOrderProceedCase
{
    public function canProceed(TraderOrder $traderOrder, bool $forceToProceed = false): bool
    {
        $order = $traderOrder->order;

        if (
            $this->isPreviousStepOfClientWakalaNotCompleted($traderOrder)
            || ($forceToProceed === false && $order->is_verification_required)
            || ($forceToProceed === false && $this->isClientWakalaStepCompleted($traderOrder))
        ) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('ClientWakalaAcceptedCase: traderOrderId: '.$traderOrder->id.' - can not proceed', $traderOrder), [
                'traderOrderId' => $traderOrder->id,
                'financingOrderId' => $order?->id,
                'forceToProceed' => $forceToProceed,
                'is_verification_required' => $order->is_verification_required,
                'isClientWakalaStepCompleted' => $this->isClientWakalaStepCompleted($traderOrder),
                'isPreviousStepOfClientWakalaNotCompleted' => $this->isPreviousStepOfClientWakalaNotCompleted($traderOrder),
            ]);

            return false;
        }

        return true;
    }

    protected function isPreviousStepOfClientWakalaNotCompleted(TraderOrder $traderOrder): bool
    {
        $previousStep = (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
            ->getPreviousStepOf(MurabhaStep::ClientWakala)->step;

        return ! $traderOrder->checkOrderStepComplete($previousStep);
    }

    protected function isClientWakalaStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakala);
    }
}
