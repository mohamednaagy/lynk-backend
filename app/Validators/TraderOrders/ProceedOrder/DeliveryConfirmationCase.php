<?php

namespace App\Validators\TraderOrders\ProceedOrder;

use App\Contracts\TraderOrders\TraderOrderProceedCase;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Models\TraderOrder;
use Illuminate\Support\Facades\Log;

class DeliveryConfirmationCase implements TraderOrderProceedCase
{
    public function canProceed(TraderOrder $traderOrder, bool $forceToProceed = false): bool
    {

        if ($traderOrder->isPreviousStepNotCompleted(MurabhaStep::CustomerDeliveryConfirmation)
        || (! $forceToProceed && $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder))) {
            Log::channel(getSuitableLoggingFromTraderProvider($traderOrder))->error(formatLogTitle('DeliveryConfirmationCase: traderOrderId: '.$traderOrder->id.' - can not proceed', $traderOrder), [
                'traderOrderId' => $traderOrder->id,
                'financingOrderId' => $traderOrder->order?->id,
                'forceToProceed' => $forceToProceed,
                'isPreviousStepNotCompleted' => $traderOrder->isPreviousStepNotCompleted(MurabhaStep::CustomerDeliveryConfirmation),
                'isCustomerDeliveryConfirmationStepCompleted' => $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder),
            ]);

            return false;
        }

        return true;
    }

    protected function isCustomerDeliveryConfirmationStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderHistoryAction([FinancingOrderHistory::DeliveryCancelled, FinancingOrderHistory::DeliveryConfirmed]);
    }
}
