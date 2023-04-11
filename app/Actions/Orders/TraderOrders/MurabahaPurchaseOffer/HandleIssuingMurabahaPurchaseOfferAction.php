<?php

namespace App\Actions\Orders\TraderOrders\MurabahaPurchaseOffer;

use App\Actions\Contracts\Orders\TraderOrders\MurabahaPurchaseOffer\HandleIssuingMurabahaPurchaseOffer;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use Exception;
use Illuminate\Http\Request;

class HandleIssuingMurabahaPurchaseOfferAction implements HandleIssuingMurabahaPurchaseOffer
{
    use DmccTraderHelperTrait;

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
    }
}
