<?php

namespace App\Support\Traders\TradingStrategies\Contracts;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface TraderStrategyInterface
{
    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request);
}
