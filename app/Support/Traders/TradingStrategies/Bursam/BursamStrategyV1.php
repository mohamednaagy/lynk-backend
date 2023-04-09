<?php

namespace App\Support\Traders\TradingStrategies\Bursam;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

class BursamStrategyV1 extends BaseBursamStrategy
{
    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
    }
}
