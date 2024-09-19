<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedIgnoreAndSell;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\ContractSignedType;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
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
        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);
        $trader->sellCommodityToOpenMarket($traderOrder);

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
