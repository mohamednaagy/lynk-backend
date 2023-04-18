<?php

namespace App\Support\Traders\TradingStrategies;

use App\Models\TraderOrder;
use App\Support\Traders\TradingStrategies\Bursam\BursamStrategyV1;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\TradingStrategies\Dmcc\DmccStrategyV1;
use Illuminate\Http\Request;

class TraderStrategyContext
{
    protected TraderStrategyInterface $strategy;

    public function __construct(string $provider, string $version)
    {
        $strategy = $provider.'.'.$version;

        $this->strategy = match ($strategy) {
            'dmcc.v1' => app(DmccStrategyV1::class),
            'bursam.v1' => app(BursamStrategyV1::class),
            default => throw new \InvalidArgumentException('Invalid Trader Or Version')
        };
    }

    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request): void
    {
        $this->strategy->updatePurchasingCommodity($traderOrder, $request);
    }

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, Request $request): void
    {
        $this->strategy->updateMurabahaPurchaseOffer($traderOrder, $request);
    }
}
