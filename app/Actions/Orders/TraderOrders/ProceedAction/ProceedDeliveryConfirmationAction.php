<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedDeliveryConfirmation;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Jobs\FinancingOrders\NotifyAdminsAboutOrderDeliveryConfirmed;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class ProceedDeliveryConfirmationAction implements ProceedDeliveryConfirmation
{
    use TraderHelperTrait;

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws BindingResolutionException
     */
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        if (
            $this->isPreviousStepOfCustomerDeliveryConfirmationNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isCustomerDeliveryConfirmationStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        dispatch(new NotifyAdminsAboutOrderDeliveryConfirmed($traderOrder));

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::CustomerDeliveryConfirmation
        );

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::DeliveryConfirmed);

        if ($canUpdateOrderStatus) {
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
        }

        return [];
    }

    protected function isPreviousStepOfCustomerDeliveryConfirmationNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf(MurabhaStep::CustomerDeliveryConfirmation)->step
        );
    }

    protected function isCustomerDeliveryConfirmationStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderHistoryAction([FinancingOrderHistory::DeliveryCancelled, FinancingOrderHistory::DeliveryConfirmed]);
    }
}
