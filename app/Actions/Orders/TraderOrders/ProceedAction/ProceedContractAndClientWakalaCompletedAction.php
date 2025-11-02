<?php

namespace App\Actions\Orders\TraderOrders\ProceedAction;

use App\Actions\Contracts\Orders\TraderOrders\ProceedAction\ProceedContractAndClientWakalaCompleted;
use App\Enums\FinancingOrderProceedCase;
use App\Enums\MurabhaStep;
use App\Enums\Trader as EnumTrader;
use App\Exceptions\OrderRequiresClientVerification;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Factories\TraderOrders\TraderOrderProceedCaseFactory;
use App\Models\TraderOrder;
use App\Services\TraderOrder\TraderOrderProceedCaseService;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;

class ProceedContractAndClientWakalaCompletedAction implements ProceedContractAndClientWakalaCompleted
{
    use TraderHelperTrait;

    private $requiredStepForProccessedTraderOrder = [
        EnumTrader::Lynk => MurabhaStep::ContractSigned,
        EnumTrader::Bursam => MurabhaStep::ContractSigned,
        EnumTrader::FakeDmcc => MurabhaStep::ClientWakala,
        EnumTrader::Dmcc => MurabhaStep::ClientWakala,
    ];

    /**
     * @throws OrderStatusDoesNotFollowSequenceException
     * @throws OrderRequiresClientVerification
     */
    public function handle(TraderOrder $traderOrder, bool $forceToProceed = false): array
    {
        $order = $traderOrder->order;

        if ($order->is_verification_required) {
            throw new OrderRequiresClientVerification;
        }

        $proceedCaseHandler = TraderOrderProceedCaseFactory::handle(FinancingOrderProceedCase::getDescription(FinancingOrderProceedCase::ContractAndClientWakalaCompleted));
        $canProceed = $proceedCaseHandler->canProceed($traderOrder, $forceToProceed);
        if (! $canProceed) {
            throw new OrderStatusDoesNotFollowSequenceException(
                [
                    'financingOrderId' => $traderOrder->financing_order_id,
                    'traderOrderId' => $traderOrder->id,
                ]
            );
        }
        app(TraderOrderProceedCaseService::class)->createCase($traderOrder->id, FinancingOrderProceedCase::ContractAndClientWakalaCompleted);

        Trader::driver($traderOrder->provider, $traderOrder->version)->processProceedContractAndClientWakala($traderOrder);

        return [];
    }
}
