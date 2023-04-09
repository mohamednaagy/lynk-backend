<?php

namespace App\Support\Traders\TradingStrategies;

use App\Models\TraderOrder;
use App\Support\Traders\TradingStrategies\Bursam\BursamStrategyV1;
use App\Support\Traders\TradingStrategies\Dmcc\BaseDmccStrategy;
use App\Support\Traders\TradingStrategies\Dmcc\DmccStrategyV1;
use App\Support\Traders\TradingStrategies\Dmcc\DmccStrategyV2;
use Illuminate\Http\Request;

class TraderStrategyContext
{
    protected BaseDmccStrategy $strategy;

    public function __construct(string $strategy, string $version)
    {
        $strategy = $strategy.'.'.$version;

        $this->strategy = match ($strategy) {
            'dmcc.v1' => app(DmccStrategyV1::class),
            'dmcc.v2' => app(DmccStrategyV2::class),
            'bursam.v1' => app(BursamStrategyV1::class),
        };
    }

    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
        $this->strategy->updatePurchasingCommodity($traderOrder, $request);
    }

    public function test(): string
    {
        return $this->strategy->test();
    }
}
