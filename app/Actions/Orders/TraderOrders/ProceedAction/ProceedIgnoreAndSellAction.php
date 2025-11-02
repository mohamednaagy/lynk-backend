<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedIgnoreAndSell;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\TraderOrderMode;
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

class ProceedIgnoreAndSellAction implements ProceedIgnoreAndSell
{
    use TraderHelperTrait;

    private TimeLimitService $timeLimitService;

    public function __construct()
    {
        $this->timeLimitService = app(TimeLimitService::class);
    }

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws BindingResolutionException
     */
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::IgnoreAndSell));
        $canProceed = $proceedCaseHandler->canProceed($traderOrder, $forceToProceed);
        if (! $canProceed) {
            throw new OrderStatusDoesNotFollowSequenceException(
                [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                ]
            );
        }
        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::IgnoreAndSell);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::DeliveryCancelled);

        match ($traderOrder->mode) {
            TraderOrderMode::Manual => (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
                ->updateMurabhaCompleteDocument($traderOrder),
            TraderOrderMode::Automatic => Trader::driver($traderOrder->provider, $traderOrder->version)->sellCommodityToLocalMarket($traderOrder),
        };

        $this->timeLimitService->cancelExpiry($traderOrder, TraderOrderTimeLimitType::DeliveryConfirmationTimeLimit);

        return [];
    }
}
