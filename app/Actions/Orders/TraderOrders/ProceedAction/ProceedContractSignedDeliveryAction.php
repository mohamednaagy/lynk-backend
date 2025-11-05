<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSignedDelivery;
use App\Enums\ContractSignedType;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\TraderOrderTimeLimitType;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Factories\TraderOrders\TraderOrderProceedCaseFactory;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TimeLimitService;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
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
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): void
    {
        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(FinancingOrderProceedCase::ContractSignedDelivery);

        if (! $proceedCaseHandler->canProceed($traderOrder, $forceToProceed)) {
            throw new OrderStatusDoesNotFollowSequenceException([
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
        }

        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ContractSignedDelivery);

        $traderOrder->update(['contract_signed_type' => ContractSignedType::Delivery]);

        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
            ->requestDeliverCommodityToCustomer($traderOrder);

        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        $traderOrder->allowProgressToNextStep();

        $trader->createSellingCommodityToCustomerDocument($traderOrder);
        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::PendingDelivery);

        $this->timeLimitService->cancelExpiry($traderOrder, TraderOrderTimeLimitType::ContractSignTimeLimit);
    }
}
