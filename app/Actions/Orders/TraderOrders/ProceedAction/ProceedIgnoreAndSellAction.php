<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedIgnoreAndSell;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class ProceedIgnoreAndSellAction implements ProceedIgnoreAndSell
{
    use TraderHelperTrait;

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws BindingResolutionException
     */
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        if (
            $this->isPreviousStepOfIgnoreAndSellNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isContractSignedStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::DeliveryCancelled);

        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
            ->updateMurabhaCompleteDocument($traderOrder);

        return [];
    }

    protected function isPreviousStepOfIgnoreAndSellNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf(MurabhaStep::CustomerDeliveryConfirmation)->step
        );
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }
}
