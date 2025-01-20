<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSignedDelivery;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderTimeLimitType;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;

class ProceedContractSignedDeliveryAction implements ProceedContractSignedDelivery
{
    use TraderHelperTrait;

    public function __construct(protected TimeLimitService $timeLimitService) {}

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

        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
            ->requestDeliverCommodityToCustomer($traderOrder);

        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        // use it to complete sequence of steps ( use it in ProcessFinancingOrders)
        $traderOrder->allowProgressToNextStep();

        $trader->createSellingCommodityToCustomerDocument($traderOrder);
        if ($traderOrder->isNeedToGenerateWakalaDocument()) {
            app()->make(GenerateClientWakala::class)->handle($traderOrder);
        }
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::PendingDelivery);

        $this->timeLimitService->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);

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
