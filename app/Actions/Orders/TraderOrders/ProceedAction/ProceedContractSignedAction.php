<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSigned;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Support\Traders\Facades\Trader;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class ProceedContractSignedAction implements ProceedContractSigned
{
    use TraderHelperTrait;

    public function __construct() {}

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws BindingResolutionException
     */
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        if (
            $this->isPreviousStepOfContractSignedNotCompleted($traderOrder)
            || ($forceToProceed === false && $this->isContractSignedStepCompleted($traderOrder))
        ) {
            throw new OrderStatusDoesNotFollowSequenceException;
        }

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        Trader::driver($traderOrder->provider, $traderOrder->version)->processProceedContractSigned($traderOrder);

        return [];
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
