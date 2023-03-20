<?php

namespace App\Actions\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Actions\Contracts\Orders\TraderOrders\MurabhaPurchaseOffer\HandleMurabhaPurchaseOffer;
use App\Enums\MurabhaStep;
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
        $trader = Trader::driver($traderOrder->provider);

        $this->createStepHistories(
            $request,
            $trader,
            $traderOrder,
            MurabhaStep::MurabhaOfferIssued
        );
    }
}
