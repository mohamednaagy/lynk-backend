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
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ContractSigned));
        $canProceed = $proceedCaseHandler->canProceed($traderOrder, $forceToProceed);
        if ($canProceed) {
            app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ContractSigned);

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ContractSigned);

            Trader::driver($traderOrder->provider, $traderOrder->version)->processProceedContractSigned($traderOrder);

            return [];
        } else {
            throw new OrderStatusDoesNotFollowSequenceException(
                [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                ]
            );
        }

    }
}
