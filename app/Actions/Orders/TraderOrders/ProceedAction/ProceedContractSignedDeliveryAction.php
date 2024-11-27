<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSignedDelivery;
use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Enums\Trader;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Contracts\Container\BindingResolutionException;
class ProceedContractSignedDeliveryAction implements ProceedContractSignedDelivery
{
    use TraderHelperTrait;

    private $timeLimitService;

    private const DELIVERY_CONFIRMATION_TIME_LIMIT_IN_HOURS = 72;

    public function __construct(TimeLimitService $timeLimitService)
    {
        $this->timeLimitService = $timeLimitService;
    }

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
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);
        $trader->createSellingCommodityToCustomerDocument($traderOrder);
        if ($traderOrder->isNeedToGenerateWakalaDocument()) {
            app()->make(GenerateClientWakala::class)->handle($traderOrder);
        }
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::PendingDelivery);
        $this->setExpiry($traderOrder);

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

    private function setExpiry(TraderOrder $traderOrder){
        if ($traderOrder->provider === Trader::Lynk){
            $effectiveAt = now()->timezone('UTC')->addHours(self::DELIVERY_CONFIRMATION_TIME_LIMIT_IN_HOURS)->format('Y-m-d H:i:s');
            $this->timeLimitService->setDeliveryConfirmationTimeLimit(traderOrder: $traderOrder, effectiveAt: $effectiveAt, defaultValue: self::DELIVERY_CONFIRMATION_TIME_LIMIT_IN_HOURS);
        }
    }
}
