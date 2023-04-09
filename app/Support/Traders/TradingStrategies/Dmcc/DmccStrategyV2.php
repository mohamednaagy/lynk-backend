<?php

namespace App\Support\Traders\TradingStrategies\Dmcc;

use App\Models\TraderOrder;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use Illuminate\Http\Request;

abstract class DmccStrategyV2 implements TraderStrategyInterface
{
    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
    }
}
