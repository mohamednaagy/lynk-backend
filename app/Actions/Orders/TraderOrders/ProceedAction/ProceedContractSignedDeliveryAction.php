<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSignedDelivery;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\ContractSignedType;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class ProceedContractSignedDeliveryAction implements ProceedContractSignedDelivery
{
    use TraderHelperTrait;

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
        $traderOrder->update(['contract_signed_type' => ContractSignedType::Delivery]);
        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);
        $trader->createSellingCommodityToCustomerDocument($traderOrder);
        if ($traderOrder->isNeedToGenerateWakalaDocument()) {
            app()->make(GenerateClientWakala::class)->handle($traderOrder);
        }

        return [];
    }

    protected function isPreviousStepOfContractSignedNotCompleted(TraderOrder $traderOrder): bool
    {
        return ! $traderOrder->checkOrderStepComplete(
            (new StepHistoriesDictionary($traderOrder->provider, $traderOrder->version))
                ->getPreviousStepOf(MurabhaStep::ContractSigned)->step
        );
    }

    protected function isContractSignedStepCompleted(TraderOrder $traderOrder): bool
    {
        return $traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned);
    }
}
