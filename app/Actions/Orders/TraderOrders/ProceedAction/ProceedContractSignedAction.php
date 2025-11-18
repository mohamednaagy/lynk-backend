<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractSigned;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderProceedCase;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Factories\TraderOrders\TraderOrderProceedCaseFactory;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\Traders\Facades\Trader;
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
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): void
    {
        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(FinancingOrderProceedCase::ContractSigned);

        if (! $proceedCaseHandler->canProceed($traderOrder, $forceToProceed)) {
            throw new OrderStatusDoesNotFollowSequenceException([
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $traderOrder->id,
            ]);
        }

        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ContractSigned);

        $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

        Trader::driver($traderOrder->provider, $traderOrder->version)->processProceedContractSigned($traderOrder);

    }
}
