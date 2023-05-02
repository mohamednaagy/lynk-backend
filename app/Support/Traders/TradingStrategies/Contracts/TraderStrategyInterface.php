<?php

namespace App\Support\Traders\TradingStrategies\Contracts;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface TraderStrategyInterface
{
    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request);

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, Request $request);

    public function UpdateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request);

    public function UpdateMurabhaCompleteDocument(TraderOrder $traderOrder, Request $request);
}
