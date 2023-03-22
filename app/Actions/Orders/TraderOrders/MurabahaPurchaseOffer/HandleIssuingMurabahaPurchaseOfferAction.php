<?php

namespace App\Actions\Orders\TraderOrders\MurabahaPurchaseOffer;

use App\Actions\Contracts\Orders\TraderOrders\MurabahaPurchaseOffer\HandleIssuingMurabahaPurchaseOffer;
use App\Enums\MurabhaStep;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Exception;
use Illuminate\Http\Request;

class HandleIssuingMurabahaPurchaseOfferAction implements HandleIssuingMurabahaPurchaseOffer
{
    use TraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     *
     * @throws Exception
     */
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void
    {
        $trader = Trader::driver($traderOrder->provider);

        $this->createStepHistories(
            $request,
            $trader,
            $traderOrder,
            MurabhaStep::MurabhaOfferIssued
        );
    }
}
