<?php

namespace App\Support\Traders\TradingStrategies\Dmcc;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

class DmccStrategyV1 extends BaseDmccStrategy
{
    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
    }
}
