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
     * @param  int  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     *
     * @throws \Exception
     */
    public function handle(
        Request $request,
        int $order,
        TraderOrder $traderOrder
    ): void {
        $order = FinancingOrder::lockForUpdate()->findOrFail($order);
        $trader = Trader::driver($traderOrder->provider);

        $this->createStepHistories(
            $request,
            $trader,
            $traderOrder,
            FinancingOrderStatus::MurabhaOfferIssued
        );

        if (! $traderOrder->checkOrderStepComplete(FinancingOrderStatus::MurabhaOfferIssued)) {
            $trader->updateOrderStatus($order, FinancingOrderStatus::MurabhaOfferIssued);
        }
    }
}
