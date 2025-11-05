<?php

namespace App\Validators\TraderOrders\ProceedOrder;

use App\Contracts\TraderOrders\TraderOrderProceedCase;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Support\Facades\Log;

class IgnoreAndSellCase implements TraderOrderProceedCase
{
    public function canProceed(TraderOrder $traderOrder, bool $forceToProceed = false): bool
    {
        if (
            $this->isPreviousStepOfCustomerDeliveryConfirmationNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder))
        ) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('IgnoreAndSellCase: traderOrderId: '.$traderOrder->id.' - can not proceed', $traderOrder), [
                'traderOrderId' => $traderOrder->id,
                'financingOrderId' => $traderOrder->order?->id,
                'forceToProceed' => $forceToProceed,
                'isPreviousStepOfCustomerDeliveryConfirmationNotCompleted' => $this->isPreviousStepOfCustomerDeliveryConfirmationNotCompleted($traderOrder),
                'isCustomerDeliveryConfirmationStepCompleted' => $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder),
            ]);

            return false;
        }

        return true;
    }

    protected function isPreviousStepOfCustomerDeliveryConfirmationNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version, $traderOrder->contract_signed_type))
                ->getPreviousStepOf(MurabhaStep::CustomerDeliveryConfirmation)->step
        );
    }

    protected function isCustomerDeliveryConfirmationStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderHistoryAction([FinancingOrderHistory::DeliveryCancelled, FinancingOrderHistory::DeliveryConfirmed]);
    }
}
