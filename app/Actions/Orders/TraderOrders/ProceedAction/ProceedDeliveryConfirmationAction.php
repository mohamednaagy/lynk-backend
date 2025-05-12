<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedDeliveryConfirmation;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\TraderOrderStatus;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Jobs\FinancingOrders\NotifyAdminsAboutOrderDeliveryConfirmed;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\TraderStrategyContext;
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
        Trader::driver($traderOrder->provider, $traderOrder->version)->validateDeliverySequence($traderOrder, $forceToProceed);

        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ConfirmDeliver);

        (new TraderStrategyContext($traderOrder->provider, $traderOrder->version))
            ->confirmDeliverCommodityToCustomer($traderOrder);

        dispatch(new NotifyAdminsAboutOrderDeliveryConfirmed($traderOrder));

        $canUpdateOrderStatus = Trader::driver($traderOrder->provider, $traderOrder->version)->confirmDelivery($traderOrder);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::DeliveryConfirmed);

        if ($canUpdateOrderStatus) {
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
        }

        return [];
    }
}
