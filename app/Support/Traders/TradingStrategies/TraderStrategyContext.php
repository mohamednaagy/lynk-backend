<?php

namespace App\Support\Traders\TradingStrategies;

use App\Models\TraderOrder;
use App\Support\Traders\TradingStrategies\Bursam\BursamStrategyV1;
use App\Support\Traders\TradingStrategies\Bursam\BursamStrategyV2;
use App\Support\Traders\TradingStrategies\Bursam\LynkStrategyV1;
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
            'dmcc.v1', 'fake.v1' => app(DmccStrategyV1::class),
            'bursam.v1' => app(BursamStrategyV1::class),
            'bursam.v2' => app(BursamStrategyV2::class),
            'lynk.v1' => app(LynkStrategyV1::class),
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

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request): void
    {
        $this->strategy->updateCommodityCertificateForClient($traderOrder, $request);
    }

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, array $data): void
    {
        $this->strategy->updateMurabhaCompleteDocument($traderOrder, $data);
    }
}
