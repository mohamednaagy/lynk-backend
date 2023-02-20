<?php

namespace App\Actions\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Actions\Contracts\Orders\TraderOrders\MurabhaPurchaseOffer\HandleMurabhaPurchaseOffer;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\Request;

class HandleMurabhaPurchaseOfferAction implements HandleMurabhaPurchaseOffer
{
    use TraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     */
    public function handle(
        Request $request,
        FinancingOrder $order,
        TraderOrder $traderOrder
    ): void {
        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            FinancingOrderStatus::MurabhaOfferIssued
        );

        $trader = Trader::driver($traderOrder->provider);

        $this->createStepHistories(
            $request,
            $trader,
            $traderOrder,
            FinancingOrderStatus::MurabhaOfferIssued
        );

        if ($canUpdateOrderStatus) {
            $trader->updateOrderStatus($order, FinancingOrderStatus::MurabhaOfferIssued);
        }
    }
}
